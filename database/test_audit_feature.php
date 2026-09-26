<?php
/**
 * Test script for Annual Financial Audit feature (workflow-aligned)
 *
 * Usage: c:\xampp\php\php.exe database/test_audit_feature.php
 */

require_once __DIR__ . '/../app/core/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/AuditModel.php';

echo "=== Testing Annual Financial Audit Feature ===\n";

$model = new AuditModel();

// Test 1: Available scopes (Phase 1 — National / Zones / Divisions / Clubs)
$scopes = $model->getAvailableScopes();
echo "1. Available Scopes:\n";
echo "   - National options: " . count($scopes['National']) . "\n";
echo "   - Zonal options: " . count($scopes['Zonal']) . "\n";
echo "   - Divisional options: " . count($scopes['Divisional']) . "\n";
echo "   - Club options: " . count($scopes['Club']) . "\n";
assert(count($scopes['Zonal']) >= 1, "Scopes list should include zones");

// Test 2: Compile audit for Kandy Zone FY 2026 (Phase 1 → 2 → 3)
$kandyScopeId = null;
foreach ($scopes['Zonal'] as $s) {
    if (stripos($s['name'], 'Kandy') !== false) {
        $kandyScopeId = $s['scope_id'];
        break;
    }
}
$kandyScopeId = $kandyScopeId ?: 6;

$auditId = $model->runAuditCheck('Zonal', $kandyScopeId, 2026, 1);
$audit = $model->getAuditById($auditId);
echo "2. Audit Compiled:\n";
echo "   - Scope: {$audit->scope_details->title}\n";
echo "   - FY: {$audit->financial_year}\n";
echo "   - Opening: LKR " . number_format($audit->opening_balance, 2) . "\n";
echo "   - Income: LKR " . number_format($audit->total_income, 2) . "\n";
echo "   - Transfers In: LKR " . number_format($audit->total_transfers_received, 2) . "\n";
echo "   - Expenses: LKR " . number_format($audit->total_expenses, 2) . "\n";
echo "   - Transfers Out: LKR " . number_format($audit->total_transfers_distributed, 2) . "\n";
echo "   - Expected Closing: LKR " . number_format($audit->expected_closing_balance, 2) . "\n";
echo "   - Actual Closing: LKR " . number_format($audit->actual_closing_balance, 2) . "\n";
echo "   - Math Check Status: {$audit->math_check_status}\n";
echo "   - Red Flags Count: " . count($audit->red_flags) . "\n";
foreach ($audit->red_flags as $rf) {
    echo "     • [{$rf->flag_type}] {$rf->description}\n";
}

// Test 3: Clarification request (Phase 4 — pending path)
$flagIds = array_map(fn($f) => $f->red_flag_id, $audit->red_flags);
$clarifyResult = $model->requestClarification($auditId, $flagIds, "Please provide tax invoice for the flagged expenses", 1, 7);
echo "3. Clarification Sent:\n";
echo "   - Success: " . ($clarifyResult['success'] ? 'YES' : 'NO') . "\n";
echo "   - Recipients Notified: {$clarifyResult['count']} ({$clarifyResult['recipients']})\n";
assert($clarifyResult['success'] === true, "Clarification should succeed");

// Verify notification was recorded in DB
$pdo = Database::getInstance()->getConnection();
$notifCount = $pdo->query("SELECT COUNT(*) FROM Notification WHERE related_entity_type = 'Audit' AND related_entity_id = {$auditId}")->fetchColumn();
echo "   - Notifications in DB: $notifCount\n";
assert($notifCount > 0, "Notification record should exist in DB");

// Test 4: Export CSV
$csv = $model->exportAuditCsv($auditId);
echo "4. Export CSV:\n";
echo "   - Length: " . strlen($csv) . " bytes\n";
assert(strpos($csv, 'CORE MATHEMATICAL LEDGER VERIFICATION') !== false, "CSV must contain verification section");
assert(strpos($csv, 'Expected Closing Balance') !== false, "CSV must contain expected closing balance");

// Test 5: Sign-off is BLOCKED while red flags are unresolved (Phase 4 rule)
$signBlocked = $model->signOffAudit($auditId, 1);
echo "5. Sign-Off With Unresolved Flags (should be blocked):\n";
echo "   - Success: " . ($signBlocked['success'] ? 'YES' : 'NO') . "\n";
echo "   - Message: {$signBlocked['message']}\n";
assert($signBlocked['success'] === false, "Sign-off must be blocked while red flags are unresolved");

// Test 6: Sign-off succeeds once every flag is resolved
foreach ($flagIds as $fid) {
    $model->resolveFlag($fid);
}
$signResult = $model->signOffAudit($auditId, 1);
echo "6. Sign-Off & Lock (all flags resolved):\n";
echo "   - Success: " . ($signResult['success'] ? 'YES' : 'NO') . "\n";
echo "   - Message: {$signResult['message']}\n";
assert($signResult['success'] === true, "Sign-off should succeed after flags are resolved");

// Verify audit locked state in DB
$refreshed = $model->getAuditById($auditId);
echo "   - Audit Status: {$refreshed->audit_status}\n";
echo "   - Locked Flag: " . ($refreshed->locked ? '1 (LOCKED)' : '0 (UNLOCKED)') . "\n";
assert($refreshed->locked == 1, "Audit must be locked after sign-off");

// Reset back to open for user interface testing
$pdo->query("UPDATE Audit SET locked = 0, audit_status = 'Pending' WHERE audit_id = {$auditId}");
$pdo->query("UPDATE RedFlag SET status = 'Open' WHERE audit_id = {$auditId}");

echo "\n=== ALL AUDIT FEATURE TESTS PASSED SUCCESSFULLY ===\n";
