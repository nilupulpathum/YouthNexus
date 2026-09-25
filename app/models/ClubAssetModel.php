<?php

/**
 * Club-scoped asset inventory (D4). Same tables as the divisional flow
 * (AssetCatalogItem / AssetStock / AssetTransfer / AssetRequest) with
 * owner_level = 'Club' and requester Club -> Divisional.
 */
class ClubAssetModel extends Model {

    public function getCatalog(): array {
        return $this->resultSet(
            "SELECT catalog_item_id, category, item_name, sku, unit
             FROM AssetCatalogItem ORDER BY category, item_name"
        );
    }

    public function getInventory(int $clubId): array {
        return $this->resultSet(
            "SELECT s.stock_id, s.catalog_item_id, s.quantity, s.updated_at,
                    c.category, c.item_name, c.sku, c.unit
             FROM AssetStock s
             INNER JOIN AssetCatalogItem c ON c.catalog_item_id = s.catalog_item_id
             WHERE s.owner_level = 'Club' AND s.owner_id = ?
             ORDER BY c.category, c.item_name",
            [$clubId]
        );
    }

    public function getSummary(int $clubId): array {
        $stock = $this->single(
            "SELECT COALESCE(SUM(quantity), 0) AS total_units, COUNT(*) AS item_types
             FROM AssetStock WHERE owner_level = 'Club' AND owner_id = ? AND quantity > 0",
            [$clubId]
        );
        $pending = $this->single(
            "SELECT COUNT(*) AS open_requests FROM AssetRequest
             WHERE requester_level = 'Club' AND requester_id = ? AND status = 'Pending'",
            [$clubId]
        );
        return [
            'units' => (int) ($stock->total_units ?? 0),
            'items' => (int) ($stock->item_types ?? 0),
            'open_requests' => (int) ($pending->open_requests ?? 0),
        ];
    }

    public function getRequests(int $clubId): array {
        return $this->resultSet(
            "SELECT ar.*, item.item_name, item.category, item.unit,
                    CONCAT_WS(' ', u.first_name, u.last_name) AS requester_name
             FROM AssetRequest ar
             INNER JOIN AssetCatalogItem item ON item.catalog_item_id = ar.catalog_item_id
             INNER JOIN User u ON u.user_id = ar.requested_by
             WHERE ar.requester_level = 'Club' AND ar.requester_id = ?
             ORDER BY ar.requested_at DESC",
            [$clubId]
        );
    }

    public function addStock(int $clubId, int $catalogItemId, int $quantity, int $userId, string $notes): void {
        if ($quantity < 1 || !$this->single("SELECT catalog_item_id FROM AssetCatalogItem WHERE catalog_item_id = ?", [$catalogItemId])) {
            throw new InvalidArgumentException('Select a valid asset and enter a positive quantity.');
        }
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO AssetStock (catalog_item_id, owner_level, owner_id, quantity, allocated_quantity)
                 VALUES (?, 'Club', ?, ?, 0)
                 ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), updated_at = CURRENT_TIMESTAMP"
            );
            $stmt->execute([$catalogItemId, $clubId, $quantity]);
            $this->writeAudit($pdo, $userId, 'ClubStockAdded', 'AssetStock', $catalogItemId,
                "Recorded {$quantity} unit(s) in club inventory. " . $notes);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function transferCustody(int $clubId, int $catalogItemId, string $custodian, string $date, string $note, int $userId): void {
        $stock = $this->single(
            "SELECT stock_id FROM AssetStock
             WHERE catalog_item_id = ? AND owner_level = 'Club' AND owner_id = ? AND quantity > 0 LIMIT 1",
            [$catalogItemId, $clubId]
        );
        if (!$stock) {
            throw new InvalidArgumentException('This item is not available in the club inventory.');
        }
        if (trim($custodian) === '' || trim($note) === '') {
            throw new InvalidArgumentException('Custodian and history note are required.');
        }
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO AssetTransfer (catalog_item_id, from_owner_level, from_owner_id,
                                            to_owner_level, to_owner_id, quantity, transfer_date,
                                            transferred_by, status, notes)
                 VALUES (?, 'Club', ?, 'Club', ?, 1, ?, ?, 'Completed', ?)"
            );
            $stmt->execute([$catalogItemId, $clubId, $clubId, $date, $userId, "Custody: {$custodian}. {$note}"]);
            $this->writeAudit($pdo, $userId, 'ClubCustodyTransfer', 'AssetTransfer',
                (int) $pdo->lastInsertId(), "Custody of item {$catalogItemId} to {$custodian}. {$note}");
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function requestFromDivision(int $clubId, int $divisionId, int $catalogItemId, int $quantity, int $userId, string $reason): void {
        if ($quantity < 1 || !$this->single("SELECT catalog_item_id FROM AssetCatalogItem WHERE catalog_item_id = ?", [$catalogItemId])) {
            throw new InvalidArgumentException('Select a valid asset and enter a positive quantity.');
        }
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A reason is required for division requests.');
        }
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO AssetRequest (catalog_item_id, requester_level, requester_id,
                                       requested_to_level, requested_to_id, scope_direction,
                                       quantity, reason, status, requested_by)
             VALUES (?, 'Club', ?, 'Divisional', ?, 'ClubToDivision', ?, ?, 'Pending', ?)"
        );
        $stmt->execute([$catalogItemId, $clubId, $divisionId, $quantity, $reason, $userId]);
        $this->writeAudit($pdo, $userId, 'ClubAssetRequest', 'AssetRequest',
            (int) $pdo->lastInsertId(), "Requested {$quantity} unit(s) from division. {$reason}");
    }

    private function writeAudit(PDO $pdo, int $userId, string $action, string $entity, int $targetId, string $details): void {
        $stmt = $pdo->prepare("INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $entity, $targetId, substr($details, 0, 500)]);
    }
}
