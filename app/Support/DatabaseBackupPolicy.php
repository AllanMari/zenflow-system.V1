<?php

namespace App\Support;

/**
 * Explicit allowlist for Phase 1 database backups.
 *
 * No table is included by convention: every table must be listed here. The
 * policy is intentionally a code constant so a future schema change requires a
 * reviewed policy revision instead of silently producing an incomplete backup.
 */
class DatabaseBackupPolicy
{
    public const FORMAT_VERSION = 1;

    public const MANIFEST_VERSION = 1;

    /** Tables whose structure AND records are included. */
    public const DATA_TABLES = [
        'activity_logs',
        'ai_insights',
        'appointment_package_selections',
        'appointment_preferred_staff',
        'appointment_services',
        'appointment_staff',
        'appointments',
        'attendance_logs',
        'attendances',
        'business_exceptions',
        'business_hours',
        'customers',
        'landing_settings',
        'login_attempts',
        'notifications',
        'package_choice_groups',
        'package_choice_options',
        'payments',
        'pdf_report_defaults',
        'role_user',
        'roles',
        'room_categories',
        'room_category_room',
        'rooms',
        'schedule_exceptions',
        'service_categories',
        'services',
        'shift_templates',
        'users',
        'work_schedules',
        'migrations',
    ];

    /** Tables whose structure is included but whose records are excluded. */
    public const STRUCTURE_ONLY_TABLES = [
        'cache',
        'cache_locks',
        'sessions',
        'jobs',
        'failed_jobs',
        'password_reset_tokens',
        'job_batches',
    ];

    /** Why each structure-only table has no rows in the backup. */
    public const EXCLUSION_REASONS = [
        'cache' => 'Transient Laravel cache entries; regenerated after restore.',
        'cache_locks' => 'Stale scheduler/cache locks; must not be restored.',
        'sessions' => 'Old authentication sessions; must not be restored.',
        'jobs' => 'No active queue workload; queued records are transient.',
        'failed_jobs' => 'Queue failure records are transient and currently unused.',
        'password_reset_tokens' => 'No password-reset feature is enabled.',
        'job_batches' => 'Job batching is not used by ZenFlow.',
    ];

    /** Non-table objects the current policy accounts for (none today). */
    public const VIEWS = [];

    public const TRIGGERS = [];

    public const ROUTINES = [];

    public const EVENTS = [];

    public static function allTables(): array
    {
        return array_values(array_merge(self::DATA_TABLES, self::STRUCTURE_ONLY_TABLES));
    }

    public static function includesData(string $table): bool
    {
        return in_array($table, self::DATA_TABLES, true);
    }

    /**
     * Normalize SHOW CREATE TABLE output for comparison.
     *
     * AUTO_INCREMENT changes as rows are written and is intentionally ignored
     * for drift comparison; the actual value is recorded separately in the
     * manifest.
     */
    public static function normalizeDdl(string $ddl): string
    {
        $normalized = str_replace("\r\n", "\n", $ddl);
        $normalized = preg_replace('/\s+AUTO_INCREMENT=\d+/i', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+CHARACTER\s+SET\s+[A-Za-z0-9_]+(?=\s+COLLATE\s+)/i', '', $normalized) ?? $normalized;

        return trim($normalized);
    }

    /**
     * Refuse to back up a schema the policy does not fully describe.
     *
     * @param  array<int,array<string,mixed>>  $tables  [['TABLE_NAME'=>..., 'ENGINE'=>...], ...]
     * @param  array<string,array<int,string>>  $objects  views/triggers/routines/events
     *
     * @return array{ok:bool, unexpected_tables:array<int,string>, missing_tables:array<int,string>, unexpected_objects:array<int,string>, non_innodb:array<int,string>}
     */
    public static function checkSchema(array $tables, array $objects): array
    {
        $actual = array_values(array_map(
            fn ($table) => (string) ($table['TABLE_NAME'] ?? ''),
            $tables
        ));
        $expected = self::allTables();

        $unexpectedTables = array_values(array_diff($actual, $expected));
        $missingTables = array_values(array_diff($expected, $actual));

        $unexpectedObjects = [];
        foreach (['views' => self::VIEWS, 'triggers' => self::TRIGGERS, 'routines' => self::ROUTINES, 'events' => self::EVENTS] as $kind => $allowed) {
            foreach ($objects[$kind] ?? [] as $name) {
                if (!in_array($name, $allowed, true)) {
                    $unexpectedObjects[] = $kind . ':' . $name;
                }
            }
        }

        $nonInnodb = [];
        foreach ($tables as $table) {
            if (($table['ENGINE'] ?? '') !== 'InnoDB') {
                $nonInnodb[] = (string) ($table['TABLE_NAME'] ?? '');
            }
        }

        return [
            'ok' => empty($unexpectedTables) && empty($missingTables) && empty($unexpectedObjects) && empty($nonInnodb),
            'unexpected_tables' => $unexpectedTables,
            'missing_tables' => $missingTables,
            'unexpected_objects' => $unexpectedObjects,
            'non_innodb' => $nonInnodb,
        ];
    }
}
