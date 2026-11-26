<?php

namespace Tests\Feature;

use App\Services\DatabaseBackupService;
use App\Services\DatabaseRestoreService;
use App\Support\DatabaseBackupPolicy;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * MySQL-only integration coverage. These tests are skipped unless a separate
 * disposable database is configured through BACKUP_TEST_DB_* environment
 * variables. The live `spa` database is never an acceptable target.
 */
class DatabaseBackupIntegrationTest extends TestCase
{
    private string $testDatabase;

    public static function assertSafeDisposableDatabaseConfig(string $testDatabase, ?string $configuredLiveDatabase = null): void
    {
        $normalizedTarget = trim($testDatabase);
        if ($normalizedTarget === '') {
            throw new RuntimeException('BACKUP_TEST_DB_DATABASE must be set to a disposable MySQL database.');
        }

        if (strtolower($normalizedTarget) === 'spa') {
            throw new RuntimeException('Refusing to run backup integration tests against the prohibited live database [spa].');
        }

        $normalizedLive = trim((string) $configuredLiveDatabase);
        if ($normalizedLive !== '' && $normalizedLive !== ':memory:' && strtolower($normalizedTarget) === strtolower($normalizedLive)) {
            throw new RuntimeException('Refusing to run backup integration tests against the configured live database.');
        }
    }

    public static function assertSelectedDatabaseMatches(string $testDatabase, ?string $selectedDatabase): void
    {
        $normalizedTarget = trim($testDatabase);
        $normalizedSelected = trim((string) $selectedDatabase);

        if ($normalizedSelected === '') {
            throw new RuntimeException('Dedicated MySQL connection did not select any database.');
        }

        if (strtolower($normalizedSelected) === 'spa') {
            throw new RuntimeException('Refusing to run backup integration tests: dedicated MySQL connection resolved to prohibited live database [spa].');
        }

        if ($normalizedSelected !== $normalizedTarget) {
            throw new RuntimeException('Refusing to run backup integration tests: selected database does not match BACKUP_TEST_DB_DATABASE.');
        }
    }

    protected function setUp(): void
    {
        // Execute safety guards BEFORE parent::setUp() or any database operation.
        // This class intentionally does not use RefreshDatabase so no migration
        // can ever run prior to verifying a dedicated disposable MySQL schema.
        $testDatabase = trim((string) env('BACKUP_TEST_DB_DATABASE', ''));
        if ($testDatabase === '') {
            $this->markTestSkipped('Set BACKUP_TEST_DB_DATABASE to a separate disposable MySQL database to run backup integration tests.');
        }

        self::assertSafeDisposableDatabaseConfig($testDatabase, (string) env('DB_DATABASE', ''));

        parent::setUp();

        $this->testDatabase = $testDatabase;
        $baseMysql = config('database.connections.mysql', []);

        $dedicatedConfig = array_merge($baseMysql, [
            'driver' => 'mysql',
            'host' => env('BACKUP_TEST_DB_HOST', $baseMysql['host'] ?? '127.0.0.1'),
            'port' => env('BACKUP_TEST_DB_PORT', $baseMysql['port'] ?? '3306'),
            'database' => $testDatabase,
            'username' => env('BACKUP_TEST_DB_USERNAME', $baseMysql['username'] ?? ''),
            'password' => env('BACKUP_TEST_DB_PASSWORD', $baseMysql['password'] ?? ''),
        ]);

        config()->set('database.connections.backup_test_mysql', $dedicatedConfig);
        DB::purge('backup_test_mysql');

        $connection = DB::connection('backup_test_mysql');
        if ($connection->getDriverName() !== 'mysql') {
            $this->markTestSkipped('BACKUP_TEST_DB_* must resolve to a MySQL connection.');
        }

        $selected = $connection->selectOne('SELECT DATABASE() AS db');
        self::assertSelectedDatabaseMatches($testDatabase, isset($selected->db) ? (string) $selected->db : null);

        // Only after the dedicated connection is verified may the mysql connection
        // used by DatabaseBackupService and migrations be pointed at it.
        config()->set('database.connections.mysql', $dedicatedConfig);
        DB::purge('mysql');

        Artisan::call('migrate:fresh', ['--database' => 'mysql', '--force' => true]);
        Storage::fake('local');
    }

    public function test_backup_covers_all_tables_and_records(): void
    {
        $manifest = app(DatabaseBackupService::class)->createBackup();

        $this->assertSame(38, $manifest['table_count']);
        $this->assertSame(DatabaseBackupPolicy::allTables(), $manifest['included_tables']);
        $this->assertCount(7, $manifest['structure_only_tables']);
        $this->assertSame('verified', $manifest['verification_status']);
        $this->assertSame($this->testDatabase, $manifest['source_database']);

        $sql = Storage::disk('local')->get('backups/backup_' . $manifest['backup_id'] . '.sql');
        $this->assertIsString($sql);
        foreach (DatabaseBackupPolicy::allTables() as $table) {
            $this->assertStringContainsString('CREATE TABLE `' . $table . '`', $sql);
        }
        foreach (DatabaseBackupPolicy::STRUCTURE_ONLY_TABLES as $table) {
            $this->assertStringNotContainsString('INSERT INTO `' . $table . '`', $sql);
        }
    }

