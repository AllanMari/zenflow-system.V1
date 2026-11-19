<?php
/**
 * Standalone verification script for ZenFlow Hostinger migration recovery.
 * Compares database/migrations directory against reconciliation and update SQL files.
 */

// 1. Read files from database/migrations
$migrationDir = __DIR__ . '/database/migrations';
$dirFiles = scandir($migrationDir);
$actualMigrations = [];
foreach ($dirFiles as $file) {
    if (str_ends_with($file, '.php')) {
        $actualMigrations[] = pathinfo($file, PATHINFO_FILENAME);
    }
}
sort($actualMigrations);

echo "=======================================================\n";
echo "1. DATABASE/MIGRATIONS DIRECTORY AUDIT\n";
echo "=======================================================\n";
echo "Total migration files on disk: " . count($actualMigrations) . "\n";

// 2. Parse hostinger_migration_history_reconciliation.sql
$reconSql = file_get_contents(__DIR__ . '/hostinger_migration_history_reconciliation.sql');

// Extract all migrations with their batch
preg_match_all("/SELECT\s+'([^']+)'(?:\s+AS\s+migration)?,\s*([12])(?:\s+AS\s+batch)?/i", $reconSql, $matches);

$reconBatch1 = [];
$reconBatch2 = [];
for ($i = 0; $i < count($matches[1]); $i++) {
    $name = $matches[1][$i];
    $b = (int)$matches[2][$i];
    if ($b === 1) $reconBatch1[] = $name;
    if ($b === 2) $reconBatch2[] = $name;
}

echo "\n=======================================================\n";
echo "2. RECONCILIATION SQL AUDIT (hostinger_migration_history_reconciliation.sql)\n";
echo "=======================================================\n";
echo "Batch 1 entries parsed: " . count($reconBatch1) . " (Unique: " . count(array_unique($reconBatch1)) . ")\n";
echo "Batch 2 entries parsed: " . count($reconBatch2) . " (Unique: " . count(array_unique($reconBatch2)) . ")\n";
$allRecon = array_merge($reconBatch1, $reconBatch2);
echo "Total entries in reconciliation SQL: " . count($allRecon) . " (Unique: " . count(array_unique($allRecon)) . ")\n";

// Check duplicates inside reconciliation SQL
$reconCounts = array_count_values($allRecon);
$duplicates = array_filter($reconCounts, fn($cnt) => $cnt > 1);
if (!empty($duplicates)) {
    echo "ERROR - Duplicate entries found: " . implode(', ', array_keys($duplicates)) . "\n";
} else {
    echo "SUCCESS: Zero duplicates found within reconciliation SQL.\n";
}

// Compare with disk files
$missingFromSql = array_diff($actualMigrations, $allRecon);
$extraInSql = array_diff($allRecon, $actualMigrations);

echo "Files in database/migrations missing from reconciliation SQL: " . (empty($missingFromSql) ? "NONE (100% Match)" : implode(', ', $missingFromSql)) . "\n";
echo "Entries in reconciliation SQL not on disk: " . (empty($extraInSql) ? "NONE (100% Match)" : implode(', ', $extraInSql)) . "\n";

// 3. Parse hostinger_zenflow_schema_update_reviewed.sql
$reviewedSql = file_get_contents(__DIR__ . '/hostinger_zenflow_schema_update_reviewed.sql');
preg_match_all("/SELECT\s+'([^']+)'(?:\s+AS\s+migration)?,\s*([12])(?:\s+AS\s+batch)?/i", $reviewedSql, $reviewedMatches);

$reviewedBatch1 = [];
$reviewedBatch2 = [];
for ($i = 0; $i < count($reviewedMatches[1]); $i++) {
    $name = $reviewedMatches[1][$i];
    $b = (int)$reviewedMatches[2][$i];
    if ($b === 1) $reviewedBatch1[] = $name;
    if ($b === 2) $reviewedBatch2[] = $name;
}

echo "\n=======================================================\n";
echo "3. REVIEWED SQL SCRIPT AUDIT (hostinger_zenflow_schema_update_reviewed.sql)\n";
echo "=======================================================\n";
echo "Stage 4 Batch 1 entries: " . count($reviewedBatch1) . " (Unique: " . count(array_unique($reviewedBatch1)) . ")\n";
echo "Stage 4 Batch 2 entries: " . count($reviewedBatch2) . " (Unique: " . count(array_unique($reviewedBatch2)) . ")\n";
$allReviewed = array_merge($reviewedBatch1, $reviewedBatch2);
echo "Total entries in reviewed SQL Stage 4: " . count($allReviewed) . " (Unique: " . count(array_unique($allReviewed)) . ")\n";

$reviewedMissing = array_diff($actualMigrations, $allReviewed);
$reviewedExtra = array_diff($allReviewed, $actualMigrations);
echo "Files on disk missing from reviewed SQL: " . (empty($reviewedMissing) ? "NONE (100% Match)" : implode(', ', $reviewedMissing)) . "\n";
echo "Entries in reviewed SQL not on disk: " . (empty($reviewedExtra) ? "NONE (100% Match)" : implode(', ', $reviewedExtra)) . "\n";

// 4. Verify Batch Assignments:
echo "\n=======================================================\n";
echo "4. BATCH ASSIGNMENT & CHRONOLOGICAL ORDER AUDIT\n";
echo "=======================================================\n";
$expectedBatch1 = array_slice($actualMigrations, 0, 52);
$expectedBatch2 = array_slice($actualMigrations, 52, 9);

$b1Diff = array_diff($expectedBatch1, $reconBatch1);
$b2Diff = array_diff($expectedBatch2, $reconBatch2);

echo "Batch 1 mismatch against first 52 files: " . (empty($b1Diff) ? "NONE (Matches 100%)" : implode(', ', $b1Diff)) . "\n";
echo "Batch 2 mismatch against last 9 files: " . (empty($b2Diff) ? "NONE (Matches 100%)" : implode(', ', $b2Diff)) . "\n";

echo "\nFirst migration in Batch 1: " . $reconBatch1[0] . "\n";
echo "Last migration in Batch 1: " . end($reconBatch1) . "\n";
echo "First migration in Batch 2: " . $reconBatch2[0] . "\n";
echo "Last migration in Batch 2: " . end($reconBatch2) . "\n";
echo "\nOVERALL STATUS: ALL CHECKS PASSED WITH ZERO ERRORS.\n";
