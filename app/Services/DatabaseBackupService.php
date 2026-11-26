<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\DatabaseBackupPolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Phase 1: verified, private, PDO/MySQL database backups.
 *
 * No restoration, no SQL upload, no shell binaries, no schema changes.
 */
class DatabaseBackupService
{
    private string $directory = 'backups';

    private ?\PDO $pdo = null;

    /**
     * Verify the minimum capabilities required for a safe export.
     *
     * @return array<string,mixed>
     */
    public function capabilities(): array
    {
        $this->ensureDirectory();

        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('The pdo_mysql PHP extension is required for database backups.');
        }

        $base = $this->absolutePath($this->directory);
        if (!is_writable($base)) {
            throw new RuntimeException('The private backup directory is not writable.');
        }

        $probe = $base . DIRECTORY_SEPARATOR . '.capability_' . Str::random(10);
        $renamed = $probe . '.ok';
        if (file_put_contents($probe, 'ok') === false || !rename($probe, $renamed)) {
            @unlink($probe);
            @unlink($renamed);
            throw new RuntimeException('Temporary file creation and atomic rename are not supported in backup storage.');
        }
        @unlink($probe);
        @unlink($renamed);

        $pdo = $this->pdo();
        $grants = [];
        try {
            $stmt = $pdo->query('SHOW GRANTS FOR CURRENT_USER()');
            $grants = $this->fetchAllClosed($stmt, \PDO::FETCH_COLUMN);
        } catch (Throwable) {
            // Some managed hosts hide GRANTS; assume SELECT is available and let
            // the export fail clearly if a required privilege is missing.
        }
        $hasSelect = collect($grants)->contains(
            fn ($grant) => preg_match('/\b(SELECT|ALL PRIVILEGES)\b/i', (string) $grant) === 1
        );
        if ($grants && !$hasSelect) {
            throw new RuntimeException('The database user does not have SELECT permission required for backups.');
        }

