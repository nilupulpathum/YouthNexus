<?php
/**
 * Additive migration for Divisional Treasurer asset requests.
 * Run after database/migrate_manage_assets.php.
 */
require_once __DIR__ . '/../app/core/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    echo "Connected to database.\n";
    $required = ['AssetCatalogItem', 'AssetStock', 'AssetTransfer', 'Notification', 'AuditLog', 'Club', 'Division', 'Zone', 'User'];
    $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    foreach ($required as $table) {
        $check->execute([$table]);
        if ((int) $check->fetchColumn() === 0) {
            throw new RuntimeException("Required table `$table` does not exist. Run migrate_manage_assets.php first.");
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS `AssetRequest` (
        `asset_request_id` INT AUTO_INCREMENT PRIMARY KEY,
        `catalog_item_id` INT NOT NULL,
        `requester_level` ENUM('Club','Divisional') NOT NULL,
        `requester_id` INT NOT NULL,
        `requested_to_level` ENUM('Divisional','Zonal') NOT NULL,
        `requested_to_id` INT NOT NULL,
        `scope_direction` ENUM('ClubToDivision','DivisionToZonal') NOT NULL,
        `quantity` INT NOT NULL,
        `reason` TEXT NOT NULL,
        `status` ENUM('Pending','Approved','Rejected','Withdrawn') NOT NULL DEFAULT 'Pending',
        `remarks` TEXT NULL,
        `requested_by` INT NOT NULL,
        `requested_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `decided_by` INT NULL,
        `decided_at` TIMESTAMP NULL DEFAULT NULL,
        CONSTRAINT `fk_asset_request_catalog` FOREIGN KEY (`catalog_item_id`) REFERENCES `AssetCatalogItem` (`catalog_item_id`) ON DELETE RESTRICT,
        CONSTRAINT `fk_asset_request_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `User` (`user_id`) ON DELETE RESTRICT,
        CONSTRAINT `fk_asset_request_decided_by` FOREIGN KEY (`decided_by`) REFERENCES `User` (`user_id`) ON DELETE RESTRICT,
        INDEX `idx_asset_request_target` (`requested_to_level`, `requested_to_id`, `status`),
        INDEX `idx_asset_request_requester` (`requester_level`, `requester_id`, `status`),
        INDEX `idx_asset_request_direction` (`scope_direction`, `status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "Table `AssetRequest` ready.\n";
    echo "\n=== Divisional Asset Management Migration Completed Successfully ===\n";
} catch (Throwable $exception) {
    die('Migration error: ' . $exception->getMessage() . "\n");
}
