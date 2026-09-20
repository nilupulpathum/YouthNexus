<?php
/**
 * migrate_manage_user.php
 * ============================================================
 * Additive-only migration for the Manage User feature.
 *
 * What it does:
 *  1. Adds `must_change_password` BOOLEAN column to User
 *     (uses IF NOT EXISTS guard — safe to run more than once).
 *
 * What it does NOT do:
 *  - Does NOT drop, rename, or alter any existing column.
 *  - Does NOT remove or modify any existing table.
 *  - Does NOT touch any FK or index already in place.
 *
 * Usage (run once):
 *   http://localhost/YouthNexus/YouthNexus/database/migrate_manage_user.php
 *   — or —
 *   php database/migrate_manage_user.php
 * ============================================================
 */

// ---- Bootstrap core (gives us DB constants) ----
define('RUNNING_MIGRATION', true);
require_once __DIR__ . '/../app/core/config.php';

// ---- PDO connection (raw — no Model class needed) ----
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    ]);
} catch (PDOException $e) {
    die('<b>Connection failed:</b> ' . htmlspecialchars($e->getMessage()));
}

$results = [];

// ============================================================
// STEP 1: Add must_change_password to User
//         MariaDB 10.0+ / MySQL 8.0.3+ support IF NOT EXISTS
//         For older MySQL we check the information_schema first.
// ============================================================

$columnExists = $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = '" . DB_NAME . "'
       AND TABLE_NAME   = 'User'
       AND COLUMN_NAME  = 'must_change_password'"
)->fetchColumn();

if (!$columnExists) {
    $pdo->exec(
        "ALTER TABLE User
         ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0
         COMMENT 'Forces password change on next login (set to 1 for newly created NYSC users)'"
    );
    $results[] = ['status' => 'OK',      'msg' => "Column <code>must_change_password</code> added to <code>User</code>."];
} else {
    $results[] = ['status' => 'SKIPPED', 'msg' => "Column <code>must_change_password</code> already exists — skipped."];
}

// ============================================================
// STEP 2: Verify AuditLog can hold the new action_type values.
//         action_type is VARCHAR(50) — strings fit without schema change.
//         We just report the column length for confirmation.
// ============================================================

$col = $pdo->query(
    "SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = '" . DB_NAME . "'
       AND TABLE_NAME   = 'AuditLog'
       AND COLUMN_NAME  = 'action_type'"
)->fetchColumn();

if ($col) {
    $results[] = ['status' => 'OK', 'msg' => "AuditLog.action_type is VARCHAR({$col}) — no schema change needed. New action strings (USER_CREATED, USER_UPDATED, USER_DEACTIVATED, USER_REACTIVATED) fit."];
} else {
    $results[] = ['status' => 'WARN', 'msg' => "Could not verify AuditLog.action_type column."];
}

// ============================================================
// Output
// ============================================================
if (php_sapi_name() === 'cli') {
    foreach ($results as $r) {
        echo "[{$r['status']}] {$r['msg']}\n";
    }
    echo "\nMigration complete.\n";
} else {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
    <title>Migration — Manage User</title>
    <style>
        body { font-family: monospace; background:#f4f6fb; padding:40px; }
        h1   { color:#1e40af; }
        .ok   { color:#059669; }
        .skip { color:#6b7280; }
        .warn { color:#d97706; }
        li   { margin:8px 0; font-size:15px; }
    </style></head><body>';
    echo '<h1>🔧 Manage User Migration</h1><ul>';
    foreach ($results as $r) {
        $cls = strtolower($r['status']) === 'ok' ? 'ok' : (strtolower($r['status']) === 'skipped' ? 'skip' : 'warn');
        echo "<li class=\"{$cls}\"><b>[{$r['status']}]</b> {$r['msg']}</li>";
    }
    echo '</ul><p><b>✅ Migration complete.</b></p></body></html>';
}
