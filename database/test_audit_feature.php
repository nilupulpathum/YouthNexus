<?php
/**
 * Test script for Annual Financial Audit feature
 *
 * Usage: c:\xampp\php\php.exe database/test_audit_feature.php
 */

require_once __DIR__ . '/../app/core/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/AuditModel.php';

echo "=== Testing Annual Financial Audit Feature ===\n";

$model = new AuditModel();

// Test 1: Available scopes
$scopes = $model->getAvailableScopes();
echo "1. Available Scopes count: " . count($scopes) . " (First: {$scopes[0]['name']})\n";
assert(count($scopes) >= 2, "Scopes list should include National and Zones");

// Test 2: Fetch Audit for Kandy Zone FY 2026
$audit = $model->getAudit('Zonal', 6, 2026, 1);
echo "2. Audit Loaded:\n";
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

assert($audit->math_check_status === 'Passed', "Math check should be Passed for Kandy Zone");
assert(count($audit->red_flags) >= 3, "Should have at least 3 red flag exceptions");

// Test 3: Request Clarification
$flagIds = array_map(fn($f) => $f->red_flag_id, $audit->red_flags);
$clarifyResult = $model->requestClarification($audit->audit_id, $flagIds, "Please provide tax invoice for PA rental", 1, 7);
echo "3. Clarification Sent:\n";
echo "   - Success: " . ($clarifyResult['success'] ? 'YES' : 'NO') . "\n";
echo "   - Recipients Notified: {$clarifyResult['count']} ({$clarifyResult['recipients']})\n";
assert($clarifyResult['success'] === true, "Clarification should succeed");

// Verify notification was recorded in DB
$pdo = Database::getInstance()->getConnection();
$notifCount = $pdo->query("SELECT COUNT(*) FROM Notification WHERE related_entity_type = 'Audit' AND related_entity_id = {$audit->audit_id}")->fetchColumn();
echo "   - Notifications in DB: $notifCount\n";
assert($notifCount > 0, "Notification record should exist in DB");

// Test 4: Export CSV
$csv = $model->exportAuditCsv($audit->audit_id);
echo "4. Export CSV:\n";
echo "   - Length: " . strlen($csv) . " bytes\n";
assert(strpos($csv, 'CORE MATHEMATICAL LEDGER VERIFICATION') !== false, "CSV must contain verification section");
assert(strpos($csv, 'Expected Closing Balance') !== false, "CSV must contain expected closing balance");

// Test 5: Sign-Off and Ledger Lock
$pdo->query("UPDATE Audit SET locked = 0, audit_status = 'Pending' WHERE audit_id = {$audit->audit_id}");
$signResult = $model->signOffAudit($audit->audit_id, 1);
echo "5. Sign-Off & Lock:\n";
echo "   - Success: " . ($signResult['success'] ? 'YES' : 'NO') . "\n";
echo "   - Message: {$signResult['message']}\n";
assert($signResult['success'] === true, "Sign-off should succeed");

// Verify audit locked state in DB
$refreshed = $model->getAuditById($audit->audit_id);
echo "   - Audit Status: {$refreshed->audit_status}\n";
echo "   - Locked Flag: " . ($refreshed->locked ? '1 (LOCKED)' : '0 (UNLOCKED)') . "\n";
assert($refreshed->locked == 1, "Audit must be locked after sign-off");

// Reset back to open for user interface testing
$pdo->query("UPDATE Audit SET locked = 0, audit_status = 'Pending' WHERE audit_id = {$audit->audit_id}");
$pdo->query("UPDATE RedFlag SET status = 'Open' WHERE audit_id = {$audit->audit_id}");

echo "\n=== ALL AUDIT FEATURE TESTS PASSED SUCCESSFULLY ===\n";