    public function test_manifest_integrity_and_tamper_detection(): void
    {
        $manifest = app(DatabaseBackupService::class)->createBackup();
        $service = app(DatabaseBackupService::class);

        $verified = $service->findBackup($manifest['backup_id']);
        $this->assertSame($manifest['sql_sha256'], $verified['sql_sha256']);
        $this->assertSame($manifest['hmac_sha256'], $verified['hmac_sha256']);

        $path = 'backups/backup_' . $manifest['backup_id'] . '.sql';
        Storage::disk('local')->put($path, Storage::disk('local')->get($path) . "\n-- tampered");

        $this->expectException(RuntimeException::class);
        $service->findBackup($manifest['backup_id']);
    }

    public function test_verified_backup_can_be_found_listed_and_downloaded(): void
    {
        $service = app(DatabaseBackupService::class);
        $manifest = $service->createBackup();

        $found = $service->findBackup($manifest['backup_id']);
        $this->assertSame($manifest['sql_sha256'], $found['sql_sha256']);
        $this->assertSame('verified', $found['verification_status']);

        $ids = array_column($service->listBackups(), 'backup_id');
        $this->assertContains($manifest['backup_id'], $ids);

        $path = $service->downloadPath($manifest['backup_id']);
        $this->assertSame('backups/backup_' . $manifest['backup_id'] . '.sql', $path);
        $this->assertSame(
            Storage::disk('local')->get('backups/backup_' . $manifest['backup_id'] . '.sql'),
            Storage::disk('local')->get($path)
        );
    }

    public function test_manifest_tampering_is_rejected(): void
    {
        $service = app(DatabaseBackupService::class);
        $manifest = $service->createBackup();
        $jsonPath = 'backups/backup_' . $manifest['backup_id'] . '.json';

        $tampered = json_decode(Storage::disk('local')->get($jsonPath), true);
        $tampered['source_database'] = 'another_database';
        Storage::disk('local')->put($jsonPath, json_encode($tampered));

        $this->expectException(RuntimeException::class);
        $service->findBackup($manifest['backup_id']);
    }

