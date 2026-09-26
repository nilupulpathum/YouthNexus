<?php

class DivisionalAssetModel extends Model {
    public function getDivision(int $divisionId) {
        return $this->single(
            "SELECT d.division_id, d.division_name, d.zonal_id, z.zonal_name
             FROM Division d INNER JOIN Zone z ON z.zonal_id = d.zonal_id
             WHERE d.division_id = ?",
            [$divisionId]
        );
    }

    public function getCatalog(): array {
        return $this->resultSet(
            "SELECT catalog_item_id, category, item_name, sku, unit, specifications
             FROM AssetCatalogItem ORDER BY category, item_name"
        );
    }

    public function getClubs(int $divisionId): array {
        return $this->resultSet(
            "SELECT club_id, club_name FROM Club
             WHERE division_id = ? AND status = 'Active' ORDER BY club_name",
            [$divisionId]
        );
    }

    public function getInventory(int $divisionId): array {
        return $this->resultSet(
            "SELECT s.stock_id, s.catalog_item_id, s.quantity, s.allocated_quantity, s.updated_at,
                    c.category, c.item_name, c.sku, c.unit, c.specifications
             FROM AssetStock s
             INNER JOIN AssetCatalogItem c ON c.catalog_item_id = s.catalog_item_id
             WHERE s.owner_level = 'Divisional' AND s.owner_id = ?
             ORDER BY c.category, c.item_name",
            [$divisionId]
        );
    }

    public function getPendingClubRequests(int $divisionId): array {
        return $this->resultSet(
            "SELECT ar.*, c.club_name, item.item_name, item.category, item.unit,
                    COALESCE(stock.quantity, 0) AS available_quantity,
                    CONCAT_WS(' ', u.first_name, u.last_name) AS requester_name
             FROM AssetRequest ar
             INNER JOIN Club c ON c.club_id = ar.requester_id AND c.division_id = ?
             INNER JOIN AssetCatalogItem item ON item.catalog_item_id = ar.catalog_item_id
             INNER JOIN User u ON u.user_id = ar.requested_by AND u.club_id = c.club_id
                AND u.role IN ('ClubTreasurer','ClubPresident')
             LEFT JOIN AssetStock stock ON stock.catalog_item_id = ar.catalog_item_id
                AND stock.owner_level = 'Divisional' AND stock.owner_id = ?
             WHERE ar.scope_direction = 'ClubToDivision'
               AND ar.requester_level = 'Club'
               AND ar.requested_to_level = 'Divisional'
               AND ar.requested_to_id = ?
               AND ar.status = 'Pending'
             ORDER BY ar.requested_at ASC, ar.asset_request_id ASC",
            [$divisionId, $divisionId, $divisionId]
        );
    }

    public function getZonalRequests(int $divisionId): array {
        return $this->resultSet(
            "SELECT ar.*, item.item_name, item.category, item.unit,
                    CONCAT_WS(' ', decision_user.first_name, decision_user.last_name) AS decided_by_name
             FROM AssetRequest ar
             INNER JOIN AssetCatalogItem item ON item.catalog_item_id = ar.catalog_item_id
             LEFT JOIN User decision_user ON decision_user.user_id = ar.decided_by
             WHERE ar.scope_direction = 'DivisionToZonal'
               AND ar.requester_level = 'Divisional' AND ar.requester_id = ?
             ORDER BY ar.requested_at DESC, ar.asset_request_id DESC
             LIMIT 20",
            [$divisionId]
        );
    }

    public function getTransferHistory(int $divisionId): array {
        return $this->resultSet(
            "SELECT t.transfer_id, t.quantity, t.transfer_date, t.status, t.notes, t.created_at,
                    item.item_name, item.category, item.sku, item.unit,
                    c.club_id, c.club_name,
                    CONCAT_WS(' ', u.first_name, u.last_name) AS transferred_by_name
             FROM AssetTransfer t
             INNER JOIN AssetCatalogItem item ON item.catalog_item_id = t.catalog_item_id
             INNER JOIN Club c ON c.club_id = t.to_owner_id AND c.division_id = ?
             INNER JOIN User u ON u.user_id = t.transferred_by
             WHERE t.from_owner_level = 'Divisional'
               AND t.from_owner_id = ?
               AND t.to_owner_level = 'Club'
             ORDER BY t.created_at DESC, t.transfer_id DESC
             LIMIT 50",
            [$divisionId, $divisionId]
        );
    }

