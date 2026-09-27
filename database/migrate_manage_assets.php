<?php
/**
 * Database Migration Script for Manage Assets Feature
 *
 * Sets up tables:
 * - AssetCatalogItem (20 standardized items across 4 categories)
 * - AssetStock (National and Zonal inventory levels)
 * - AssetTransfer (Transfer audit log)
 * - Notification (Zonal coordinator alerts)
 *
 * Usage: php database/migrate_manage_assets.php
 */

require_once __DIR__ . '/../app/core/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    echo "Connected to database youthnexus.\n";

    // ============================================
    // 1. AssetCatalogItem (20 standardized items)
    // ============================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS AssetCatalogItem (
        catalog_item_id               INT AUTO_INCREMENT PRIMARY KEY,
        category                      VARCHAR(60) NOT NULL,
        item_name                     VARCHAR(150) NOT NULL,
        sku                           VARCHAR(50) NOT NULL UNIQUE,
        specifications                VARCHAR(255) DEFAULT NULL,
        unit                          VARCHAR(50) NOT NULL DEFAULT 'Units',
        national_low_stock_threshold  INT NOT NULL DEFAULT 10,
        zonal_required_threshold      INT NOT NULL DEFAULT 2,
        created_at                    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_category (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `AssetCatalogItem` ready.\n";

    // ============================================
    // 2. AssetStock (National, Zonal, Divisional, Club inventory)
    // ============================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS AssetStock (
        stock_id           INT AUTO_INCREMENT PRIMARY KEY,
        catalog_item_id    INT NOT NULL,
        owner_level        ENUM('National','Zonal','Divisional','Club') NOT NULL DEFAULT 'National',
        owner_id           INT NULL,
        quantity           INT NOT NULL DEFAULT 0,
        allocated_quantity INT NOT NULL DEFAULT 0,
        updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (catalog_item_id) REFERENCES AssetCatalogItem(catalog_item_id) ON DELETE CASCADE,
        UNIQUE KEY uk_catalog_owner (catalog_item_id, owner_level, owner_id),
        INDEX idx_owner (owner_level, owner_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `AssetStock` ready.\n";

    // ============================================
    // 3. AssetTransfer (Transfer log)
    // ============================================
    // Reconcile/Drop old AssetTransfer if schema differs
    $cols = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".DB_NAME."' AND TABLE_NAME = 'AssetTransfer'")->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($cols) && !in_array('catalog_item_id', $cols)) {
        $pdo->exec("DROP TABLE IF EXISTS AssetTransfer");
        echo "Dropped legacy `AssetTransfer` table.\n";
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS AssetTransfer (
        transfer_id       INT AUTO_INCREMENT PRIMARY KEY,
        catalog_item_id   INT NOT NULL,
        from_owner_level  ENUM('National','Zonal','Divisional','Club') NOT NULL DEFAULT 'National',
        from_owner_id     INT NULL,
        to_owner_level    ENUM('National','Zonal','Divisional','Club') NOT NULL DEFAULT 'Zonal',
        to_owner_id       INT NOT NULL,
        quantity          INT NOT NULL,
        transfer_date     DATE NOT NULL,
        transferred_by    INT NOT NULL,
        status            ENUM('Completed','Pending','In-Transit','Cancelled') NOT NULL DEFAULT 'Completed',
        notes             TEXT DEFAULT NULL,
        created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (catalog_item_id) REFERENCES AssetCatalogItem(catalog_item_id) ON DELETE CASCADE,
        FOREIGN KEY (transferred_by) REFERENCES User(user_id) ON DELETE CASCADE,
        INDEX idx_transfer_date (transfer_date),
        INDEX idx_to_owner (to_owner_level, to_owner_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `AssetTransfer` ready.\n";

    // ============================================
    // 4. Notification table (if not exists)
    // ============================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS Notification (
        notification_id     INT AUTO_INCREMENT PRIMARY KEY,
        recipient_id        INT NOT NULL,
        type                VARCHAR(50) NOT NULL DEFAULT 'General',
        message             VARCHAR(500) NOT NULL,
        related_entity_type VARCHAR(50) DEFAULT NULL,
        related_entity_id   INT DEFAULT NULL,
        read_status         BOOLEAN NOT NULL DEFAULT FALSE,
        created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (recipient_id) REFERENCES User(user_id) ON DELETE CASCADE,
        INDEX idx_recipient (recipient_id, read_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `Notification` ready.\n";

    // ============================================
    // 5. Seed 20 Standardized Catalog Items (5 per Category)
    // ============================================
    $catalogCount = $pdo->query("SELECT COUNT(*) FROM AssetCatalogItem")->fetchColumn();
    if ($catalogCount < 20) {
        $items = [
            // Category 1: Sports & Recreation
            [
                'category' => 'Sports & Recreation',
                'item_name' => 'Cricket Tournament Kit',
                'sku' => 'SKU-SP-01',
                'specifications' => 'Grade A Match Grade',
                'unit' => 'Kits',
                'national_low_stock_threshold' => 30,
                'zonal_required_threshold' => 5
            ],
            [
                'category' => 'Sports & Recreation',
                'item_name' => 'Multi-Sport Volleyball Set',
                'sku' => 'SKU-SP-02',
                'specifications' => 'Net, Posts & 10 Balls',
                'unit' => 'Sets',
                'national_low_stock_threshold' => 25,
                'zonal_required_threshold' => 5
            ],
            [
                'category' => 'Sports & Recreation',
                'item_name' => 'Football Training Equipment Set',
                'sku' => 'SKU-SP-03',
                'specifications' => '10 Match Balls, Agility Ladders & Cones',
                'unit' => 'Sets',
                'national_low_stock_threshold' => 25,
                'zonal_required_threshold' => 4
            ],
            [
                'category' => 'Sports & Recreation',
                'item_name' => 'Table Tennis Full Set & Board',
                'sku' => 'SKU-SP-04',
                'specifications' => 'Tournament Table, 4 Bats, 50 Balls & Net',
                'unit' => 'Sets',
                'national_low_stock_threshold' => 15,
                'zonal_required_threshold' => 2
            ],
            [
                'category' => 'Sports & Recreation',
                'item_name' => 'Athletics Track & Field Equipment Pack',
                'sku' => 'SKU-SP-05',
                'specifications' => 'Stopwatches, Measuring Tapes, Relay Batons, Shot Put',
                'unit' => 'Packs',
                'national_low_stock_threshold' => 10,
                'zonal_required_threshold' => 2
            ],

            // Category 2: Event & A/V Equipment
            [
                'category' => 'Event & A/V Equipment',
                'item_name' => 'Professional PA Sound System',
                'sku' => 'SKU-AV-01',
                'specifications' => '800W Active Dual',
                'unit' => 'Units',
                'national_low_stock_threshold' => 15,
                'zonal_required_threshold' => 2
            ],
            [
                'category' => 'Event & A/V Equipment',
                'item_name' => 'Heavy-duty Laser Projector',
                'sku' => 'SKU-AV-02',
                'specifications' => '6500 ANSI Lumens',
                'unit' => 'Units',
                'national_low_stock_threshold' => 10,
                'zonal_required_threshold' => 1
            ],
            [
                'category' => 'Event & A/V Equipment',
                'item_name' => 'Digital Cordless Microphones (Pair)',
                'sku' => 'SKU-AV-03',
                'specifications' => 'UHF Dual Channel',
                'unit' => 'Pairs',
                'national_low_stock_threshold' => 40,
                'zonal_required_threshold' => 6
            ],
            [
                'category' => 'Event & A/V Equipment',
                'item_name' => 'Portable Stage Lighting Rig',
                'sku' => 'SKU-AV-04',
                'specifications' => '4-Bar LED RGB DMX Kit',
                'unit' => 'Rigs',
                'national_low_stock_threshold' => 12,
                'zonal_required_threshold' => 2
            ],
            [
                'category' => 'Event & A/V Equipment',
                'item_name' => 'Multi-Channel Audio Mixer Console',
                'sku' => 'SKU-AV-05',
                'specifications' => '16-Channel USB FX Mixer',
                'unit' => 'Units',
                'national_low_stock_threshold' => 12,
                'zonal_required_threshold' => 2
            ],

            // Category 3: Community & Cleaning
            [
                'category' => 'Community & Cleaning',
                'item_name' => 'Commercial Floor Scrubber',
                'sku' => 'SKU-CL-01',
                'specifications' => '45L Tank Auto-Drive',
                'unit' => 'Units',
                'national_low_stock_threshold' => 8,
                'zonal_required_threshold' => 2
            ],
            [
                'category' => 'Community & Cleaning',
                'item_name' => 'Industrial Pressure Washer',
                'sku' => 'SKU-CL-02',
                'specifications' => '2200 PSI Commercial Heavy-Duty',
                'unit' => 'Units',
                'national_low_stock_threshold' => 10,
                'zonal_required_threshold' => 2
            ],
            [
                'category' => 'Community & Cleaning',
                'item_name' => 'Industrial Vacuum Units',
                'sku' => 'SKU-CL-03',
                'specifications' => 'Heavy Duty Cycle Dual Motor',
                'unit' => 'Units',
                'national_low_stock_threshold' => 15,
                'zonal_required_threshold' => 3
            ],
            [
                'category' => 'Community & Cleaning',
                'item_name' => 'Portable Fogging & Sanitizing Machine',
                'sku' => 'SKU-CL-04',
                'specifications' => 'Thermal ULV Sprayer',
                'unit' => 'Units',
                'national_low_stock_threshold' => 12,
                'zonal_required_threshold' => 2
            ],
            [
                'category' => 'Community & Cleaning',
                'item_name' => 'Waste Management & Recycling Station',
                'sku' => 'SKU-CL-05',
                'specifications' => '4-Compartment Steel Waste Hub',
                'unit' => 'Stations',
                'national_low_stock_threshold' => 20,
                'zonal_required_threshold' => 4
            ],

            // Category 4: Official & Office
            [
                'category' => 'Official & Office',
                'item_name' => 'Ergonomic Office Workstations',
                'sku' => 'SKU-OF-01',
                'specifications' => 'Modular 4-Pod',
                'unit' => 'Pods',
                'national_low_stock_threshold' => 20,
                'zonal_required_threshold' => 5
            ],
            [
                'category' => 'Official & Office',
                'item_name' => 'Executive Conference Table & Chairs',
                'sku' => 'SKU-OF-02',
                'specifications' => '12-Seater with Integrated Power Hub',
                'unit' => 'Sets',
                'national_low_stock_threshold' => 8,
                'zonal_required_threshold' => 1
            ],
            [
                'category' => 'Official & Office',
                'item_name' => 'Heavy-Duty Multi-Function Laser Printer',
                'sku' => 'SKU-OF-03',
                'specifications' => 'Duplex A3/A4 Network Copier & Scanner',
                'unit' => 'Units',
                'national_low_stock_threshold' => 10,
                'zonal_required_threshold' => 2
            ],
            [
                'category' => 'Official & Office',
                'item_name' => 'Fireproof Biometric Document Safe',
                'sku' => 'SKU-OF-04',
                'specifications' => 'UL-Class 2-Hour Fire Resistant 150L',
                'unit' => 'Safes',
                'national_low_stock_threshold' => 10,
                'zonal_required_threshold' => 1
            ],
            [
                'category' => 'Official & Office',
                'item_name' => 'High-Speed Document Scanner & Archiving Hub',
                'sku' => 'SKU-OF-05',
                'specifications' => '80ppm Dual-Sensor Feeder',
                'unit' => 'Units',
                'national_low_stock_threshold' => 10,
                'zonal_required_threshold' => 2
            ],
        ];

        $stmt = $pdo->prepare("INSERT INTO AssetCatalogItem 
            (category, item_name, sku, specifications, unit, national_low_stock_threshold, zonal_required_threshold)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                category = VALUES(category),
                item_name = VALUES(item_name),
                specifications = VALUES(specifications),
                unit = VALUES(unit),
                national_low_stock_threshold = VALUES(national_low_stock_threshold),
                zonal_required_threshold = VALUES(zonal_required_threshold)");

        foreach ($items as $item) {
            $stmt->execute([
                $item['category'],
                $item['item_name'],
                $item['sku'],
                $item['specifications'],
                $item['unit'],
                $item['national_low_stock_threshold'],
                $item['zonal_required_threshold']
            ]);
        }
        echo "Seeded 20 standardized items into `AssetCatalogItem`.\n";
    }

    // ============================================
    // 6. Seed Initial National Warehouse Stock
    // ============================================
    // Remove duplicates if any exist
    $pdo->exec("DELETE s1 FROM AssetStock s1
                INNER JOIN AssetStock s2 
                WHERE s1.stock_id > s2.stock_id 
                  AND s1.catalog_item_id = s2.catalog_item_id 
                  AND s1.owner_level = s2.owner_level 
                  AND (s1.owner_id = s2.owner_id OR (s1.owner_id IS NULL AND s2.owner_id IS NULL))");

    $nationalStockCount = $pdo->query("SELECT COUNT(*) FROM AssetStock WHERE owner_level = 'National' AND owner_id IS NULL")->fetchColumn();
    if ($nationalStockCount < 20) {
        $catalogItems = $pdo->query("SELECT catalog_item_id, sku FROM AssetCatalogItem")->fetchAll();
        $stockMap = [
            'SKU-AV-01' => 4,    // Professional PA Sound System (Deficit)
            'SKU-SP-01' => 12,   // Cricket Tournament Kit (Low stock)
            'SKU-AV-02' => 3,    // Heavy-duty Laser Projector (Deficit)
            'SKU-SP-02' => 145,  // Multi-Sport Volleyball Set (Optimal)
            'SKU-CL-01' => 28,   // Commercial Floor Scrubber (Optimal)
            'SKU-AV-03' => 14,   // Digital Cordless Microphones (Deficit)
            'SKU-OF-01' => 84,   // Ergonomic Office Workstations (Optimal)
            'SKU-CL-03' => 62,   // Industrial Vacuum Units (Optimal)
            'SKU-SP-03' => 45,   // Football Training Set (Optimal)
            'SKU-SP-04' => 8,    // Table Tennis Full Set (Low stock)
            'SKU-SP-05' => 18,   // Athletics Pack (Optimal)
            'SKU-AV-04' => 20,   // Portable Stage Lighting (Optimal)
            'SKU-AV-05' => 15,   // Multi-Channel Audio Mixer (Optimal)
            'SKU-CL-02' => 30,   // Industrial Pressure Washer (Optimal)
            'SKU-CL-04' => 22,   // Portable Fogging Machine (Optimal)
            'SKU-CL-05' => 40,   // Waste Management Station (Optimal)
            'SKU-OF-02' => 12,   // Executive Conference Table (Optimal)
            'SKU-OF-03' => 16,   // Multi-Function Printer (Optimal)
            'SKU-OF-04' => 9,    // Fireproof Safe (Low stock)
            'SKU-OF-05' => 14,   // High-Speed Document Scanner (Optimal)
        ];

        foreach ($catalogItems as $cItem) {
            $qty = $stockMap[$cItem->sku] ?? 20;
            $exists = $pdo->prepare("SELECT stock_id FROM AssetStock WHERE catalog_item_id = ? AND owner_level = 'National' AND owner_id IS NULL");
            $exists->execute([$cItem->catalog_item_id]);
            $sId = $exists->fetchColumn();

            if ($sId) {
                $pdo->prepare("UPDATE AssetStock SET quantity = ? WHERE stock_id = ?")->execute([$qty, $sId]);
            } else {
                $pdo->prepare("INSERT INTO AssetStock (catalog_item_id, owner_level, owner_id, quantity) VALUES (?, 'National', NULL, ?)")->execute([$cItem->catalog_item_id, $qty]);
            }
        }
        echo "Seeded National warehouse stock in `AssetStock`.\n";
    }

    // ============================================
    // 7. Seed Initial Zonal Stock for Sri Lankan Zones
    // ============================================
    $zonalStockCount = $pdo->query("SELECT COUNT(*) FROM AssetStock WHERE owner_level = 'Zonal'")->fetchColumn();
    if ($zonalStockCount == 0) {
        $zones = $pdo->query("SELECT zonal_id, zonal_name FROM Zone")->fetchAll();
        $catalogItems = $pdo->query("SELECT catalog_item_id, sku FROM AssetCatalogItem")->fetchAll();

        $stmtZoneStock = $pdo->prepare("INSERT INTO AssetStock (catalog_item_id, owner_level, owner_id, quantity, allocated_quantity)
            VALUES (?, 'Zonal', ?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), allocated_quantity = VALUES(allocated_quantity)");

        foreach ($zones as $zone) {
            foreach ($catalogItems as $cItem) {
                // Vary quantities per zone to generate realistic deficit & optimal states
                $isCentralOrWestern = in_array($zone->zonal_id, [1, 2]);
                $alloc = $isCentralOrWestern ? 5 : 2;
                $avail = ($cItem->sku === 'SKU-AV-01' && $zone->zonal_id == 2) ? 0 : ($isCentralOrWestern ? 4 : 2);

                $stmtZoneStock->execute([$cItem->catalog_item_id, $zone->zonal_id, $avail, $alloc]);
            }
        }
        echo "Seeded initial Zonal stock for all zones in `AssetStock`.\n";
    }

    echo "\n=== Manage Assets Migration Completed Successfully ===\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
