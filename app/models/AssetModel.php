<?php

/**
 * AssetModel
 *
 * Data layer for the National Asset Management & Logistics module.
 * Implements dual-table transactions for stock replenishment and zonal distributions.
 */
class AssetModel extends Model {

    /**
     * Get all active zones from database.
     */
    public function getAllZones() {
        return $this->resultSet("SELECT zonal_id, zonal_name FROM Zone ORDER BY zonal_id ASC");
    }

    /**
     * Get distinct catalog categories.
     */
    public function getCategories() {
        return [
            'Sports & Recreation',
            'Event & A/V Equipment',
            'Community & Cleaning',
            'Official & Office'
        ];
    }

    /**
     * Get all catalog items, optionally filtered.
     */
    public function getCatalogItems($category = null, $search = null) {
        $sql = "SELECT * FROM AssetCatalogItem WHERE 1=1";
        $params = [];

        if (!empty($category) && $category !== 'All Categories') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        if (!empty($search)) {
            $sql .= " AND (item_name LIKE ? OR sku LIKE ? OR specifications LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY category ASC, item_name ASC";
        return $this->resultSet($sql, $params);
    }

    /**
     * Get single catalog item by ID.
     */
    public function getCatalogItemById($catalogItemId) {
        return $this->single("SELECT * FROM AssetCatalogItem WHERE catalog_item_id = ?", [$catalogItemId]);
    }

    /**
     * Get national warehouse inventory with status determination.
     */
    public function getNationalInventory($filters = []) {
        $category = $filters['category'] ?? null;
        $search   = $filters['search'] ?? null;

        $sql = "SELECT 
                    c.catalog_item_id,
                    c.category,
                    c.item_name,
                    c.sku,
                    c.specifications,
                    c.unit,
                    c.national_low_stock_threshold,
                    c.zonal_required_threshold,
                    COALESCE(s.quantity, 0) AS quantity,
                    COALESCE(s.allocated_quantity, 0) AS allocated_quantity,
                    s.updated_at
                FROM AssetCatalogItem c
                LEFT JOIN AssetStock s 
                    ON c.catalog_item_id = s.catalog_item_id 
                    AND s.owner_level = 'National'
                    AND s.owner_id IS NULL
                WHERE 1=1";
        $params = [];

        if (!empty($category) && $category !== 'All Categories') {
            $sql .= " AND c.category = ?";
            $params[] = $category;
        }

        if (!empty($search)) {
            $sql .= " AND (c.item_name LIKE ? OR c.sku LIKE ? OR c.specifications LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY c.catalog_item_id ASC";
        $rows = $this->resultSet($sql, $params);

        foreach ($rows as $row) {
            $qty = (int)$row->quantity;
            $threshold = (int)$row->national_low_stock_threshold;

            if ($qty <= (int)ceil($threshold * 0.4)) {
                $row->status = 'Deficit';
            } elseif ($qty < $threshold) {
                $row->status = 'Low Stock';
            } else {
                $row->status = 'Optimal';
            }
        }

        return $rows;
    }

    /**
     * Get inventory allocated to a specific Zone with status determination.
     */
    public function getZoneInventory($zoneId, $filters = []) {
        $category = $filters['category'] ?? null;
        $search   = $filters['search'] ?? null;

        $sql = "SELECT 
                    c.catalog_item_id,
                    c.category,
                    c.item_name,
                    c.sku,
                    c.specifications,
                    c.unit,
                    c.national_low_stock_threshold,
                    c.zonal_required_threshold,
                    COALESCE(s.quantity, 0) AS available_quantity,
                    COALESCE(s.allocated_quantity, 0) AS allocated_quantity,
                    s.updated_at
                FROM AssetCatalogItem c
                LEFT JOIN AssetStock s 
                    ON c.catalog_item_id = s.catalog_item_id 
                    AND s.owner_level = 'Zonal' 
                    AND s.owner_id = ?
                WHERE 1=1";
        $params = [(int)$zoneId];

        if (!empty($category) && $category !== 'All Categories') {
            $sql .= " AND c.category = ?";
            $params[] = $category;
        }

        if (!empty($search)) {
            $sql .= " AND (c.item_name LIKE ? OR c.sku LIKE ? OR c.specifications LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY c.catalog_item_id ASC";
        $rows = $this->resultSet($sql, $params);

        foreach ($rows as $row) {
            $available = (int)$row->available_quantity;
            $reqThreshold = (int)$row->zonal_required_threshold;

            if ($available == 0 || $available < (int)ceil($reqThreshold * 0.5)) {
                $row->status = 'Deficit';
            } elseif ($available < $reqThreshold) {
                $row->status = 'Low Stock';
            } else {
                $row->status = 'Optimal';
            }
        }

        return $rows;
    }

    /**
     * Calculate global warehouse & zonal inventory statistics.
     */
    public function getInventoryStats() {
        // Total items in national stock
        $totalStock = (int)$this->single("SELECT COALESCE(SUM(quantity), 0) as total FROM AssetStock WHERE owner_level = 'National'")->total;

        // Total distributed to zones
        $totalDistributed = (int)$this->single("SELECT COALESCE(SUM(quantity), 0) as total FROM AssetStock WHERE owner_level = 'Zonal'")->total;

        // Low stock SKUs buffer at national level
        $nationalItems = $this->getNationalInventory();
        $lowStockCount = 0;
        $lowStockNames = [];

        foreach ($nationalItems as $item) {
            if ($item->status === 'Deficit' || $item->status === 'Low Stock') {
                $lowStockCount++;
                if (count($lowStockNames) < 3) {
                    $lowStockNames[] = $item->item_name;
                }
            }
        }

        // Active zonal deficits across all zones
        $deficitSql = "SELECT COUNT(*) as deficit_count
                       FROM AssetCatalogItem c
                       CROSS JOIN Zone z
                       LEFT JOIN AssetStock s 
                           ON c.catalog_item_id = s.catalog_item_id 
                           AND s.owner_level = 'Zonal' 
                           AND s.owner_id = z.zonal_id
                       WHERE COALESCE(s.quantity, 0) < c.zonal_required_threshold";
        $activeDeficits = (int)$this->single($deficitSql)->deficit_count;

        // Priority deficit zones
        $priorityZonesSql = "SELECT z.zonal_name, COUNT(*) as gaps
                             FROM AssetCatalogItem c
                             CROSS JOIN Zone z
                             LEFT JOIN AssetStock s 
                                 ON c.catalog_item_id = s.catalog_item_id 
                                 AND s.owner_level = 'Zonal' 
                                 AND s.owner_id = z.zonal_id
                             WHERE COALESCE(s.quantity, 0) < c.zonal_required_threshold
                             GROUP BY z.zonal_id, z.zonal_name
                             ORDER BY gaps DESC
                             LIMIT 3";
        $priorityZones = $this->resultSet($priorityZonesSql);
        $priorityZoneNames = array_map(fn($pz) => preg_replace('/\s+Province|\s+Zone/i', '', $pz->zonal_name), $priorityZones);

        return [
            'total_stock'          => $totalStock,
            'total_distributed'    => $totalDistributed,
            'low_stock_count'      => $lowStockCount,
            'low_stock_names'      => $lowStockNames,
            'active_deficits'      => $activeDeficits,
            'priority_zone_names'  => $priorityZoneNames,
            'catalog_count'        => count($nationalItems)
        ];
    }

    /**
     * Fetch complete item context for interactive distribution / add modals.
     */
    public function getItemModalInfo($catalogItemId, $zoneId = null) {
        $item = $this->getCatalogItemById($catalogItemId);
        if (!$item) {
            return null;
        }

        // National warehouse available stock
        $natStockRow = $this->single("SELECT quantity FROM AssetStock WHERE catalog_item_id = ? AND owner_level = 'National'", [$catalogItemId]);
        $availableStock = $natStockRow ? (int)$natStockRow->quantity : 0;

        $zoneCurrent = 0;
        $zoneName = '';
        $zoneThreshold = (int)$item->zonal_required_threshold;

        if (!empty($zoneId)) {
            $zoneRow = $this->single("SELECT zonal_name FROM Zone WHERE zonal_id = ?", [$zoneId]);
            $zoneName = $zoneRow ? $zoneRow->zonal_name : 'Selected Zone';

            $zStockRow = $this->single("SELECT quantity FROM AssetStock WHERE catalog_item_id = ? AND owner_level = 'Zonal' AND owner_id = ?", [$catalogItemId, $zoneId]);
            $zoneCurrent = $zStockRow ? (int)$zStockRow->quantity : 0;
        }

        $recommended = max(0, $zoneThreshold - $zoneCurrent);

        return [
            'catalog_item_id' => (int)$item->catalog_item_id,
            'category'        => $item->category,
            'item_name'       => $item->item_name,
            'sku'             => $item->sku,
            'specifications'  => $item->specifications,
            'unit'            => $item->unit,
            'national_stock'  => $availableStock,
            'zone_id'         => $zoneId ? (int)$zoneId : null,
            'zone_name'       => $zoneName,
            'zone_current'    => $zoneCurrent,
            'zone_threshold'  => $zoneThreshold,
            'recommended'     => $recommended
        ];
    }

    /**
     * Add verified stock to the National Warehouse (Dual: Stock + AuditLog).
     */
    public function addNationalStock($catalogItemId, $quantity, $invoiceRef, $userId) {
        $quantity = (int)$quantity;
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Quantity must be at least 1 unit.");
        }

        $item = $this->getCatalogItemById($catalogItemId);
        if (!$item) {
            throw new RuntimeException("Catalog item not found.");
        }

        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Update National Stock
            $chk = $pdo->prepare("SELECT stock_id FROM AssetStock WHERE catalog_item_id = ? AND owner_level = 'National' AND owner_id IS NULL");
            $chk->execute([$catalogItemId]);
            $stockId = $chk->fetchColumn();

            if ($stockId) {
                $upd = $pdo->prepare("UPDATE AssetStock SET quantity = quantity + ?, updated_at = CURRENT_TIMESTAMP WHERE stock_id = ?");
                $upd->execute([$quantity, $stockId]);
            } else {
                $ins = $pdo->prepare("INSERT INTO AssetStock (catalog_item_id, owner_level, owner_id, quantity) VALUES (?, 'National', NULL, ?)");
                $ins->execute([$catalogItemId, $quantity]);
            }

            // 2. Log in AuditLog
            $auditStmt = $pdo->prepare("INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details)
                                        VALUES (?, 'StockAdded', 'AssetStock', ?, ?)");
            $details = sprintf("Procurement receipt added: %d %s of '%s' (SKU: %s). Invoice Ref: %s",
                $quantity, $item->unit, $item->item_name, $item->sku, $invoiceRef);
            $auditStmt->execute([$userId, $catalogItemId, $details]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Distribute assets from National Warehouse to a Regional Zonal Office.
     * Transaction: Dec National Stock -> Inc/Insert Zonal Stock -> Insert AssetTransfer -> Insert AuditLog -> Notify Coordinator.
     */
    public function distributeToZone($catalogItemId, $targetZoneId, $quantity, $userId, $notes = '') {
        $quantity = (int)$quantity;
        $targetZoneId = (int)$targetZoneId;

        if ($quantity <= 0) {
            throw new InvalidArgumentException("Distribution quantity must be greater than 0.");
        }

        $item = $this->getCatalogItemById($catalogItemId);
        if (!$item) {
            throw new RuntimeException("Catalog item does not exist.");
        }

        $zone = $this->single("SELECT zonal_id, zonal_name FROM Zone WHERE zonal_id = ?", [$targetZoneId]);
        if (!$zone) {
            throw new RuntimeException("Target Zone does not exist.");
        }

        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Check National available stock with locking
            $stockCheckStmt = $pdo->prepare("SELECT quantity FROM AssetStock WHERE catalog_item_id = ? AND owner_level = 'National' AND owner_id IS NULL FOR UPDATE");
            $stockCheckStmt->execute([$catalogItemId]);
            $currentNatStock = (int)$stockCheckStmt->fetchColumn();

            if ($currentNatStock < $quantity) {
                throw new RuntimeException("Insufficient warehouse stock. Available: {$currentNatStock} units, Requested: {$quantity} units.");
            }

            // 2. Decrement National Stock
            $decStmt = $pdo->prepare("UPDATE AssetStock SET quantity = quantity - ?, updated_at = CURRENT_TIMESTAMP 
                                     WHERE catalog_item_id = ? AND owner_level = 'National' AND owner_id IS NULL");
            $decStmt->execute([$quantity, $catalogItemId]);

            // 3. Increment or Insert Zonal Stock
            $zChk = $pdo->prepare("SELECT stock_id FROM AssetStock WHERE catalog_item_id = ? AND owner_level = 'Zonal' AND owner_id = ?");
            $zChk->execute([$catalogItemId, $targetZoneId]);
            $zStockId = $zChk->fetchColumn();

            if ($zStockId) {
                $zUpd = $pdo->prepare("UPDATE AssetStock SET quantity = quantity + ?, allocated_quantity = allocated_quantity + ?, updated_at = CURRENT_TIMESTAMP WHERE stock_id = ?");
                $zUpd->execute([$quantity, $quantity, $zStockId]);
            } else {
                $zIns = $pdo->prepare("INSERT INTO AssetStock (catalog_item_id, owner_level, owner_id, quantity, allocated_quantity) VALUES (?, 'Zonal', ?, ?, ?)");
                $zIns->execute([$catalogItemId, $targetZoneId, $quantity, $quantity]);
            }

            // 4. Insert AssetTransfer Record
            $trfStmt = $pdo->prepare("INSERT INTO AssetTransfer 
                (catalog_item_id, from_owner_level, from_owner_id, to_owner_level, to_owner_id, quantity, transfer_date, transferred_by, status, notes)
                VALUES (?, 'National', NULL, 'Zonal', ?, ?, CURDATE(), ?, 'Completed', ?)");
            $trfStmt->execute([$catalogItemId, $targetZoneId, $quantity, $userId, $notes]);
            $transferId = $pdo->lastInsertId();

            // 5. Insert AuditLog Record
            $auditStmt = $pdo->prepare("INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details)
                                        VALUES (?, 'AssetDistributed', 'AssetTransfer', ?, ?)");
            $details = sprintf("Dispatched %d %s of '%s' to %s. Transfer ID: #%d",
                $quantity, $item->unit, $item->item_name, $zone->zonal_name, $transferId);
            $auditStmt->execute([$userId, $transferId, $details]);

            // 6. Send Notification to Zonal Coordinator(s)
            $coordStmt = $pdo->prepare("SELECT user_id FROM User WHERE (role = 'ZonalCoordinator' OR role = 'ZonalTreasurer') AND (zonal_id = ? OR zonal_id IS NULL) AND status = 'Active'");
            $coordStmt->execute([$targetZoneId]);
            $coordinators = $coordStmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($coordinators)) {
                $notifStmt = $pdo->prepare("INSERT INTO Notification (recipient_id, type, message, related_entity_type, related_entity_id, read_status)
                                            VALUES (?, 'AssetReceived', ?, 'AssetTransfer', ?, 0)");
                $notifMsg = sprintf("You have received %d %s of '%s' (SKU: %s) from National Warehouse. Transfer Ref #TRF-%04d.",
                    $quantity, $item->unit, $item->item_name, $item->sku, $transferId);

                foreach ($coordinators as $coordId) {
                    $notifStmt->execute([$coordId, $notifMsg, $transferId]);
                }
            }

            $pdo->commit();
            return [
                'success'     => true,
                'transfer_id' => $transferId,
                'item_name'   => $item->item_name,
                'quantity'    => $quantity,
                'zone_name'   => $zone->zonal_name
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Get recent transfer log / distribution ledger.
     */
    public function getTransferLog($limit = 20) {
        $sql = "SELECT 
                    t.*,
                    c.item_name,
                    c.sku,
                    c.unit,
                    c.category,
                    z.zonal_name,
                    CONCAT(u.first_name, ' ', u.last_name) AS authorized_by_name
                FROM AssetTransfer t
                JOIN AssetCatalogItem c ON t.catalog_item_id = c.catalog_item_id
                JOIN Zone z ON t.to_owner_id = z.zonal_id
                JOIN User u ON t.transferred_by = u.user_id
                ORDER BY t.created_at DESC
                LIMIT ?";
        return $this->resultSet($sql, [(int)$limit]);
    }
}
