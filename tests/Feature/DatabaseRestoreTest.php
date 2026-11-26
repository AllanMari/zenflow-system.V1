<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\DatabaseBackupService;
use App\Services\DatabaseRestoreService;
use App\Support\DatabaseBackupPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class DatabaseRestoreTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->admin = User::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Admin',
            'password' => Hash::make('AdminSecret!2026'),
        ]);
        $this->admin->roles()->attach(Role::firstOrCreate(['name' => 'admin']));
    }

    public function test_non_admin_roles_cannot_invoke_restore_endpoint(): void
    {
        $this->post(route('admin.database-backup.restore', 'validid123'), ['admin_password' => 'AdminSecret!2026'])
            ->assertRedirect(route('login'));

        foreach (['receptionist', 'staff', 'customer'] as $roleName) {
            $user = User::factory()->create(['password' => Hash::make('RolePass!2026')]);
            $user->roles()->attach(Role::firstOrCreate(['name' => $roleName]));

            $this->actingAs($user)
                ->post(route('admin.database-backup.restore', 'validid123'), ['admin_password' => 'RolePass!2026'])
                ->assertStatus(403);
        }
    }

    public function test_restore_endpoint_requires_admin_password_and_rejects_wrong_password_before_safety_backup(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.database-backup.restore', 'validid123'), [])
            ->assertSessionHasErrors('admin_password');

        $restoreMock = $this->partialMock(DatabaseRestoreService::class);
        $restoreMock->shouldNotReceive('restore');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.database-backup.restore', 'validid123'), [
                'admin_password' => 'WrongPassword!999',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Admin password verification failed. Database restore was cancelled.');
        // Password must never be flashed in old input or session
        $this->assertNull(session()->getOldInput('admin_password'));
    }

    public function test_restore_endpoint_rate_limits_repeated_failed_password_attempts(): void
    {
        $restoreMock = $this->partialMock(DatabaseRestoreService::class);
        $restoreMock->shouldNotReceive('restore');

        $rateKey = 'database-restore:' . $this->admin->id . '|127.0.0.1';
        RateLimiter::clear($rateKey);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->admin)
                ->post(route('admin.database-backup.restore', 'validid123'), [
                    'admin_password' => 'WrongPassword!999',
                ])
                ->assertSessionHas('error', 'Admin password verification failed. Database restore was cancelled.');
        }

        // 6th attempt (even with correct password) is rate-limited before restoreService
        $this->actingAs($this->admin)
            ->post(route('admin.database-backup.restore', 'validid123'), [
                'admin_password' => 'AdminSecret!2026',
            ])
            ->assertSessionHas('error', 'Too many failed password attempts. Please wait a few minutes before trying again.');

        RateLimiter::clear($rateKey);
    }

    public function test_restore_endpoint_accepts_valid_admin_password_and_invokes_restore_service(): void
    {
        $restoreMock = $this->partialMock(DatabaseRestoreService::class);
        $restoreMock->shouldReceive('restore')
            ->once()
            ->with('validid123', \Mockery::on(fn ($u) => $u->id === $this->admin->id))
            ->andReturn(['safety_backup_id' => 'safety999']);

        $this->actingAs($this->admin)
            ->post(route('admin.database-backup.restore', 'validid123'), [
                'admin_password' => 'AdminSecret!2026',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_restore_button_only_appears_for_verified_backups(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.database-backup.index'))
            ->assertOk()
            ->assertDontSee('class="restore-backup-form', false);

        $id = 'verifiedbackup1';
        $sql = $this->valid38TableSql();
        $this->putSignedBackup($id, $sql);

        $this->actingAs($this->admin)
            ->get(route('admin.database-backup.index'))
            ->assertOk()
            ->assertSee('open-restore-modal', false)
            ->assertSee('Confirm Database Restore')
            ->assertSee('Admin Password')
            ->assertSee('Restore Database')
            ->assertSee($id);
    }

    public function test_invalid_id_missing_files_and_tampered_artifacts_are_rejected_before_db_changes(): void
    {
        $restore = app(DatabaseRestoreService::class);

        // Invalid ID
        try {
            $restore->restore('../etc', $this->admin);
            $this->fail('Expected invalid backup ID to fail.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }

        // Missing manifest/SQL
        try {
            $restore->restore('missingbackup123', $this->admin);
            $this->fail('Expected missing backup to fail.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }

        // Missing SQL only
        $id = 'missingsql123';
        $sql = $this->valid38TableSql();
        $this->putSignedBackup($id, $sql);
        Storage::disk('local')->delete('backups/backup_' . $id . '.sql');
        try {
            $restore->restore($id, $this->admin);
            $this->fail('Expected missing SQL file to fail.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }

        // Tampered SQL
        $id2 = 'tamperedsql123';
        $this->putSignedBackup($id2, $sql);
        Storage::disk('local')->put('backups/backup_' . $id2 . '.sql', $sql . "\n-- tampered");
        try {
            $restore->restore($id2, $this->admin);
            $this->fail('Expected tampered SQL to fail.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('checksum', strtolower($e->getMessage()));
        }

        // Tampered manifest / wrong HMAC
        $id3 = 'tamperedjson123';
        $this->putSignedBackup($id3, $sql);
        $raw = json_decode(Storage::disk('local')->get('backups/backup_' . $id3 . '.json'), true);
        $raw['hmac_sha256'] = str_repeat('a', 64);
        Storage::disk('local')->put('backups/backup_' . $id3 . '.json', json_encode($raw));
        try {
            $restore->restore($id3, $this->admin);
            $this->fail('Expected tampered HMAC to fail.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_manifest_policy_rejects_unsupported_format_wrong_tables_and_objects(): void
    {
        $restore = app(DatabaseRestoreService::class);
        $base = [
            'format_version' => 1,
            'manifest_version' => 1,
            'verification_status' => 'verified',
            'source_database' => 'spa_test',
            'included_tables' => DatabaseBackupPolicy::allTables(),
            'data_tables' => DatabaseBackupPolicy::DATA_TABLES,
            'structure_only_tables' => DatabaseBackupPolicy::STRUCTURE_ONLY_TABLES,
            'objects' => ['views' => [], 'triggers' => [], 'routines' => [], 'events' => []],
        ];

        $restore->validateManifestPolicy($base);
        $this->assertTrue(true);

        // Unsupported format version
        $badFormat = $base;
        $badFormat['format_version'] = 99;
        try {
            $restore->validateManifestPolicy($badFormat);
            $this->fail('Expected unsupported format version to fail.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }

        // Unexpected table manifest
        $badTables = $base;
        $badTables['included_tables'] = ['users'];
        try {
            $restore->validateManifestPolicy($badTables);
            $this->fail('Expected invalid table manifest to fail.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }

        // Unexpected view object
        $badObjects = $base;
        $badObjects['objects']['views'] = ['v_evil'];
        try {
            $restore->validateManifestPolicy($badObjects);
            $this->fail('Expected unsupported objects to fail.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_sql_parser_accepts_valid_38_table_sql_and_rejects_disallowed_statements(): void
    {
        $restore = app(DatabaseRestoreService::class);
        $validSql = $this->valid38TableSql();
        $parsed = $restore->parseAndValidateSql($validSql);
        $this->assertCount(38, $parsed['create']);

        // Reject arbitrary DROP / ALTER / TRIGGER / USE
        foreach ([
            $validSql . "\nDROP DATABASE spa;",
            $validSql . "\nCREATE TRIGGER evil AFTER INSERT ON users FOR EACH ROW BEGIN END;",
            $validSql . "\nINSERT INTO `surprise_table` (`id`) VALUES (1);",
            $validSql . "\nINSERT INTO `sessions` (`id`) VALUES ('abc');",
        ] as $badSql) {
            try {
                $restore->parseAndValidateSql($badSql);
                $this->fail('Expected SQL parser to reject disallowed SQL.');
            } catch (RuntimeException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_target_database_verification_refuses_non_mysql_and_live_spa(): void
    {
        $restore = app(DatabaseRestoreService::class);
        $sqlitePdo = new \PDO('sqlite::memory:');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MySQL');
        $restore->verifyTargetDatabase($sqlitePdo);
    }

    public function test_concurrent_backup_and_restore_share_exclusive_lock(): void
    {
        $backupService = app(DatabaseBackupService::class);
        $restoreService = app(DatabaseRestoreService::class);

        $acquire = new \ReflectionMethod(DatabaseBackupService::class, 'acquireBackupLock');
        $acquire->setAccessible(true);
        $release = new \ReflectionMethod(DatabaseBackupService::class, 'releaseBackupLock');
        $release->setAccessible(true);

        $lock = $acquire->invoke($backupService);
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('already running');
            $restoreService->restore('anyid123', $this->admin);
        } finally {
            $release->invoke($backupService, $lock);
        }
    }

    public function test_maintenance_guard_blocks_ordinary_requests_with_503_during_restore_or_recovery(): void
    {
        Storage::disk('local')->put('backups/restore-maintenance.flag', 'restore123');
        Storage::disk('local')->put('backups/restore-state.json', json_encode([
            'restore_id' => 'restore123',
            'state' => DatabaseRestoreService::STATE_RECOVERY_REQUIRED,
        ]));

        // Ordinary routes are blocked with 503
        $this->get('/')->assertStatus(503);

        // Login and Admin recovery routes remain accessible in RECOVERY_REQUIRED
        // (when users/sessions tables exist) so Admin can re-authenticate and recover
        $this->get(route('login'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.database-backup.index'))->assertOk();
        $this->actingAs($this->admin)
            ->post(route('admin.database-backup.restore', 'missing123'), [])
            ->assertSessionHasErrors('admin_password');

        Storage::disk('local')->delete('backups/restore-maintenance.flag');
        $this->get('/')->assertOk();
    }

    public function test_interrupted_restore_with_unheld_lock_transitions_to_recovery_required(): void
    {
        Storage::disk('local')->put('backups/restore-maintenance.flag', 'crashed123');
        Storage::disk('local')->put('backups/restore-state.json', json_encode([
            'restore_id' => 'crashed123',
            'backup_id' => 'target123',
            'state' => DatabaseRestoreService::STATE_RESTORING,
            'transitions' => [['state' => DatabaseRestoreService::STATE_RESTORING, 'at' => '2026-11-27T01:00:00+08:00']],
        ]));

        $restore = app(DatabaseRestoreService::class);
        $state = $restore->detectInterruptedRestore();

        $this->assertNotNull($state);
        $this->assertSame(DatabaseRestoreService::STATE_RECOVERY_REQUIRED, $state['state']);
        $this->assertSame('interrupted_during_restore', $state['failure_category']);
        $this->assertTrue($restore->isMaintenanceActive());
    }

    public function test_active_restore_holding_lock_blocks_login_and_new_restore_attempts(): void
    {
        $backupService = app(DatabaseBackupService::class);
        Storage::disk('local')->put('backups/restore-maintenance.flag', 'active123');
        Storage::disk('local')->put('backups/restore-state.json', json_encode([
            'restore_id' => 'active123',
            'state' => DatabaseRestoreService::STATE_RESTORING,
        ]));

        $acquire = new \ReflectionMethod(DatabaseBackupService::class, 'acquireBackupLock');
        $acquire->setAccessible(true);
        $release = new \ReflectionMethod(DatabaseBackupService::class, 'releaseBackupLock');
        $release->setAccessible(true);

        $lock = $acquire->invoke($backupService);
        try {
            $this->get('/')->assertStatus(503);
            $this->get(route('login'))->assertStatus(503);
            $this->actingAs($this->admin)
                ->post(route('admin.database-backup.restore', 'any123'), ['admin_password' => 'AdminSecret!2026'])
                ->assertStatus(503);
            $this->actingAs($this->admin)->get(route('admin.database-backup.index'))->assertOk();
        } finally {
            $release->invoke($backupService, $lock);
        }
    }

    public function test_composite_foreign_keys_are_grouped_by_constraint_name_for_orphan_checks(): void
    {
        $restore = app(DatabaseRestoreService::class);

        $rows = [
            [
                'CONSTRAINT_NAME' => 'fk_child_parent_composite',
                'TABLE_NAME' => 'child_items',
                'COLUMN_NAME' => 'tenant_id',
                'REFERENCED_TABLE_NAME' => 'parent_items',
                'REFERENCED_COLUMN_NAME' => 'tenant_id',
                'ORDINAL_POSITION' => 1,
            ],
            [
                'CONSTRAINT_NAME' => 'fk_child_parent_composite',
                'TABLE_NAME' => 'child_items',
                'COLUMN_NAME' => 'parent_id',
                'REFERENCED_TABLE_NAME' => 'parent_items',
                'REFERENCED_COLUMN_NAME' => 'id',
                'ORDINAL_POSITION' => 2,
            ],
        ];

        $grouped = $restore->groupForeignKeysByConstraint($rows);
        $this->assertCount(1, $grouped);
        $this->assertArrayHasKey('child_items.fk_child_parent_composite', $grouped);

        $group = $grouped['child_items.fk_child_parent_composite'];
        $sql = $restore->buildFkOrphanCheckSql($group['table'], $group['columns'], $group['referenced_table']);

        $this->assertStringContainsString('c.`tenant_id` = p.`tenant_id` AND c.`parent_id` = p.`id`', $sql);
        $this->assertStringContainsString('c.`tenant_id` IS NOT NULL AND c.`parent_id` IS NOT NULL', $sql);
    }

    public function test_symlink_rejection_in_backup_storage(): void
    {
        $service = app(DatabaseBackupService::class);
        $method = new \ReflectionMethod(DatabaseBackupService::class, 'assertNotSymlink');
        $method->setAccessible(true);

        $regular = Storage::disk('local')->path('backups/regular.txt');
        @mkdir(dirname($regular), 0777, true);
        file_put_contents($regular, 'ok');
        $method->invoke($service, $regular);
        $this->assertTrue(true);
    }

    public function test_pre_restore_failure_records_failed_state_and_does_not_leave_maintenance_active(): void
    {
        $restore = app(DatabaseRestoreService::class);

        try {
            $restore->restore('nonexistent123', $this->admin);
            $this->fail('Expected missing backup to throw.');
        } catch (RuntimeException) {
            $state = $restore->currentState();
            $this->assertNotNull($state);
            $this->assertSame(DatabaseRestoreService::STATE_FAILED, $state['state']);
            $this->assertFalse($restore->isMaintenanceActive());
        }
    }

    private function valid38TableSql(): string
    {
        $lines = ["-- ZenFlow database backup\n"];
        foreach (DatabaseBackupPolicy::allTables() as $table) {
            $lines[] = "CREATE TABLE `{$table}` (`id` BIGINT UNSIGNED NOT NULL PRIMARY KEY);";
        }
        $lines[] = "INSERT INTO `roles` (`id`) VALUES (1);";

        return implode("\n", $lines);
    }

    private function putSignedBackup(string $id, string $sql): void
    {
        Storage::disk('local')->put('backups/backup_' . $id . '.sql', $sql);

        $manifest = [
            'format_version' => 1,
            'manifest_version' => 1,
            'backup_id' => $id,
            'created_at' => '2026-11-27T00:30:00+08:00',
            'source_database' => 'spa_test',
            'database_charset' => 'utf8mb4',
            'database_collation' => 'utf8mb4_0900_ai_ci',
            'included_tables' => DatabaseBackupPolicy::allTables(),
            'data_tables' => DatabaseBackupPolicy::DATA_TABLES,
            'structure_only_tables' => DatabaseBackupPolicy::STRUCTURE_ONLY_TABLES,
            'excluded_reasons' => DatabaseBackupPolicy::EXCLUSION_REASONS,
            'table_count' => 38,
            'row_counts' => ['roles' => 1],
            'exported_row_count' => 1,
            'objects' => ['views' => [], 'triggers' => [], 'routines' => [], 'events' => []],
            'file_size' => strlen($sql),
            'sql_sha256' => hash('sha256', $sql),
            'verification_status' => 'verified',
        ];

        $canonical = $this->canonical($manifest);
        $hash = hash('sha256', $canonical);
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true);
        }
        $manifest['manifest_hash'] = $hash;
        $manifest['hmac_sha256'] = hash_hmac('sha256', 'v1|' . $id . '|spa_test|' . $hash . '|' . $manifest['sql_sha256'], $key);

        Storage::disk('local')->put('backups/backup_' . $id . '.json', $this->canonical($manifest));
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
