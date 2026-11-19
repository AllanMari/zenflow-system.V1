-- ============================================================
-- ZenFlow — Hostinger Focused Migration History Reconciliation (Migrations 53–61)
-- Target Database: u846442502_zenflow_db
-- Scope: ONLY the 9 NEW migrations (Migrations 53–61)
--
-- STRICT RULES:
-- 1. Does NOT rebuild or touch historical migrations 1–52.
-- 2. Checks pre-conditions before inserting.
-- 3. Idempotent insertion using WHERE NOT EXISTS.
-- 4. Verifies all 9 records after insertion.
-- ============================================================


-- ============================================================
-- PRE-CHECK 1: Inspect Current History State & Target Batch
-- ============================================================

-- Check total migrations count and maximum existing batch:
SELECT
    COUNT(*) AS total_migrations_recorded,
    COALESCE(MAX(`batch`), 0) AS max_existing_batch
FROM `migrations`;
-- CRITICAL STOP CONDITION:
-- If total_migrations_recorded = 0:
--   STOP! The `migrations` table is completely empty on Hostinger.
--   Inserting these 9 as Batch 2 while migrations 1–52 are absent from the table
--   means Laravel will still see migrations 1–52 as pending and will attempt to run
--   `create_users_table` on the next `php artisan migrate`, causing a crash.
--   Report this condition before continuing.
-- If total_migrations_recorded > 0 (e.g., historical migrations exist in Batch 1):
--   Target batch for these 9 new migrations should be (max_existing_batch + 1), typically Batch 2.


-- ============================================================
-- PRE-CHECK 2: Check Existing Status of the 9 New Migrations
-- ============================================================

-- 2.1 Check if any of the 9 migrations are already recorded:
SELECT `migration`, `batch`
FROM `migrations`
WHERE `migration` IN (
    '2026_11_17_000001_create_room_categories_and_pivot_tables',
    '2026_11_17_000002_add_participants_and_therapists_required_to_services_table',
    '2026_11_17_000003_create_appointment_staff_table',
    '2026_11_17_000004_add_treatment_notes_to_appointments_table',
    '2026_11_17_000005_update_services_room_category_id_foreign_key',
    '2026_11_18_125535_make_duration_minutes_nullable_on_services_table',
    '2026_11_18_125537_create_package_choice_groups_table',
    '2026_11_18_125542_create_package_choice_options_table',
    '2026_11_18_125543_create_appointment_package_selections_table'
);
-- If all 9 already exist: Do not re-insert. Skip to Post-Verification.
-- If a partial subset exists: Note which ones already exist.

-- 2.2 Check for duplicate names among these 9 in the table:
SELECT `migration`, COUNT(*) AS occurrences
FROM `migrations`
WHERE `migration` IN (
    '2026_11_17_000001_create_room_categories_and_pivot_tables',
    '2026_11_17_000002_add_participants_and_therapists_required_to_services_table',
    '2026_11_17_000003_create_appointment_staff_table',
    '2026_11_17_000004_add_treatment_notes_to_appointments_table',
    '2026_11_17_000005_update_services_room_category_id_foreign_key',
    '2026_11_18_125535_make_duration_minutes_nullable_on_services_table',
    '2026_11_18_125537_create_package_choice_groups_table',
    '2026_11_18_125542_create_package_choice_options_table',
    '2026_11_18_125543_create_appointment_package_selections_table'
)
GROUP BY `migration`
HAVING COUNT(*) > 1;
-- Expected: 0 rows.


-- ============================================================
-- INSERTION: Idempotent Insertion of Migrations 53–61 (Batch 2)
-- Run ONLY AFTER the schema changes in hostinger_zenflow_schema_update_reviewed.sql
-- have been executed and verified.
-- ============================================================

INSERT INTO `migrations` (`migration`, `batch`)
SELECT item.migration, item.batch
FROM (
    SELECT '2026_11_17_000001_create_room_categories_and_pivot_tables' AS migration, 2 AS batch UNION ALL
    SELECT '2026_11_17_000002_add_participants_and_therapists_required_to_services_table', 2 UNION ALL
    SELECT '2026_11_17_000003_create_appointment_staff_table', 2 UNION ALL
    SELECT '2026_11_17_000004_add_treatment_notes_to_appointments_table', 2 UNION ALL
    SELECT '2026_11_17_000005_update_services_room_category_id_foreign_key', 2 UNION ALL
    SELECT '2026_11_18_125535_make_duration_minutes_nullable_on_services_table', 2 UNION ALL
    SELECT '2026_11_18_125537_create_package_choice_groups_table', 2 UNION ALL
    SELECT '2026_11_18_125542_create_package_choice_options_table', 2 UNION ALL
    SELECT '2026_11_18_125543_create_appointment_package_selections_table', 2
) AS item
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` m WHERE m.`migration` = item.migration
);


-- ============================================================
-- POST-VERIFICATION: Verify Newly Inserted Records
-- ============================================================

-- 1. Confirm all 9 migrations exist with the expected batch:
SELECT `migration`, `batch`
FROM `migrations`
WHERE `migration` IN (
    '2026_11_17_000001_create_room_categories_and_pivot_tables',
    '2026_11_17_000002_add_participants_and_therapists_required_to_services_table',
    '2026_11_17_000003_create_appointment_staff_table',
    '2026_11_17_000004_add_treatment_notes_to_appointments_table',
    '2026_11_17_000005_update_services_room_category_id_foreign_key',
    '2026_11_18_125535_make_duration_minutes_nullable_on_services_table',
    '2026_11_18_125537_create_package_choice_groups_table',
    '2026_11_18_125542_create_package_choice_options_table',
    '2026_11_18_125543_create_appointment_package_selections_table'
)
ORDER BY `migration`;
-- Expected: Exactly 9 rows, all with batch = 2.

-- 2. Confirm total distinct count among these 9:
SELECT
    COUNT(*) AS total_new_records,
    COUNT(DISTINCT `migration`) AS distinct_new_records
FROM `migrations`
WHERE `migration` IN (
    '2026_11_17_000001_create_room_categories_and_pivot_tables',
    '2026_11_17_000002_add_participants_and_therapists_required_to_services_table',
    '2026_11_17_000003_create_appointment_staff_table',
    '2026_11_17_000004_add_treatment_notes_to_appointments_table',
    '2026_11_17_000005_update_services_room_category_id_foreign_key',
    '2026_11_18_125535_make_duration_minutes_nullable_on_services_table',
    '2026_11_18_125537_create_package_choice_groups_table',
    '2026_11_18_125542_create_package_choice_options_table',
    '2026_11_18_125543_create_appointment_package_selections_table'
);
-- Expected: total_new_records = 9, distinct_new_records = 9.

-- 3. Confirm absence of duplicates:
SELECT `migration`, COUNT(*) AS count
FROM `migrations`
WHERE `migration` IN (
    '2026_11_17_000001_create_room_categories_and_pivot_tables',
    '2026_11_17_000002_add_participants_and_therapists_required_to_services_table',
    '2026_11_17_000003_create_appointment_staff_table',
    '2026_11_17_000004_add_treatment_notes_to_appointments_table',
    '2026_11_17_000005_update_services_room_category_id_foreign_key',
    '2026_11_18_125535_make_duration_minutes_nullable_on_services_table',
    '2026_11_18_125537_create_package_choice_groups_table',
    '2026_11_18_125542_create_package_choice_options_table',
    '2026_11_18_125543_create_appointment_package_selections_table'
)
GROUP BY `migration`
HAVING COUNT(*) > 1;
-- Expected: 0 rows.