    public function getSummary(int $divisionId): array {
        $stock = $this->single(
            "SELECT COALESCE(SUM(quantity), 0) AS total_units, COUNT(*) AS item_types
             FROM AssetStock WHERE owner_level = 'Divisional' AND owner_id = ? AND quantity > 0",
            [$divisionId]
        );
        $pending = $this->single(
            "SELECT
                COALESCE(SUM(scope_direction = 'ClubToDivision' AND requested_to_id = ? AND status = 'Pending'), 0) AS club_pending,
                COALESCE(SUM(scope_direction = 'DivisionToZonal' AND requester_id = ? AND status = 'Pending'), 0) AS zonal_pending
             FROM AssetRequest
             WHERE (scope_direction = 'ClubToDivision' AND requested_to_id = ?)
                OR (scope_direction = 'DivisionToZonal' AND requester_id = ?)",
            [$divisionId, $divisionId, $divisionId, $divisionId]
        );
        return [
            'units' => (int) ($stock->total_units ?? 0),
            'items' => (int) ($stock->item_types ?? 0),
            'club_pending' => (int) ($pending->club_pending ?? 0),
            'zonal_pending' => (int) ($pending->zonal_pending ?? 0),
        ];
    }

    public function addStock(int $divisionId, int $catalogItemId, int $quantity, int $userId, string $notes): void {
        if ($quantity < 1 || !$this->single("SELECT catalog_item_id FROM AssetCatalogItem WHERE catalog_item_id = ?", [$catalogItemId])) {
            throw new InvalidArgumentException('Select a valid asset and enter a positive quantity.');
        }
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO AssetStock (catalog_item_id, owner_level, owner_id, quantity, allocated_quantity)
                 VALUES (?, 'Divisional', ?, ?, 0)
                 ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), updated_at = CURRENT_TIMESTAMP"
            );
            $stmt->execute([$catalogItemId, $divisionId, $quantity]);
            $this->writeAudit($pdo, $userId, 'DivisionalStockAdded', 'AssetStock', $catalogItemId,
                "Recorded {$quantity} unit(s) in divisional inventory. " . $notes);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function adjustStock(int $divisionId, int $catalogItemId, int $newQuantity, int $userId, string $reason): void {
        if ($newQuantity < 0 || trim($reason) === '') {
            throw new InvalidArgumentException('Enter a valid quantity and an adjustment reason.');
        }
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $select = $pdo->prepare("SELECT stock_id, quantity FROM AssetStock WHERE catalog_item_id = ? AND owner_level = 'Divisional' AND owner_id = ? FOR UPDATE");
            $select->execute([$catalogItemId, $divisionId]);
            $stock = $select->fetch();
            if (!$stock) throw new RuntimeException('The selected inventory record was not found.');
            $pdo->prepare("UPDATE AssetStock SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE stock_id = ?")
                ->execute([$newQuantity, $stock->stock_id]);
            $this->writeAudit($pdo, $userId, 'DivisionalStockAdjusted', 'AssetStock', (int) $stock->stock_id,
                "Quantity changed from {$stock->quantity} to {$newQuantity}. Reason: {$reason}");
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function transferToClub(int $divisionId, int $catalogItemId, int $clubId, int $quantity, int $userId, string $notes): void {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $club = $this->lockClub($pdo, $divisionId, $clubId);
            $transferId = $this->completeClubTransfer($pdo, $divisionId, $catalogItemId, $clubId, $quantity, $userId, $notes);
            $this->notifyClub($pdo, $clubId, 'AssetTransfer', "{$quantity} unit(s) were transferred to {$club->club_name}.", $transferId);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function decideClubRequest(int $divisionId, int $requestId, int $userId, string $decision, string $remarks): void {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "SELECT ar.*, c.club_name FROM AssetRequest ar
                 INNER JOIN Club c ON c.club_id = ar.requester_id AND c.division_id = ?
                 INNER JOIN User requester ON requester.user_id = ar.requested_by
                    AND requester.club_id = c.club_id AND requester.role IN ('ClubTreasurer','ClubPresident')
                 WHERE ar.asset_request_id = ? AND ar.scope_direction = 'ClubToDivision'
                   AND ar.requested_to_level = 'Divisional' AND ar.requested_to_id = ? FOR UPDATE"
            );
            $stmt->execute([$divisionId, $requestId, $divisionId]);
            $request = $stmt->fetch();
            if (!$request || $request->status !== 'Pending') throw new RuntimeException('This asset request is no longer pending.');
            if ($decision === 'approve') {
                $transferId = $this->completeClubTransfer($pdo, $divisionId, (int) $request->catalog_item_id, (int) $request->requester_id,
                    (int) $request->quantity, $userId, 'Approved request AR-' . str_pad((string) $requestId, 4, '0', STR_PAD_LEFT));
                $this->notifyClub($pdo, (int) $request->requester_id, 'AssetTransfer',
                    "{$request->quantity} unit(s) were allocated to {$request->club_name}.", $transferId);
            }
            $status = $decision === 'approve' ? 'Approved' : 'Rejected';
            $update = $pdo->prepare("UPDATE AssetRequest SET status = ?, remarks = ?, decided_by = ?, decided_at = NOW() WHERE asset_request_id = ? AND status = 'Pending'");
            $update->execute([$status, $remarks !== '' ? $remarks : null, $userId, $requestId]);
            if ($update->rowCount() !== 1) throw new RuntimeException('This asset request is no longer pending.');
            $notification = $pdo->prepare("INSERT INTO Notification (recipient_id, type, message, related_entity_type, related_entity_id, read_status) VALUES (?, 'AssetRequest', ?, 'AssetRequest', ?, 0)");
            $notification->execute([$request->requested_by, "Your asset request was " . strtolower($status) . '.', $requestId]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function requestFromZonal(int $divisionId, int $catalogItemId, int $quantity, int $userId, string $reason): void {
        if ($quantity < 1 || trim($reason) === '') throw new InvalidArgumentException('Select an asset, quantity, and reason.');
        $division = $this->getDivision($divisionId);
        if (!$division) throw new RuntimeException('The division could not be found.');
        $item = $this->single("SELECT catalog_item_id, item_name FROM AssetCatalogItem WHERE catalog_item_id = ?", [$catalogItemId]);
        if (!$item) throw new RuntimeException('The selected catalog item was not found.');
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare(
                "INSERT INTO AssetRequest (catalog_item_id, requester_level, requester_id, requested_to_level, requested_to_id, scope_direction, quantity, reason, requested_by)
                 VALUES (?, 'Divisional', ?, 'Zonal', ?, 'DivisionToZonal', ?, ?, ?)"
            );
            $insert->execute([$catalogItemId, $divisionId, $division->zonal_id, $quantity, $reason, $userId]);
            $requestId = (int) $pdo->lastInsertId();
            $users = $pdo->prepare("SELECT user_id FROM User WHERE zonal_id = ? AND role IN ('ZonalTreasurer','ZonalCoordinator') AND status = 'Active'");
            $users->execute([$division->zonal_id]);
            $notify = $pdo->prepare("INSERT INTO Notification (recipient_id, type, message, related_entity_type, related_entity_id, read_status) VALUES (?, 'AssetRequest', ?, 'AssetRequest', ?, 0)");
            foreach ($users->fetchAll(PDO::FETCH_COLUMN) as $recipientId) {
                $notify->execute([$recipientId, "{$division->division_name} requested {$quantity} unit(s) of {$item->item_name}.", $requestId]);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function withdrawZonalRequest(int $divisionId, int $requestId, int $userId, string $reason): void {
        $reason = trim($reason);
        if (strlen($reason) < 5 || strlen($reason) > 1000) {
            throw new InvalidArgumentException('Provide a withdrawal reason between 5 and 1000 characters.');
        }
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $update = $pdo->prepare(
                "UPDATE AssetRequest
                 SET status = 'Withdrawn', remarks = ?, decided_by = ?, decided_at = NOW()
                 WHERE asset_request_id = ? AND requester_level = 'Divisional'
                   AND requester_id = ? AND requested_by = ? AND scope_direction = 'DivisionToZonal'
                   AND status = 'Pending'"
            );
            $update->execute([$reason, $userId, $requestId, $divisionId, $userId]);
            if ($update->rowCount() !== 1) throw new RuntimeException('Only your pending zonal request can be withdrawn.');
            $this->writeAudit($pdo, $userId, 'DivisionalAssetRequestWithdrawn', 'AssetRequest', $requestId, $reason);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function retireStock(
        int $divisionId,
        int $catalogItemId,
        int $quantity,
        int $userId,
        string $actionType,
        string $reason
    ): void {
        if ($quantity < 1 || !in_array($actionType, ['Retired', 'WrittenOff'], true)) {
            throw new InvalidArgumentException('Select a valid retirement action and quantity.');
        }
        $reason = trim($reason);
        if (strlen($reason) < 5 || strlen($reason) > 1000) {
            throw new InvalidArgumentException('Provide a retirement reason between 5 and 1000 characters.');
        }
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $select = $pdo->prepare(
                "SELECT stock_id, quantity FROM AssetStock
                 WHERE catalog_item_id = ? AND owner_level = 'Divisional' AND owner_id = ? FOR UPDATE"
            );
            $select->execute([$catalogItemId, $divisionId]);
            $stock = $select->fetch();
            if (!$stock || (int) $stock->quantity < $quantity) {
                throw new RuntimeException('The retirement quantity exceeds available divisional stock.');
            }
            $pdo->prepare('UPDATE AssetStock SET quantity = quantity - ?, updated_at = CURRENT_TIMESTAMP WHERE stock_id = ?')
                ->execute([$quantity, $stock->stock_id]);
            $insert = $pdo->prepare(
                'INSERT INTO AssetRetirement (stock_id, quantity, action_type, reason, recorded_by) VALUES (?, ?, ?, ?, ?)'
            );
            $insert->execute([$stock->stock_id, $quantity, $actionType, $reason, $userId]);
            $retirementId = (int) $pdo->lastInsertId();
            $this->writeAudit($pdo, $userId, 'DivisionalAsset' . $actionType, 'AssetRetirement', $retirementId,
                "{$quantity} unit(s). {$reason}");
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    private function lockClub(PDO $pdo, int $divisionId, int $clubId) {
        $stmt = $pdo->prepare("SELECT club_id, club_name FROM Club WHERE club_id = ? AND division_id = ? AND status = 'Active' FOR UPDATE");
        $stmt->execute([$clubId, $divisionId]);
        $club = $stmt->fetch();
        if (!$club) throw new RuntimeException('Select an active club in your division.');
        return $club;
    }

    private function completeClubTransfer(PDO $pdo, int $divisionId, int $catalogItemId, int $clubId, int $quantity, int $userId, string $notes): int {
        if ($quantity < 1) throw new InvalidArgumentException('Transfer quantity must be positive.');
        $this->lockClub($pdo, $divisionId, $clubId);
        $source = $pdo->prepare("SELECT stock_id, quantity FROM AssetStock WHERE catalog_item_id = ? AND owner_level = 'Divisional' AND owner_id = ? FOR UPDATE");
        $source->execute([$catalogItemId, $divisionId]);
        $stock = $source->fetch();
        if (!$stock || (int) $stock->quantity < $quantity) throw new RuntimeException('There is not enough divisional stock for this transfer.');
        $pdo->prepare("UPDATE AssetStock SET quantity = quantity - ? WHERE stock_id = ?")->execute([$quantity, $stock->stock_id]);
        $pdo->prepare(
            "INSERT INTO AssetStock (catalog_item_id, owner_level, owner_id, quantity, allocated_quantity)
             VALUES (?, 'Club', ?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), allocated_quantity = allocated_quantity + VALUES(allocated_quantity), updated_at = CURRENT_TIMESTAMP"
        )->execute([$catalogItemId, $clubId, $quantity, $quantity]);
        $pdo->prepare(
            "INSERT INTO AssetTransfer (catalog_item_id, from_owner_level, from_owner_id, to_owner_level, to_owner_id, quantity, transfer_date, transferred_by, status, notes)
             VALUES (?, 'Divisional', ?, 'Club', ?, ?, CURDATE(), ?, 'Completed', ?)"
        )->execute([$catalogItemId, $divisionId, $clubId, $quantity, $userId, $notes]);
        $transferId = (int) $pdo->lastInsertId();
        $this->writeAudit($pdo, $userId, 'AssetTransferredToClub', 'AssetTransfer', $transferId,
            "Transferred {$quantity} unit(s) from division {$divisionId} to club {$clubId}.");
        return $transferId;
    }

    private function notifyClub(PDO $pdo, int $clubId, string $type, string $message, int $relatedId): void {
        $users = $pdo->prepare("SELECT user_id FROM User WHERE club_id = ? AND role IN ('ClubTreasurer','ClubPresident') AND status = 'Active'");
        $users->execute([$clubId]);
        $notify = $pdo->prepare("INSERT INTO Notification (recipient_id, type, message, related_entity_type, related_entity_id, read_status) VALUES (?, ?, ?, 'AssetTransfer', ?, 0)");
        foreach ($users->fetchAll(PDO::FETCH_COLUMN) as $recipientId) $notify->execute([$recipientId, $type, $message, $relatedId]);
    }

    private function writeAudit(PDO $pdo, int $userId, string $action, string $entity, int $targetId, string $details): void {
        $stmt = $pdo->prepare("INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $entity, $targetId, substr($details, 0, 500)]);
    }
}
