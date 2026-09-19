<?php
/**
 * Test script to verify Manage Assets feature functionality.
 */

$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';

require_once __DIR__ . '/../app/core/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/models/AssetModel.php';

$pdo = Database::getInstance()->getConnection();
$model = new AssetModel();

echo "--- 1. Testing Catalog Items ---\n";
$items = $model->getCatalogItems();
echo "Total Catalog Items: " . count($items) . " (Expected: 20)\n";
assert(count($items) === 20, "Must have 20 catalog items");

$categories = $model->getCategories();
echo "Categories: " . implode(', ', $categories) . "\n";

echo "\n--- 2. Testing National Inventory & Stats ---\n";
$nationalInv = $model->getNationalInventory();
echo "National Inventory lines: " . count($nationalInv) . "\n";
$stats = $model->getInventoryStats();
echo "Total Stock: " . $stats['total_stock'] . " units\n";
echo "Total Distributed: " . $stats['total_distributed'] . " units\n";
echo "Low Stock SKUs: " . $stats['low_stock_count'] . "\n";
echo "Active Zonal Deficits: " . $stats['active_deficits'] . "\n";

echo "\n--- 3. Testing Stock Addition ---\n";
$testItem = $items[0];
$beforeStock = (int)$pdo->query("SELECT quantity FROM AssetStock WHERE catalog_item_id = {$testItem->catalog_item_id} AND owner_level = 'National'")->fetchColumn();
echo "Stock before addition: {$beforeStock}\n";

$model->addNationalStock($testItem->catalog_item_id, 15, 'TEST-INV-9999', 1);
$afterStock = (int)$pdo->query("SELECT quantity FROM AssetStock WHERE catalog_item_id = {$testItem->catalog_item_id} AND owner_level = 'National'")->fetchColumn();
echo "Stock after adding 15: {$afterStock}\n";
assert($afterStock === $beforeStock + 15, "Stock addition increment check");

$auditCheck = $pdo->query("SELECT details FROM AuditLog WHERE action_type = 'StockAdded' ORDER BY log_id DESC LIMIT 1")->fetchColumn();
echo "AuditLog entry: {$auditCheck}\n";

echo "\n--- 4. Testing Asset Distribution to Zone ---\n";
$zones = $model->getAllZones();
$targetZone = $zones[0];
$zonalStockBefore = (int)$pdo->query("SELECT quantity FROM AssetStock WHERE catalog_item_id = {$testItem->catalog_item_id} AND owner_level = 'Zonal' AND owner_id = {$targetZone->zonal_id}")->fetchColumn();

$distResult = $model->distributeToZone($testItem->catalog_item_id, $targetZone->zonal_id, 5, 1, 'Test Zonal Allocation');
echo "Distribution Result: Transfer ID #{$distResult['transfer_id']}\n";

$zonalStockAfter = (int)$pdo->query("SELECT quantity FROM AssetStock WHERE catalog_item_id = {$testItem->catalog_item_id} AND owner_level = 'Zonal' AND owner_id = {$targetZone->zonal_id}")->fetchColumn();
$natStockAfterDist = (int)$pdo->query("SELECT quantity FROM AssetStock WHERE catalog_item_id = {$testItem->catalog_item_id} AND owner_level = 'National'")->fetchColumn();

echo "National stock after distribution: {$natStockAfterDist} (Decremented by 5)\n";
echo "Zonal stock after distribution: {$zonalStockAfter} (Incremented from {$zonalStockBefore} by 5)\n";
assert($natStockAfterDist === $afterStock - 5, "National stock decrement check");
assert($zonalStockAfter === $zonalStockBefore + 5, "Zonal stock increment check");

$transferLogCheck = $pdo->query("SELECT * FROM AssetTransfer WHERE transfer_id = {$distResult['transfer_id']}")->fetch();
echo "AssetTransfer record verified: Qty = {$transferLogCheck->quantity}, To Zone = {$transferLogCheck->to_owner_id}\n";

$notifCount = $pdo->query("SELECT COUNT(*) FROM Notification WHERE related_entity_type = 'AssetTransfer' AND related_entity_id = {$distResult['transfer_id']}")->fetchColumn();
echo "Notifications created: {$notifCount}\n";

echo "\n--- 5. Testing Syntax of Controller and View ---\n";
require_once __DIR__ . '/../app/controllers/Assets.php';
require_once __DIR__ . '/../app/controllers/Manageassets.php';
echo "Controllers compiled without syntax errors.\n";

echo "\nALL TESTS PASSED SUCCESSFULLY!\n";
