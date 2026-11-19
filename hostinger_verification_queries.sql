-- ============================================================
-- ZenFlow — Hostinger Focused Verification Queries (Migrations 53–61)
-- Target Database: u846442502_zenflow_db
-- Scope: ONLY the 6 NEW tables, 2 MODIFIED tables, and relationships to rooms/users/services.
--
-- ALL QUERIES ARE STRICTLY READ-ONLY.
-- ============================================================


-- ============================================================
-- SECTION 1: Verification of the 6 New Tables
-- ============================================================

-- 1.1 Verify that all 6 new tables exist:
SELECT TABLE_NAME, ENGINE, TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'u846442502_zenflow_db'
  AND TABLE_NAME IN (
    'room_categories',
    'room_category_room',
    'appointment_staff',
    'package_choice_groups',
    'package_choice_options',
    'appointment_package_selections'
  )
ORDER BY TABLE_NAME;
-- Expected: Exactly 6 rows returned.

-- 1.2 Verify column definitions across all 6 new tables:
SELECT
    TABLE_NAME,
    COLUMN_NAME,
    ORDINAL_POSITION,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'u846442502_zenflow_db'
  AND TABLE_NAME IN (
    'room_categories',
    'room_category_room',
    'appointment_staff',
    'package_choice_groups',
    'package_choice_options',
    'appointment_package_selections'
  )
ORDER BY TABLE_NAME, ORDINAL_POSITION;
-- Expected key definitions:
--   room_categories:
--     id: bigint unsigned NOT NULL auto_increment
--     name: varchar(100) NOT NULL
--     code: varchar(50) NOT NULL
--     description: text NULL DEFAULT NULL
--     is_active: tinyint(1) NOT NULL DEFAULT 1
--     created_at: timestamp NULL DEFAULT NULL
--     updated_at: timestamp NULL DEFAULT NULL
--   room_category_room:
--     id: bigint unsigned NOT NULL auto_increment
--     room_id: bigint unsigned NOT NULL
--     room_category_id: bigint unsigned NOT NULL
--     created_at: timestamp NULL DEFAULT NULL
--     updated_at: timestamp NULL DEFAULT NULL
--   appointment_staff:
--     id: bigint unsigned NOT NULL auto_increment
--     appointment_id: bigint unsigned NOT NULL
--     user_id: bigint unsigned NOT NULL
--     role: enum('primary','assisting') NOT NULL DEFAULT 'primary'
--     created_at: timestamp NULL DEFAULT NULL
--     updated_at: timestamp NULL DEFAULT NULL
--   package_choice_groups:
--     id: bigint unsigned NOT NULL auto_increment
--     service_id: bigint unsigned NOT NULL
--     label: varchar(255) NOT NULL
--     is_required: tinyint NOT NULL DEFAULT 1
--     max_selections: tinyint NOT NULL DEFAULT 1
--     created_at: timestamp NULL DEFAULT NULL
--     updated_at: timestamp NULL DEFAULT NULL
--   package_choice_options:
--     id: bigint unsigned NOT NULL auto_increment
--     choice_group_id: bigint unsigned NOT NULL
--     service_id: bigint unsigned NOT NULL
--     sort_order: tinyint NOT NULL DEFAULT 0
--     created_at: timestamp NULL DEFAULT NULL
--     updated_at: timestamp NULL DEFAULT NULL
--   appointment_package_selections:
--     id: bigint unsigned NOT NULL auto_increment
--     appointment_id: bigint unsigned NOT NULL
--     choice_group_id: bigint unsigned NULL DEFAULT NULL
--     selected_service_id: bigint unsigned NULL DEFAULT NULL
--     group_label_snapshot: varchar(255) NOT NULL
--     selected_service_name_snapshot: varchar(255) NOT NULL
--     created_at: timestamp NULL DEFAULT NULL
--     updated_at: timestamp NULL DEFAULT NULL

-- 1.3 Verify Primary Keys on all 6 new tables:
SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'u846442502_zenflow_db'
  AND CONSTRAINT_NAME = 'PRIMARY'
  AND TABLE_NAME IN (
    'room_categories',
    'room_category_room',
    'appointment_staff',
    'package_choice_groups',
    'package_choice_options',
    'appointment_package_selections'
  )
ORDER BY TABLE_NAME;
-- Expected: Each of the 6 tables has column `id` as PRIMARY.

-- 1.4 Verify Unique & Ordinary Indexes on the 6 new tables:
SELECT
    TABLE_NAME,
    INDEX_NAME,
    NON_UNIQUE,
    COLUMN_NAME,
    SEQ_IN_INDEX
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = 'u846442502_zenflow_db'
  AND TABLE_NAME IN (
    'room_categories',
    'room_category_room',
    'appointment_staff',
    'package_choice_groups',
    'package_choice_options',
    'appointment_package_selections'
  )
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;
-- Expected:
--   room_categories:
--     room_categories_code_unique: NON_UNIQUE=0 (code)
--   room_category_room:
--     uq_room_category_room: NON_UNIQUE=0 (room_id, room_category_id)
--   appointment_staff:
--     uq_appointment_staff_user: NON_UNIQUE=0 (appointment_id, user_id)
--   package_choice_options:
--     package_choice_options_choice_group_id_service_id_unique: NON_UNIQUE=0 (choice_group_id, service_id)

