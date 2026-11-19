-- ============================================================
-- ZenFlow — Hostinger Focused Schema Update (Migrations 53–61)
-- Target Database: u846442502_zenflow_db
-- Scope: ONLY the 6 NEW tables and 2 MODIFIED existing tables
--
-- STRICT PRESERVATION GUARANTEES:
-- 1. Zero data modification on existing production rows (users, customers, services).
-- 2. Non-destructive: no DROP TABLE, TRUNCATE, or column drops.
-- 3. Idempotent: safe conditional additions and missing-record-only backfill.
-- 4. No unnecessary disabling of FOREIGN_KEY_CHECKS.
-- ============================================================


-- ============================================================
-- STEP 1: Create room_categories and room_category_room (Migration 53)
-- Source: 2026_11_17_000001_create_room_categories_and_pivot_tables.php
-- Referenced tables: rooms (existing)
-- ============================================================

CREATE TABLE IF NOT EXISTS `room_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_categories_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `room_category_room` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `room_id` bigint unsigned NOT NULL,
  `room_category_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_room_category_room` (`room_id`, `room_category_id`),
  KEY `room_category_room_room_id_foreign` (`room_id`),
  KEY `room_category_room_room_category_id_foreign` (`room_category_id`),
  CONSTRAINT `room_category_room_room_id_foreign`
    FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `room_category_room_room_category_id_foreign`
    FOREIGN KEY (`room_category_id`) REFERENCES `room_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- STEP 2: Add participants_required & therapists_required to services (Migration 54)
-- Source: 2026_11_17_000002_add_participants_and_therapists_required_to_services_table.php
-- ============================================================

-- Pre-flight check: Run this query first to see which columns currently exist.
-- SELECT COLUMN_NAME FROM information_schema.COLUMNS
-- WHERE TABLE_SCHEMA = 'u846442502_zenflow_db' AND TABLE_NAME = 'services'
--   AND COLUMN_NAME IN ('participants_required', 'therapists_required');

-- If NEITHER exists (0 rows returned from pre-flight check):
ALTER TABLE `services`
  ADD COLUMN `participants_required` int unsigned NOT NULL DEFAULT 1 AFTER `included_services`,
  ADD COLUMN `therapists_required` int unsigned NOT NULL DEFAULT 1 AFTER `participants_required`;

-- If ONLY participants_required is missing:
-- ALTER TABLE `services`
--   ADD COLUMN `participants_required` int unsigned NOT NULL DEFAULT 1 AFTER `included_services`;

-- If ONLY therapists_required is missing:
-- ALTER TABLE `services`
--   ADD COLUMN `therapists_required` int unsigned NOT NULL DEFAULT 1 AFTER `participants_required`;


-- ============================================================
-- STEP 3: Create appointment_staff and Backfill Primary Therapists (Migration 55)
-- Source: 2026_11_17_000003_create_appointment_staff_table.php
-- Referenced tables: appointments (existing), users (existing)
-- ============================================================