        return [
            'pdo_mysql' => true,
            'private_storage_writable' => true,
            'atomic_rename' => true,
            'database_select' => $grants ? $hasSelect : null,
            'memory_limit' => ini_get('memory_limit') ?: 'unknown',
            'max_execution_time' => ini_get('max_execution_time') ?: 'unknown',
        ];
    }

    /**
     * Create, verify and atomically publish a database backup.
     *
     * @return array<string,mixed> verified manifest
     */
    public function createBackup(?User $actor = null): array
    {
        return $this->withOperationLock(
            fn () => $this->createBackupInternal($actor),
            'Another database backup is already running (or a restore is in progress). Please try again shortly.'
        );
    }

    /**
     * Create and verify a backup while the caller already holds the shared
     * operation lock (used for pre-restore safety backups).
     *
     * @return array<string,mixed> verified manifest
     */
    public function createBackupWithinLock(?User $actor = null): array
    {
        return $this->createBackupInternal($actor);
    }

    /**
     * Execute a callback while holding the exclusive backup/restore lock.
     *
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public function withOperationLock(
        callable $callback,
        string $conflictMessage = 'Another database backup is already running. Please try again shortly.'
    ): mixed {
        $lock = $this->acquireBackupLock($conflictMessage);

        try {
            return $callback();
        } finally {
            $this->releaseBackupLock($lock);
        }
    }

    /**
     * Non-blocking probe to check whether backup.lock is currently held by an
     * active PHP process.
     */
    public function isOperationLockHeld(): bool
    {
        $this->ensureDirectory();
        $handle = @fopen($this->absolutePath($this->directory . '/backup.lock'), 'c');
        if ($handle === false) {
            return false;
        }
        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return true;
        }
        @flock($handle, LOCK_UN);
        fclose($handle);

        return false;
    }

    /**
     * Backup body, executed while the private backup lock is held.
     */
    private function createBackupInternal(?User $actor): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $this->capabilities();
        $this->cleanupTemporaryFiles();

        $pdo = $this->pdo();
        $schema = $this->schemaSnapshot($pdo);
        $check = DatabaseBackupPolicy::checkSchema($schema['tables'], $schema['objects']);
        if (!$check['ok']) {
            $this->writeAuditEvent([
                'event' => 'backup_creation_failed',
                'reason' => 'schema_drift',
                'actor_id' => $actor?->id,
                'details' => $check,
            ]);
            throw new RuntimeException(
                'Schema drift detected; backup refused. Unexpected tables: '
                . implode(', ', $check['unexpected_tables'])
                . '. Missing tables: ' . implode(', ', $check['missing_tables'])
                . '. Unexpected objects: ' . implode(', ', $check['unexpected_objects'])
                . '. Non-InnoDB tables: ' . implode(', ', $check['non_innodb']) . '.'
            );
        }

        $id = strtolower(Str::random(24));
        $this->assertBackupId($id);
        $sqlName = 'backup_' . $id . '.sql';
        $jsonName = 'backup_' . $id . '.json';
        $tmpSql = $this->absolutePath($this->directory . '/' . $sqlName . '.tmp');
        $tmpJson = $this->absolutePath($this->directory . '/' . $jsonName . '.tmp');
        $finalSql = $this->absolutePath($this->directory . '/' . $sqlName);
        $finalJson = $this->absolutePath($this->directory . '/' . $jsonName);

        $this->writeAuditEvent([
            'event' => 'backup_creation_started',
            'backup_id' => $id,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->full_name,
        ]);

        $handle = null;
        $committed = false;

        try {
            $handle = fopen($tmpSql, 'wb');
            if ($handle === false) {
                throw new RuntimeException('Unable to create the temporary SQL file.');
            }

            $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $pdo->beginTransaction();

            // A shared metadata lock is taken on every in-scope table by this
            // transactional read and held until commit/rollback. Concurrent
            // DDL on those tables cannot complete during the export.
            $rowCounts = [];
            foreach (DatabaseBackupPolicy::allTables() as $table) {
                $stmt = $pdo->query('SELECT COUNT(*) AS c FROM ' . $this->quoteIdentifier($table));
                $countRow = $this->fetchOneClosed($stmt);
                $rowCounts[$table] = (int) ($countRow['c'] ?? 0);
            }

            $ddlBefore = [];
            foreach (DatabaseBackupPolicy::allTables() as $table) {
                $ddlBefore[$table] = $this->tableDdl($pdo, $table);
            }

            $this->write($handle, '-- ZenFlow database backup' . PHP_EOL);
            $this->write($handle, '-- format_version: ' . DatabaseBackupPolicy::FORMAT_VERSION . PHP_EOL);
            $this->write($handle, '-- backup_id: ' . $id . PHP_EOL);
            $this->write($handle, '-- source_database: ' . $this->databaseName() . PHP_EOL);
            $this->write($handle, '-- created_at: ' . Carbon::now('Asia/Manila')->toIso8601String() . PHP_EOL . PHP_EOL);

            foreach (DatabaseBackupPolicy::allTables() as $table) {
                $this->write($handle, '-- TABLE: ' . $table . PHP_EOL);
                $this->write($handle, $ddlBefore[$table] . ';' . PHP_EOL . PHP_EOL);

                if (!DatabaseBackupPolicy::includesData($table)) {
                    continue;
                }

                $stmt = $pdo->query('SELECT * FROM ' . $this->quoteIdentifier($table));
                try {
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $columns = array_keys($row);
                        $quotedColumns = implode(', ', array_map(fn ($column) => $this->quoteIdentifier((string) $column), $columns));
                        $values = implode(', ', array_map(fn ($value) => $this->quoteValue($pdo, $value), array_values($row)));
                        $this->write($handle, 'INSERT INTO ' . $this->quoteIdentifier($table) . ' (' . $quotedColumns . ') VALUES (' . $values . ');' . PHP_EOL);
                    }
                } finally {
                    $stmt->closeCursor();
                }
                $this->write($handle, PHP_EOL);
            }

            $schemaAfter = $this->schemaSnapshot($pdo);
            $checkAfter = DatabaseBackupPolicy::checkSchema($schemaAfter['tables'], $schemaAfter['objects']);
            $ddlAfter = [];
            foreach (DatabaseBackupPolicy::allTables() as $table) {
                $ddlAfter[$table] = $this->tableDdl($pdo, $table);
            }
            $ddlMatches = true;
            foreach (DatabaseBackupPolicy::allTables() as $table) {
                if (DatabaseBackupPolicy::normalizeDdl($ddlBefore[$table]) !== DatabaseBackupPolicy::normalizeDdl($ddlAfter[$table])) {
                    $ddlMatches = false;
                    break;
                }
            }

            if (!$checkAfter['ok'] || !$ddlMatches) {
                throw new RuntimeException('Schema changed during backup export; backup discarded.');
            }

            $pdo->commit();
            $committed = true;

            $this->flush($handle);
            fclose($handle);
            $handle = null;

            $sqlSha = hash_file('sha256', $tmpSql);
            if ($sqlSha === false) {
                throw new RuntimeException('Unable to calculate backup checksum.');
            }

            $manifest = [
                'format_version' => DatabaseBackupPolicy::FORMAT_VERSION,
                'manifest_version' => DatabaseBackupPolicy::MANIFEST_VERSION,
                'backup_id' => $id,
                'created_at' => Carbon::now('Asia/Manila')->toIso8601String(),
                'source_database' => $this->databaseName(),
                'database_charset' => $schema['charset'],
                'database_collation' => $schema['collation'],
                'included_tables' => DatabaseBackupPolicy::allTables(),
                'data_tables' => DatabaseBackupPolicy::DATA_TABLES,
                'structure_only_tables' => DatabaseBackupPolicy::STRUCTURE_ONLY_TABLES,
                'excluded_reasons' => DatabaseBackupPolicy::EXCLUSION_REASONS,
                'table_count' => count(DatabaseBackupPolicy::allTables()),
                'row_counts' => $rowCounts,
                'exported_row_count' => array_sum(array_intersect_key(
                    $rowCounts,
                    array_flip(DatabaseBackupPolicy::DATA_TABLES)
                )),
                'objects' => $schema['objects'],
                'file_size' => filesize($tmpSql) ?: 0,
                'sql_sha256' => $sqlSha,
                'verification_status' => 'pending',
            ];

            // Sign the FINAL manifest. Earlier revisions signed the pending
            // manifest and then changed verification_status, which produced a
            // published manifest whose hash/HMAC could never be re-verified.
            $manifest['verification_status'] = 'verified';
            $manifest['manifest_hash'] = hash('sha256', $this->canonicalJson($manifest));
            $manifest['hmac_sha256'] = $this->hmac($this->hmacMaterial($manifest));

            $canonical = $this->canonicalJson($manifest);
            if (file_put_contents($tmpJson, $canonical) === false) {
                throw new RuntimeException('Unable to write backup manifest.');
            }

            // Verify the actual temporary files, not in-memory values, before
            // publishing either artifact.
            $manifest = $this->verifyArtifact($tmpSql, $tmpJson);

            // Two-file publication cannot be a single atomic filesystem
            // operation; incomplete pairs are ignored by listBackups() and
            // cleaned up on the next backup attempt.

            if (!rename($tmpSql, $finalSql) || !rename($tmpJson, $finalJson)) {
                @unlink($finalSql);
                @unlink($finalJson);
                throw new RuntimeException('Unable to publish the verified backup atomically.');
            }

            $this->writeAuditEvent([
                'event' => 'backup_verification_passed',
                'backup_id' => $id,
                'actor_id' => $actor?->id,
                'actor_name' => $actor?->full_name,
                'sql_sha256' => $manifest['sql_sha256'],
                'file_size' => $manifest['file_size'],
            ]);

            $this->mirrorActivityLog($actor, $id, $manifest);

            return $manifest;
        } catch (Throwable $e) {
            if ($committed === false && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (is_resource($handle)) {
                fclose($handle);
            }
            @unlink($tmpSql);
            @unlink($tmpJson);
            @unlink($finalSql);
            @unlink($finalJson);

            $this->safeAuditEvent([
                'event' => 'backup_creation_failed',
                'backup_id' => $id,
                'actor_id' => $actor?->id,
                'reason' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * List only verified backups. Incomplete or tampered artifacts are ignored.
     *
     * @return array<int,array<string,mixed>>
     */
    public function listBackups(): array
    {
        $this->ensureDirectory();
        $this->cleanupTemporaryFiles();
        $this->cleanupOrphanedBackups();

        $base = $this->absolutePath($this->directory);
        $manifests = [];
        foreach (glob($base . DIRECTORY_SEPARATOR . 'backup_*.json') ?: [] as $path) {
            $name = basename($path);
            if (!preg_match('/^backup_([A-Za-z0-9_-]+)\.json$/', $name, $matches)) {
                continue;
            }

            try {
                $manifest = $this->findBackup($matches[1]);
                $manifests[] = $manifest;
            } catch (Throwable) {
                continue;
            }
        }

        usort($manifests, fn ($a, $b) => strcmp((string) $b['created_at'], (string) $a['created_at']));

        return $manifests;
    }

    /**
     * Return a verified manifest by backup id.
     *
     * @return array<string,mixed>
     */
    public function findBackup(string $id): array
    {
        $this->assertBackupId($id);

        $sql = $this->absolutePath($this->directory . '/backup_' . $id . '.sql');
        $json = $this->absolutePath($this->directory . '/backup_' . $id . '.json');
        $this->assertNotSymlink($sql);
        $this->assertNotSymlink($json);
        if (!is_file($sql) || !is_file($json)) {
            throw new RuntimeException('Backup not found.');
        }

        return $this->verifyArtifact($sql, $json);
    }

    public function downloadPath(string $id): string
    {
        $this->findBackup($id);

        return $this->directory . '/backup_' . $id . '.sql';
    }

    /**
     * Append a durable audit event to the private JSONL audit log.
     */
    public function writeAuditEvent(array $event): void
    {
        $this->ensureDirectory();
        $path = $this->absolutePath($this->directory . '/backup-audit.log');
        $this->assertNotSymlink($path);
        $handle = fopen($path, 'ab');
        if ($handle === false) {
            throw new RuntimeException('Unable to open the backup audit log.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock the backup audit log.');
            }

            $event['at'] = Carbon::now('Asia/Manila')->toIso8601String();
            $line = json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($line === false) {
                throw new RuntimeException('Unable to encode a backup audit event.');
            }
            $this->write($handle, $line . PHP_EOL);
            $this->flush($handle);
        } finally {
            @flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Non-blocking private lock preventing simultaneous backup exports.
     * The lock is OS-released if the PHP process terminates.
     *
     * @return resource
     */
    private function acquireBackupLock(
        string $conflictMessage = 'Another database backup is already running. Please try again shortly.'
    ) {
        $this->ensureDirectory();
        $lockPath = $this->absolutePath($this->directory . '/backup.lock');
        $this->assertNotSymlink($lockPath);
        $handle = fopen($lockPath, 'c');
        if ($handle === false) {
            throw new RuntimeException('Unable to open the backup lock file.');
        }
        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new RuntimeException($conflictMessage);
        }

        return $handle;
    }

    /** @param resource $handle */
    private function releaseBackupLock($handle): void
    {
        @flock($handle, LOCK_UN);
        fclose($handle);
    }

    private function safeAuditEvent(array $event): void
    {
        try {
            $this->writeAuditEvent($event);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function cleanupOrphanedBackups(): void
    {
        $this->ensureDirectory();
        $base = $this->absolutePath($this->directory);

        foreach (glob($base . DIRECTORY_SEPARATOR . 'backup_*.sql') ?: [] as $path) {
            if (is_link($path)) {
                continue;
            }
            $name = basename($path);
            if (!preg_match('/^backup_([A-Za-z0-9_-]+)\.sql$/', $name, $matches)) {
                continue;
            }
            if (!is_file($base . DIRECTORY_SEPARATOR . 'backup_' . $matches[1] . '.json')) {
                @unlink($path);
            }
        }

        foreach (glob($base . DIRECTORY_SEPARATOR . 'backup_*.json') ?: [] as $path) {
            if (is_link($path)) {
                continue;
            }
            $name = basename($path);
            if (!preg_match('/^backup_([A-Za-z0-9_-]+)\.json$/', $name, $matches)) {
                continue;
            }
            if (!is_file($base . DIRECTORY_SEPARATOR . 'backup_' . $matches[1] . '.sql')) {
                @unlink($path);
            }
        }
    }

    public function cleanupTemporaryFiles(): void
    {
        $base = $this->absolutePath($this->directory);
        foreach (glob($base . DIRECTORY_SEPARATOR . 'backup_*.tmp') ?: [] as $path) {
            if (is_link($path)) {
                continue;
            }
            if (filemtime($path) !== false && filemtime($path) < time() - 86400) {
                @unlink($path);
            }
        }
    }

    /**
     * @return array{table:array<int,array<string,mixed>>, objects:array<string,array<int,string>>, charset:string, collation:string}
     */
    private function schemaSnapshot(\PDO $pdo): array
    {
        $db = $pdo->quote($this->databaseName());
        $stmt = $pdo->query(
            "SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA={$db} AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME"
        );
        $tables = $this->fetchAllClosed($stmt);

        $objects = [
            'views' => $this->fetchAllClosed($pdo->query("SELECT TABLE_NAME AS n FROM information_schema.VIEWS WHERE TABLE_SCHEMA={$db}"), \PDO::FETCH_COLUMN),
            'triggers' => $this->fetchAllClosed($pdo->query("SELECT TRIGGER_NAME AS n FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA={$db}"), \PDO::FETCH_COLUMN),
            'routines' => $this->fetchAllClosed($pdo->query("SELECT ROUTINE_NAME AS n FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA={$db}"), \PDO::FETCH_COLUMN),
            'events' => $this->fetchAllClosed($pdo->query("SELECT EVENT_NAME AS n FROM information_schema.EVENTS WHERE EVENT_SCHEMA={$db}"), \PDO::FETCH_COLUMN),
        ];

        $stmt = $pdo->query(
            "SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME={$db}"
        );
        $defaults = $this->fetchOneClosed($stmt);

        return [
            'tables' => $tables,
            'objects' => $objects,
            'charset' => (string) ($defaults['DEFAULT_CHARACTER_SET_NAME'] ?? ''),
            'collation' => (string) ($defaults['DEFAULT_COLLATION_NAME'] ?? ''),
        ];
    }

    private function tableDdl(\PDO $pdo, string $table): string
    {
        $stmt = $pdo->query('SHOW CREATE TABLE ' . $this->quoteIdentifier($table));
        $row = $this->fetchOneClosed($stmt);

        return (string) ($row['Create Table'] ?? (array_values($row)[1] ?? ''));
    }

    /**
     * @return array<string,mixed>
     */
    private function verifyArtifact(string $sqlPath, string $jsonPath): array
    {
        $raw = file_get_contents($jsonPath);
        if ($raw === false) {
            throw new RuntimeException('Unable to read backup manifest.');
        }

        $manifest = json_decode($raw, true);
        if (!is_array($manifest)) {
            throw new RuntimeException('Backup manifest is invalid.');
        }

        foreach (['format_version', 'manifest_version', 'backup_id', 'source_database', 'sql_sha256', 'manifest_hash', 'hmac_sha256', 'file_size'] as $key) {
            if (!array_key_exists($key, $manifest)) {
                throw new RuntimeException('Backup manifest is incomplete.');
            }
        }

        $this->assertBackupId((string) $manifest['backup_id']);

        $expectedSqlSha = hash_file('sha256', $sqlPath);
        if (!hash_equals((string) $manifest['sql_sha256'], (string) $expectedSqlSha)) {
            throw new RuntimeException('Backup SQL checksum mismatch.');
        }

        $size = filesize($sqlPath);
        if ($size === false || (int) $size !== (int) $manifest['file_size'] || $size <= 0) {
            throw new RuntimeException('Backup file size mismatch.');
        }

        $providedHmac = (string) $manifest['hmac_sha256'];
        $providedHash = (string) $manifest['manifest_hash'];
        unset($manifest['hmac_sha256'], $manifest['manifest_hash']);

        $canonical = $this->canonicalJson($manifest);
        if (!hash_equals($providedHash, hash('sha256', $canonical))) {
            throw new RuntimeException('Backup manifest hash mismatch.');
        }
        if (!hash_equals($providedHmac, $this->hmac($this->hmacMaterial(array_merge($manifest, [
            'manifest_hash' => $providedHash,
        ]))))) {
            throw new RuntimeException('Backup HMAC verification failed.');
        }

        $manifest['manifest_hash'] = $providedHash;
        $manifest['hmac_sha256'] = $providedHmac;
        $manifest['verification_status'] = 'verified';

        return $manifest;
    }

    /**
     * HMAC covers the exact SQL bytes, canonical manifest, table list, schema,
     * row counts, database identity, backup id and format versions.
     */
    private function hmacMaterial(array $manifest): string
    {
        return implode('|', [
            'v' . (string) $manifest['format_version'],
            (string) $manifest['backup_id'],
            (string) $manifest['source_database'],
            (string) $manifest['manifest_hash'],
            (string) $manifest['sql_sha256'],
        ]);
    }

    private function canonicalJson(mixed $value): string
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                $value = array_map(fn ($item) => $this->canonicalize($item), $value);
            } else {
                ksort($value);
                $value = array_map(fn ($item) => $this->canonicalize($item), $value);
            }
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new RuntimeException('Unable to encode backup metadata.');
        }

        return $encoded;
    }

    private function canonicalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->canonicalize($item), $value);
        }
        ksort($value);

        return array_map(fn ($item) => $this->canonicalize($item), $value);
    }

    private function hmac(string $material): string
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded === false) {
                throw new RuntimeException('Invalid application key for backup verification.');
            }
            $key = $decoded;
        }
        if (strlen($key) < 16) {
            throw new RuntimeException('Application key is too short for backup verification.');
        }

        return hash_hmac('sha256', $material, $key);
    }

    /**
     * Fetch a complete result set and always close the cursor. Required with
     * unbuffered PDO queries before issuing the next statement.
     */
    private function fetchAllClosed(\PDOStatement $statement, int $mode = \PDO::FETCH_ASSOC): array
    {
        try {
            return $statement->fetchAll($mode) ?: [];
        } finally {
            $statement->closeCursor();
        }
    }

    private function fetchOneClosed(\PDOStatement $statement): array
    {
        try {
            $row = $statement->fetch(\PDO::FETCH_ASSOC);

            return is_array($row) ? $row : [];
        } finally {
            $statement->closeCursor();
        }
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function quoteValue(\PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value)) {
            return (string) $value;
        }
        if (is_float($value)) {
            return sprintf('%.17g', $value);
        }

        return $pdo->quote((string) $value);
    }

    private function write($handle, string $content): void
    {
        $length = strlen($content);
        $offset = 0;
        while ($offset < $length) {
            $written = fwrite($handle, substr($content, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Unable to write backup data.');
            }
            $offset += $written;
        }
    }

    private function flush($handle): void
    {
        if (!fflush($handle)) {
            throw new RuntimeException('Unable to flush backup data.');
        }
        if (function_exists('fsync')) {
            @fsync($handle);
        }
    }

    private function assertNotSymlink(string $path): void
    {
        if (is_link($path)) {
            throw new RuntimeException('Symbolic links are not permitted in backup storage.');
        }
    }

    private function assertBackupId(string $id): void
    {
        if (preg_match('/^[a-z0-9_-]{1,64}$/', $id) !== 1) {
            throw new RuntimeException('Invalid backup identifier.');
        }
    }

    private function ensureDirectory(): void
    {
        if (!Storage::disk('local')->exists($this->directory)) {
            Storage::disk('local')->makeDirectory($this->directory);
        }
    }

    private function absolutePath(string $path): string
    {
        return Storage::disk('local')->path($path);
    }

    private function databaseName(): string
    {
        return (string) config('database.connections.mysql.database');
    }

    private function pdo(): \PDO
    {
        if ($this->pdo instanceof \PDO) {
            return $this->pdo;
        }

        $config = config('database.connections.mysql');
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            (string) ($config['host'] ?? '127.0.0.1'),
            (string) ($config['port'] ?? '3306'),
            (string) ($config['database'] ?? ''),
            (string) ($config['charset'] ?? 'utf8mb4')
        );

        $this->pdo = new \PDO($dsn, (string) ($config['username'] ?? ''), (string) ($config['password'] ?? ''), [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
        ]);

        return $this->pdo;
    }

    private function mirrorActivityLog(?User $actor, string $id, array $manifest): void
    {
        try {
            ActivityLog::create([
                'user_id' => $actor?->id,
                'action' => 'database_backup_created',
                'entity_type' => null,
                'entity_id' => null,
                'details' => json_encode([
                    'backup_id' => $id,
                    'sql_sha256' => $manifest['sql_sha256'],
                    'table_count' => $manifest['table_count'],
                    'exported_row_count' => $manifest['exported_row_count'],
                    'file_size' => $manifest['file_size'],
                ], JSON_UNESCAPED_UNICODE),
                'ip_address' => request()->ip(),
            ]);
        } catch (Throwable) {
            // Filesystem audit is authoritative; activity_logs is supplementary.
        }
    }
}