    public function test_representative_values_are_exported(): void
    {
        DB::connection('mysql')->table('customers')->insert([
            'first_name' => "O'Neil \"Test\"",
            'last_name' => 'Ünicode',
            'email' => null,
            'phone_number' => '0917-000-0000',
            'medical_notes' => "line one\nline two; with semicolon",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $manifest = app(DatabaseBackupService::class)->createBackup();
        $sql = Storage::disk('local')->get('backups/backup_' . $manifest['backup_id'] . '.sql');

        $this->assertIsString($sql);
        $this->assertStringContainsString("O\\'Neil", $sql);
        $this->assertStringContainsString('Ünicode', $sql);
        $this->assertStringContainsString('NULL', $sql);
        $this->assertStringContainsString('line two; with semicolon', $sql);
    }

    public function test_schema_metadata_is_recorded(): void
    {
        $manifest = app(DatabaseBackupService::class)->createBackup();

        $this->assertNotEmpty($manifest['database_charset']);
        $this->assertNotEmpty($manifest['database_collation']);
        $this->assertArrayHasKey('cache', $manifest['row_counts']);
        $this->assertArrayHasKey('appointments', $manifest['row_counts']);
        $this->assertNotEmpty($manifest['objects']);
    }

    public function test_end_to_end_backup_and_restore_recovers_state_and_retains_safety_backup(): void
    {
        $customerId = DB::connection('mysql')->table('customers')->insertGetId([
            'first_name' => 'Restore',
            'last_name' => 'Baseline',
            'phone_number' => '09171112233',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $backupService = app(DatabaseBackupService::class);
        $restoreService = app(DatabaseRestoreService::class);

        $baselineManifest = $backupService->createBackup();

        // Mutate disposable database after baseline backup
        DB::connection('mysql')->table('customers')->where('id', $customerId)->delete();
        $this->assertSame(0, DB::connection('mysql')->table('customers')->where('id', $customerId)->count());

        // Restore from baseline backup
        $result = $restoreService->restore($baselineManifest['backup_id']);
        $this->assertSame(DatabaseRestoreService::STATE_COMPLETED, $result['state']);
        $this->assertTrue($result['verification']['sql_import']);
        $this->assertTrue($result['verification']['schema_verification']);
        $this->assertTrue($result['verification']['data_verification']);
        $this->assertTrue($result['verification']['smoke_checks']);

        // Original record is recovered and safety backup exists & is verified
        $this->assertSame(1, DB::connection('mysql')->table('customers')->where('id', $customerId)->count());
        $safety = $backupService->findBackup($result['safety_backup_id']);
        $this->assertSame('verified', $safety['verification_status']);

        // Verify 42 FKs, 7 empty structure-only tables, and FOREIGN_KEY_CHECKS=1
        $fkCount = (int) DB::connection('mysql')->selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND CONSTRAINT_TYPE = ?',
            [$this->testDatabase, 'FOREIGN KEY']
        )->c;
        $this->assertSame(42, $fkCount);

        foreach (DatabaseBackupPolicy::STRUCTURE_ONLY_TABLES as $structureTable) {
            $this->assertSame(0, DB::connection('mysql')->table($structureTable)->count());
        }

        $fkCheckRow = DB::connection('mysql')->selectOne("SHOW VARIABLES LIKE 'foreign_key_checks'");
        $this->assertSame('ON', strtoupper((string) ($fkCheckRow->Value ?? '')));
    }

    public function test_tampered_backup_is_rejected_without_modifying_disposable_database(): void
    {
        $customerId = DB::connection('mysql')->table('customers')->insertGetId([
            'first_name' => 'Intact',
            'last_name' => 'Record',
            'phone_number' => '09172223344',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $manifest = app(DatabaseBackupService::class)->createBackup();
        $path = 'backups/backup_' . $manifest['backup_id'] . '.sql';
        Storage::disk('local')->put($path, Storage::disk('local')->get($path) . "\n-- tampered");

        try {
            app(DatabaseRestoreService::class)->restore($manifest['backup_id']);
            $this->fail('Expected tampered backup to be rejected.');
        } catch (RuntimeException) {
            $this->assertSame(1, DB::connection('mysql')->table('customers')->where('id', $customerId)->count());
            $state = app(DatabaseRestoreService::class)->currentState();
            $this->assertSame(DatabaseRestoreService::STATE_FAILED, $state['state']);
            $this->assertFalse(app(DatabaseRestoreService::class)->isMaintenanceActive());
        }
    }

    public function test_mid_restore_sql_failure_enters_recovery_required_and_recovers_via_safety_backup(): void
    {
        $customerId = DB::connection('mysql')->table('customers')->insertGetId([
            'first_name' => 'Recover',
            'last_name' => 'Target',
            'phone_number' => '09173334455',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $backupService = app(DatabaseBackupService::class);
        $restoreService = app(DatabaseRestoreService::class);

        $goodManifest = $backupService->createBackup();
        $badId = 'badsqlrestore123';
        $badSql = Storage::disk('local')->get('backups/backup_' . $goodManifest['backup_id'] . '.sql')
            . "\nINSERT INTO `customers` (`nonexistent_column_for_test`) VALUES (1);\n";

        Storage::disk('local')->put('backups/backup_' . $badId . '.sql', $badSql);
        $badManifest = $goodManifest;
        $badManifest['backup_id'] = $badId;
        $badManifest['file_size'] = strlen($badSql);
        $badManifest['sql_sha256'] = hash('sha256', $badSql);
        $badManifest['verification_status'] = 'verified';
        unset($badManifest['manifest_hash'], $badManifest['hmac_sha256']);

        $canonical = $this->canonicalJsonForTest($badManifest);
        $badManifest['manifest_hash'] = hash('sha256', $canonical);
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true);
        }
        $badManifest['hmac_sha256'] = hash_hmac(
            'sha256',
            'v1|' . $badId . '|' . $badManifest['source_database'] . '|' . $badManifest['manifest_hash'] . '|' . $badManifest['sql_sha256'],
            $key
        );
        Storage::disk('local')->put('backups/backup_' . $badId . '.json', $this->canonicalJsonForTest($badManifest));

        // 1. Trigger mid-restore SQL failure -> must enter RECOVERY_REQUIRED & retain safety backup
        try {
            $restoreService->restore($badId);
            $this->fail('Expected SQL execution failure.');
        } catch (\Throwable) {
            $state = $restoreService->currentState();
            $this->assertSame(DatabaseRestoreService::STATE_RECOVERY_REQUIRED, $state['state']);
            $this->assertTrue($restoreService->isMaintenanceActive());
            $this->assertNotEmpty($state['safety_backup_id']);

            $fkCheckRow = DB::connection('mysql')->selectOne("SHOW VARIABLES LIKE 'foreign_key_checks'");
            $this->assertSame('ON', strtoupper((string) ($fkCheckRow->Value ?? '')));

            // 2. Recover using the automatically created pre-restore safety backup
            $recovered = $restoreService->restore((string) $state['safety_backup_id']);
            $this->assertSame(DatabaseRestoreService::STATE_COMPLETED, $recovered['state']);
            $this->assertFalse($restoreService->isMaintenanceActive());
            $this->assertSame(1, DB::connection('mysql')->table('customers')->where('id', $customerId)->count());
        }
    }

    private function canonicalJsonForTest(mixed $value): string
    {
        $normalize = function (mixed $v) use (&$normalize): mixed {
            if (!is_array($v)) {
                return $v;
            }
            if (!array_is_list($v)) {
                ksort($v);
            }

            return array_map($normalize, $v);
        };

        return json_encode($normalize($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