CREATE TABLE IF NOT EXISTS `appointment_staff` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `appointment_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` enum('primary','assisting') NOT NULL DEFAULT 'primary',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_appointment_staff_user` (`appointment_id`, `user_id`),
  KEY `appointment_staff_user_id_foreign` (`user_id`),
  CONSTRAINT `appointment_staff_appointment_id_foreign`
    FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_staff_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Idempotent missing-records-only backfill:
-- Copies user_id from appointments into appointment_staff as 'primary'
-- ONLY if no assignment already exists for that appointment and user.
INSERT INTO `appointment_staff` (`appointment_id`, `user_id`, `role`, `created_at`, `updated_at`)
SELECT a.`id`, a.`user_id`, 'primary', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM `appointments` a
WHERE a.`user_id` IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `appointment_staff` s
    WHERE s.`appointment_id` = a.`id` AND s.`user_id` = a.`user_id`
  );


-- ============================================================
-- STEP 4: Add treatment_notes to appointments (Migration 56)
-- Source: 2026_11_17_000004_add_treatment_notes_to_appointments_table.php
-- ============================================================

-- Pre-flight check:
-- SELECT COLUMN_NAME FROM information_schema.COLUMNS
-- WHERE TABLE_SCHEMA = 'u846442502_zenflow_db' AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'treatment_notes';

-- If 0 rows returned from pre-flight check, execute:
ALTER TABLE `appointments`
  ADD COLUMN `treatment_notes` text DEFAULT NULL AFTER `notes`;


-- ============================================================
-- STEP 5: Update services.room_category_id Foreign Key (Migration 57)
-- Source: 2026_11_17_000005_update_services_room_category_id_foreign_key.php
--
-- SAFETY PRE-CHECK (Mandatory):
-- Confirm that all existing services have NULL room_category_id.
-- Run this check query:
--   SELECT `id`, `name`, `room_category_id` FROM `services` WHERE `room_category_id` IS NOT NULL;
-- If ANY rows are returned, STOP IMMEDIATELY. Do not run the ALTER below.
--
-- STATE CHECK:
-- Confirm that services_room_category_id_foreign currently references service_categories.
-- Run:
--   SELECT REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE
--   WHERE TABLE_SCHEMA = 'u846442502_zenflow_db' AND TABLE_NAME = 'services' AND COLUMN_NAME = 'room_category_id' AND REFERENCED_TABLE_NAME IS NOT NULL;
-- If REFERENCED_TABLE_NAME = 'room_categories', this step is already applied. Skip it.
-- If REFERENCED_TABLE_NAME = 'service_categories', proceed with the single atomic ALTER below.
-- ============================================================

ALTER TABLE `services`
  DROP FOREIGN KEY `services_room_category_id_foreign`,
  ADD CONSTRAINT `services_room_category_id_foreign`
    FOREIGN KEY (`room_category_id`) REFERENCES `room_categories` (`id`)
    ON DELETE SET NULL;


-- ============================================================
-- STEP 6: Make duration_minutes Nullable on services (Migration 58)
-- Source: 2026_11_18_125535_make_duration_minutes_nullable_on_services_table.php
-- ============================================================

ALTER TABLE `services`
  MODIFY COLUMN `duration_minutes` int NULL DEFAULT NULL;


-- ============================================================
-- STEP 7: Create package_choice_groups (Migration 59)
-- Source: 2026_11_18_125537_create_package_choice_groups_table.php
-- Referenced tables: services (existing)
-- ============================================================

CREATE TABLE IF NOT EXISTS `package_choice_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint unsigned NOT NULL,
  `label` varchar(255) NOT NULL,
  `is_required` tinyint NOT NULL DEFAULT 1,
  `max_selections` tinyint NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `package_choice_groups_service_id_foreign` (`service_id`),
  CONSTRAINT `package_choice_groups_service_id_foreign`
    FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- STEP 8: Create package_choice_options (Migration 60)
-- Source: 2026_11_18_125542_create_package_choice_options_table.php
-- Referenced tables: package_choice_groups (created Step 7), services (existing)
-- ============================================================

CREATE TABLE IF NOT EXISTS `package_choice_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `choice_group_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned NOT NULL,
  `sort_order` tinyint NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `package_choice_options_choice_group_id_service_id_unique` (`choice_group_id`, `service_id`),
  KEY `package_choice_options_service_id_foreign` (`service_id`),
  CONSTRAINT `package_choice_options_choice_group_id_foreign`
    FOREIGN KEY (`choice_group_id`) REFERENCES `package_choice_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `package_choice_options_service_id_foreign`
    FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- STEP 9: Create appointment_package_selections (Migration 61)
-- Source: 2026_11_18_125543_create_appointment_package_selections_table.php
-- Referenced tables: appointments (existing), package_choice_groups (created Step 7), services (existing)
-- ============================================================

CREATE TABLE IF NOT EXISTS `appointment_package_selections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `appointment_id` bigint unsigned NOT NULL,
  `choice_group_id` bigint unsigned DEFAULT NULL,
  `selected_service_id` bigint unsigned DEFAULT NULL,
  `group_label_snapshot` varchar(255) NOT NULL,
  `selected_service_name_snapshot` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appointment_package_selections_appointment_id_foreign` (`appointment_id`),
  KEY `appointment_package_selections_choice_group_id_foreign` (`choice_group_id`),
  KEY `appointment_package_selections_selected_service_id_foreign` (`selected_service_id`),
  CONSTRAINT `appointment_package_selections_appointment_id_foreign`
    FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_package_selections_choice_group_id_foreign`
    FOREIGN KEY (`choice_group_id`) REFERENCES `package_choice_groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointment_package_selections_selected_service_id_foreign`
    FOREIGN KEY (`selected_service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
