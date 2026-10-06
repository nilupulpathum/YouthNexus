<?php
/**
 * Master Migration Runner for YouthNexus
 *
 * Runs all database migrations in dependency-safe order.
 * Safe to run repeatedly (idempotent).
 *
 * Usage: c:\xampp\php\php.exe database/run_migrations.php
 */

require_once __DIR__ . '/../app/core/config.php';

$php = PHP_BINARY ?: 'php';

// Order matters for initial table dependencies
$orderedMigrations = [
    'migrate_fund_transfer.php',
    'migrate_broadcast_announcements.php',
    'migrate_announcement_archive_restore.php',
    'migrate_divisional_reports.php',
    'migrate_manage_reports.php',
    'migrate_national_analytics.php',
    'migrate_divisional_club_health.php',
    'migrate_annual_audit.php',
    'migrate_annual_audit_v2.php',
    'migrate_divisional_club_audit.php',
    'migrate_zonal_audit_flags.php',
    'migrate_divisional_treasurer.php',
    'migrate_divisional_void_request.php',
    'migrate_divisional_asset_management.php',
    'migrate_manage_assets.php',
    'migrate_divisional_lifecycle.php',
    'migrate_event_rsvp.php',
    'migrate_handover_log.php',
    'migrate_manage_user.php',
    'migrate_cv_skill_endorsement.php',
];

echo "=== YouthNexus Master Migration Runner ===\n\n";

$allPassed = true;

foreach ($orderedMigrations as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (!file_exists($fullPath)) {
        continue;
    }

    echo "Running: $file ... ";
    $output = [];
    $returnCode = 0;
    exec("\"$php\" \"$fullPath\" 2>&1", $output, $returnCode);

    if ($returnCode === 0) {
        echo "OK\n";
    } else {
        echo "FAILED (code $returnCode)\n";
        echo "--- Details ---\n" . implode("\n", $output) . "\n---------------\n";
        $allPassed = false;
    }
}

echo "\n" . ($allPassed ? "=== All migrations completed successfully! ===" : "=== Some migrations encountered issues. ===") . "\n";
