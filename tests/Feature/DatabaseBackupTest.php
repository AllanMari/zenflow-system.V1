<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\DatabaseBackupService;
use App\Support\DatabaseBackupPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Phase 1 backup tests. These use SQLite/array test config and Storage::fake;
 * MySQL fidelity is covered separately by DatabaseBackupIntegrationTest.
 */
class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->admin = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Admin']);
        $this->admin->roles()->attach(Role::firstOrCreate(['name' => 'admin']));
    }

    public function test_admin_can_view_backup_page_and_no_sql_upload_is_exposed(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.database-backup.index'))
            ->assertOk()
            ->assertSee('Database Backup')
            ->assertSee('Create Backup')
            ->assertSee('Backup History')
            ->assertDontSee('Upload SQL');
    }

    public function test_non_admin_roles_cannot_access_backup_routes(): void
    {
        foreach (['receptionist', 'staff', 'customer'] as $roleName) {
            $user = User::factory()->create();
            $user->roles()->attach(Role::firstOrCreate(['name' => $roleName]));

            $this->actingAs($user)->get(route('admin.database-backup.index'))->assertStatus(403);
            $this->actingAs($user)->post(route('admin.database-backup.create'))->assertStatus(403);
            $this->actingAs($user)->get(route('admin.database-backup.download', 'abc123'))->assertStatus(403);
        }
    }

    public function test_policy_explicitly_classifies_all_thirty_eight_tables(): void
    {
        $this->assertCount(31, DatabaseBackupPolicy::DATA_TABLES);
        $this->assertCount(7, DatabaseBackupPolicy::STRUCTURE_ONLY_TABLES);
        $this->assertCount(38, DatabaseBackupPolicy::allTables());
        $this->assertSame(
            DatabaseBackupPolicy::STRUCTURE_ONLY_TABLES,
            array_keys(DatabaseBackupPolicy::EXCLUSION_REASONS)
        );
        $this->assertTrue(DatabaseBackupPolicy::includesData('appointments'));
        $this->assertFalse(DatabaseBackupPolicy::includesData('sessions'));
    }

    public function test_policy_detects_schema_drift(): void
    {
        $tables = array_map(fn ($name) => ['TABLE_NAME' => $name, 'ENGINE' => 'InnoDB'], DatabaseBackupPolicy::allTables());

        $ok = DatabaseBackupPolicy::checkSchema($tables, [
            'views' => [], 'triggers' => [], 'routines' => [], 'events' => [],
        ]);
        $this->assertTrue($ok['ok']);

        $drift = DatabaseBackupPolicy::checkSchema(
            array_merge($tables, [['TABLE_NAME' => 'surprise_table', 'ENGINE' => 'InnoDB']]),
            ['views' => ['v_surprise'], 'triggers' => [], 'routines' => [], 'events' => []]
        );

        $this->assertFalse($drift['ok']);
        $this->assertContains('surprise_table', $drift['unexpected_tables']);
        $this->assertContains('views:v_surprise', $drift['unexpected_objects']);
    }

    public function test_policy_detects_missing_tables_and_non_innodb_tables(): void
    {
        $tables = array_map(fn ($name) => ['TABLE_NAME' => $name, 'ENGINE' => 'InnoDB'], DatabaseBackupPolicy::allTables());
        array_shift($tables);
        $tables[0]['ENGINE'] = 'MyISAM';

        $result = DatabaseBackupPolicy::checkSchema($tables, [
            'views' => [], 'triggers' => [], 'routines' => [], 'events' => [],
        ]);

        $this->assertFalse($result['ok']);
        $this->assertNotEmpty($result['missing_tables']);
        $this->assertNotEmpty($result['non_innodb']);
    }

    public function test_invalid_backup_id_and_path_traversal_are_rejected(): void
    {
        $service = app(DatabaseBackupService::class);

        foreach (['../secret', 'backup/../x', 'abc!', 'a b'] as $id) {
            try {
                $service->findBackup($id);
                $this->fail('Invalid backup id was accepted: ' . $id);
            } catch (RuntimeException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_tampered_sql_fails_verification_and_is_not_listed(): void
    {
        $service = app(DatabaseBackupService::class);
        $id = 'abc123def456';
        $sql = "-- ZenFlow backup\nSELECT 1;\n";
        Storage::disk('local')->put('backups/backup_' . $id . '.sql', $sql);

        $manifest = [
            'format_version' => 1,
            'manifest_version' => 1,
            'backup_id' => $id,
            'created_at' => '2026-11-26T12:00:00+08:00',
            'source_database' => 'spa',
            'database_charset' => 'utf8mb4',
            'database_collation' => 'utf8mb4_0900_ai_ci',
            'included_tables' => DatabaseBackupPolicy::allTables(),
            'data_tables' => DatabaseBackupPolicy::DATA_TABLES,
            'structure_only_tables' => DatabaseBackupPolicy::STRUCTURE_ONLY_TABLES,
            'excluded_reasons' => DatabaseBackupPolicy::EXCLUSION_REASONS,
            'table_count' => 38,
            'row_counts' => [],
            'exported_row_count' => 0,
            'objects' => ['views' => [], 'triggers' => [], 'routines' => [], 'events' => []],
            'file_size' => strlen($sql),
            'sql_sha256' => hash('sha256', $sql),
            'verification_status' => 'verified',
        ];
        $canonical = $this->canonical($manifest);
        $hash = hash('sha256', $canonical);
        $key = config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true);
        }
        $manifest['manifest_hash'] = $hash;
        $manifest['hmac_sha256'] = hash_hmac(
            'sha256',
            'v1|' . $id . '|spa|' . $hash . '|' . $manifest['sql_sha256'],
            $key
        );
        Storage::disk('local')->put('backups/backup_' . $id . '.json', $this->canonical($manifest));

        $this->assertCount(1, $service->listBackups());

        Storage::disk('local')->put('backups/backup_' . $id . '.sql', $sql . '-- tampered');

        $this->assertCount(0, $service->listBackups());
        $this->expectException(RuntimeException::class);
        $service->findBackup($id);
    }

    public function test_private_audit_log_is_written_with_flock(): void
    {
        app(DatabaseBackupService::class)->writeAuditEvent([
            'event' => 'backup_creation_started',
            'backup_id' => 'audit123',
            'actor_id' => $this->admin->id,
        ]);

        $contents = Storage::disk('local')->get('backups/backup-audit.log');
        $this->assertStringContainsString('backup_creation_started', $contents);
        $this->assertStringContainsString('audit123', $contents);
    }

    public function test_final_status_manifest_is_verifiable_and_stale_hash_is_rejected(): void
    {
        $service = app(DatabaseBackupService::class);
        $id = 'signed123456';
        $sql = "-- ZenFlow backup\nSELECT 2;\n";
        Storage::disk('local')->put('backups/backup_' . $id . '.sql', $sql);

        $final = $this->baseManifest($id, $sql, 'verified');
        $canonical = $this->canonical($final);
        $hash = hash('sha256', $canonical);
        $final['manifest_hash'] = $hash;
        $final['hmac_sha256'] = $this->sign($hash, $final['sql_sha256'], $id);
        Storage::disk('local')->put('backups/backup_' . $id . '.json', $this->canonical($final));

        $this->assertSame('verified', $service->findBackup($id)['verification_status']);
        $this->assertCount(1, $service->listBackups());

        // Simulate the old defect: hash/HMAC were calculated while the status
        // was pending, then the status was changed without re-signing.
        $pending = $this->baseManifest($id, $sql, 'pending');
        $pending['manifest_hash'] = hash('sha256', $this->canonical($pending));
        $pending['hmac_sha256'] = $this->sign($pending['manifest_hash'], $pending['sql_sha256'], $id);
        $stale = $pending;
        $stale['verification_status'] = 'verified';
        Storage::disk('local')->put('backups/backup_' . $id . '.json', $this->canonical($stale));

        $this->expectException(RuntimeException::class);
        $service->findBackup($id);
    }

    public function test_concurrent_backup_is_rejected_with_clear_message(): void
    {
        $service = app(DatabaseBackupService::class);
        $acquire = new \ReflectionMethod(DatabaseBackupService::class, 'acquireBackupLock');
        $acquire->setAccessible(true);
        $release = new \ReflectionMethod(DatabaseBackupService::class, 'releaseBackupLock');
        $release->setAccessible(true);

        $lock = $acquire->invoke($service);
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Another database backup is already running');
            $service->createBackup();
        } finally {
            $release->invoke($service, $lock);
        }
    }

    public function test_orphaned_sql_is_cleaned_without_removing_valid_backup(): void
    {
        $service = app(DatabaseBackupService::class);
        Storage::disk('local')->put('backups/backup_orphan123.sql', 'SELECT 1;');

        $validId = 'validbackup123';
        $sql = "-- ZenFlow backup\nSELECT 3;\n";
        Storage::disk('local')->put('backups/backup_' . $validId . '.sql', $sql);
        $manifest = $this->baseManifest($validId, $sql, 'verified');
        $canonical = $this->canonical($manifest);
        $hash = hash('sha256', $canonical);
        $manifest['manifest_hash'] = $hash;
        $manifest['hmac_sha256'] = $this->sign($hash, $manifest['sql_sha256'], $validId);
        Storage::disk('local')->put('backups/backup_' . $validId . '.json', $this->canonical($manifest));

        $service->cleanupOrphanedBackups();

        $this->assertFalse(Storage::disk('local')->exists('backups/backup_orphan123.sql'));
        $this->assertTrue(Storage::disk('local')->exists('backups/backup_' . $validId . '.sql'));
        $this->assertTrue(Storage::disk('local')->exists('backups/backup_' . $validId . '.json'));
    }

    public function test_create_endpoint_uses_service_and_flashes_result(): void
    {
        $manifest = ['backup_id' => 'created123'];
        $mock = $this->mock(DatabaseBackupService::class);
        $mock->shouldReceive('createBackup')->once()->andReturn($manifest);

        $this->actingAs($this->admin)
            ->post(route('admin.database-backup.create'))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_integration_guard_rejects_missing_and_live_spa_targets_without_mysql(): void
    {
        foreach (['', '   ', 'spa', 'SPA', 'Spa'] as $rejected) {
            try {
                DatabaseBackupIntegrationTest::assertSafeDisposableDatabaseConfig($rejected, ':memory:');
                $this->fail('Expected guard to reject target: ' . $rejected);
            } catch (RuntimeException) {
                $this->assertTrue(true);
            }
        }

        // Valid disposable name passes even when phpunit.xml sets DB_DATABASE=:memory:
        DatabaseBackupIntegrationTest::assertSafeDisposableDatabaseConfig('spa_backup_test', ':memory:');
        $this->assertTrue(true);
    }

    public function test_integration_guard_verifies_selected_database_matches_configured_target(): void
    {
        DatabaseBackupIntegrationTest::assertSelectedDatabaseMatches('spa_backup_test', 'spa_backup_test');
        $this->assertTrue(true);

        foreach ([null, '', 'spa', 'SPA', 'other_database'] as $badSelected) {
            try {
                DatabaseBackupIntegrationTest::assertSelectedDatabaseMatches('spa_backup_test', $badSelected);
                $this->fail('Expected guard to reject selected database: ' . var_export($badSelected, true));
            } catch (RuntimeException) {
                $this->assertTrue(true);
            }
        }
    }

    private function baseManifest(string $id, string $sql, string $status): array
    {
        return [
            'format_version' => 1,
            'manifest_version' => 1,
            'backup_id' => $id,
            'created_at' => '2026-11-26T12:00:00+08:00',
            'source_database' => 'spa',
            'database_charset' => 'utf8mb4',
            'database_collation' => 'utf8mb4_0900_ai_ci',
            'included_tables' => DatabaseBackupPolicy::allTables(),
            'data_tables' => DatabaseBackupPolicy::DATA_TABLES,
            'structure_only_tables' => DatabaseBackupPolicy::STRUCTURE_ONLY_TABLES,
            'excluded_reasons' => DatabaseBackupPolicy::EXCLUSION_REASONS,
            'table_count' => 38,
            'row_counts' => [],
            'exported_row_count' => 0,
            'objects' => ['views' => [], 'triggers' => [], 'routines' => [], 'events' => []],
            'file_size' => strlen($sql),
            'sql_sha256' => hash('sha256', $sql),
            'verification_status' => $status,
        ];
    }

    private function sign(string $hash, string $sqlSha, string $id): string
    {
        $key = config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true);
        }

        return hash_hmac('sha256', 'v1|' . $id . '|spa|' . $hash . '|' . $sqlSha, $key);
    }

    private function canonical(mixed $value): string
    {
        if (is_array($value)) {
            if (!array_is_list($value)) {
                ksort($value);
            }
            $value = array_map(fn ($item) => $this->canonicalize($item), $value);
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function canonicalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        if (!array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn ($item) => $this->canonicalize($item), $value);
    }
}