-- 1.5 Verify Foreign Keys and ON DELETE rules across the new tables:
SELECT
    kcu.TABLE_NAME,
    kcu.CONSTRAINT_NAME,
    kcu.COLUMN_NAME,
    kcu.REFERENCED_TABLE_NAME,
    kcu.REFERENCED_COLUMN_NAME,
    rc.DELETE_RULE
FROM information_schema.KEY_COLUMN_USAGE kcu
JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
  ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
 AND rc.CONSTRAINT_SCHEMA = kcu.TABLE_SCHEMA
WHERE kcu.TABLE_SCHEMA = 'u846442502_zenflow_db'
  AND kcu.TABLE_NAME IN (
    'room_category_room',
    'appointment_staff',
    'package_choice_groups',
    'package_choice_options',
    'appointment_package_selections'
  )
  AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY kcu.TABLE_NAME, kcu.COLUMN_NAME;
-- Expected 10 Foreign Key rows:
--   room_category_room.room_id                      -> rooms(id)                    [CASCADE]
--   room_category_room.room_category_id             -> room_categories(id)          [CASCADE]
--   appointment_staff.appointment_id                -> appointments(id)             [CASCADE]
--   appointment_staff.user_id                       -> users(id)                    [CASCADE]
--   package_choice_groups.service_id                -> services(id)                 [CASCADE]
--   package_choice_options.choice_group_id          -> package_choice_groups(id)    [CASCADE]
--   package_choice_options.service_id               -> services(id)                 [CASCADE]
--   appointment_package_selections.appointment_id   -> appointments(id)             [CASCADE]
--   appointment_package_selections.choice_group_id  -> package_choice_groups(id)    [SET NULL]
--   appointment_package_selections.selected_service_id -> services(id)              [SET NULL]


-- ============================================================
-- SECTION 2: Verification of Modified Existing Tables (services & appointments)
-- ============================================================

-- 2.1 Verify participants_required and therapists_required on services:
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'u846442502_zenflow_db'
  AND TABLE_NAME = 'services'
  AND COLUMN_NAME IN ('participants_required', 'therapists_required')
ORDER BY COLUMN_NAME;
-- Expected:
--   participants_required: int unsigned, IS_NULLABLE = NO, COLUMN_DEFAULT = 1
--   therapists_required:   int unsigned, IS_NULLABLE = NO, COLUMN_DEFAULT = 1

-- 2.2 Verify duration_minutes allows NULL on services:
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'u846442502_zenflow_db'
  AND TABLE_NAME = 'services'
  AND COLUMN_NAME = 'duration_minutes';
-- Expected:
--   duration_minutes: int, IS_NULLABLE = YES, COLUMN_DEFAULT = NULL

-- 2.3 Verify services.room_category_id references room_categories with SET NULL:
SELECT
    kcu.CONSTRAINT_NAME,
    kcu.COLUMN_NAME,
    kcu.REFERENCED_TABLE_NAME,
    kcu.REFERENCED_COLUMN_NAME,
    rc.DELETE_RULE
FROM information_schema.KEY_COLUMN_USAGE kcu
JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
  ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
 AND rc.CONSTRAINT_SCHEMA = kcu.TABLE_SCHEMA
WHERE kcu.TABLE_SCHEMA = 'u846442502_zenflow_db'
  AND kcu.TABLE_NAME = 'services'
  AND kcu.COLUMN_NAME = 'room_category_id'
  AND kcu.REFERENCED_TABLE_NAME IS NOT NULL;
-- Expected:
--   REFERENCED_TABLE_NAME = 'room_categories'
--   REFERENCED_COLUMN_NAME = 'id'
--   DELETE_RULE = 'SET NULL'

-- 2.4 Verify treatment_notes on appointments:
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'u846442502_zenflow_db'
  AND TABLE_NAME = 'appointments'
  AND COLUMN_NAME = 'treatment_notes';
-- Expected:
--   treatment_notes: text, IS_NULLABLE = YES, COLUMN_DEFAULT = NULL


-- ============================================================
-- SECTION 3: Backfill Completeness & Data Preservation Checks
-- ============================================================

-- 3.1 Verify appointment_staff backfill completeness:
-- Checks that EVERY appointment having a non-NULL user_id has a corresponding 'primary' entry in appointment_staff.
SELECT a.id AS unbackfilled_appointment_id, a.user_id
FROM `appointments` a
LEFT JOIN `appointment_staff` s
  ON s.appointment_id = a.id AND s.user_id = a.user_id AND s.role = 'primary'
WHERE a.user_id IS NOT NULL
  AND s.id IS NULL;
-- Expected: 0 rows (backfill is complete).

-- 3.2 Verify existing service records are preserved:
SELECT
    COUNT(*) AS total_services,
    COUNT(CASE WHEN room_category_id IS NOT NULL THEN 1 END) AS services_with_room_category
FROM `services`;
-- Expected: total_services = 18 (preserves all existing service records).
