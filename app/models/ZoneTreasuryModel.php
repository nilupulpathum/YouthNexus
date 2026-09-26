<?php

/**
 * Zonal treasurer workspace (D12): asset inventory, zone ledger and
 * division void decisions, all scoped to the treasurer's zone.
 */
class ZoneTreasuryModel extends Model {

    public function getDivisions(int $zonalId): array {
        return $this->resultSet(
            "SELECT division_id, division_name FROM Division
             WHERE zonal_id = ? ORDER BY division_name",
            [$zonalId]
        );
    }

    // ------------------------------------------------------------------
    // ASSETS (owner_level = 'Zonal')
    // ------------------------------------------------------------------

    public function getCatalog(): array {
        return $this->resultSet(
            "SELECT catalog_item_id, category, item_name, sku, unit
             FROM AssetCatalogItem ORDER BY category, item_name"
        );
    }

    public function getInventory(int $zonalId): array {
        return $this->resultSet(
            "SELECT s.stock_id, s.catalog_item_id, s.quantity, s.updated_at,
                    c.category, c.item_name, c.sku, c.unit
             FROM AssetStock s
             INNER JOIN AssetCatalogItem c ON c.catalog_item_id = s.catalog_item_id
             WHERE s.owner_level = 'Zonal' AND s.owner_id = ?
             ORDER BY c.category, c.item_name",
            [$zonalId]
        );
    }

    public function getAssetSummary(int $zonalId): array {
        $stock = $this->single(
            "SELECT COALESCE(SUM(quantity), 0) AS total_units, COUNT(*) AS item_types
             FROM AssetStock WHERE owner_level = 'Zonal' AND owner_id = ? AND quantity > 0",
            [$zonalId]
        );
        return [
            'units' => (int) ($stock->total_units ?? 0),
            'items' => (int) ($stock->item_types ?? 0),
        ];
    }

    public function addStock(int $zonalId, int $catalogItemId, int $quantity, int $userId, string $notes): void {
        if ($quantity < 1 || !$this->single("SELECT catalog_item_id FROM AssetCatalogItem WHERE catalog_item_id = ?", [$catalogItemId])) {
            throw new InvalidArgumentException('Select a valid asset and enter a positive quantity.');
        }
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO AssetStock (catalog_item_id, owner_level, owner_id, quantity, allocated_quantity)
                 VALUES (?, 'Zonal', ?, ?, 0)
                 ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), updated_at = CURRENT_TIMESTAMP"
            );
            $stmt->execute([$catalogItemId, $zonalId, $quantity]);
            $this->writeAudit($pdo, $userId, 'ZonalStockAdded', 'AssetStock', $catalogItemId,
                "Recorded {$quantity} unit(s) in zonal inventory. " . $notes);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function transferCustody(int $zonalId, int $catalogItemId, string $custodian, string $note, int $userId): void {
        $stock = $this->single(
            "SELECT stock_id FROM AssetStock
             WHERE catalog_item_id = ? AND owner_level = 'Zonal' AND owner_id = ? AND quantity > 0 LIMIT 1",
            [$catalogItemId, $zonalId]
        );
        if (!$stock) {
            throw new InvalidArgumentException('This item is not available in the zonal inventory.');
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
                 VALUES (?, 'Zonal', ?, 'Zonal', ?, 1, CURDATE(), ?, 'Completed', ?)"
            );
            $stmt->execute([$catalogItemId, $zonalId, $zonalId, $userId, "Custody: {$custodian}. {$note}"]);
            $this->writeAudit($pdo, $userId, 'ZonalCustodyTransfer', 'AssetTransfer',
                (int) $pdo->lastInsertId(), "Custody of item {$catalogItemId} to {$custodian}. {$note}");
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    // ------------------------------------------------------------------
    // LEDGER (owner_type = 'Zone')
    // ------------------------------------------------------------------

    public function ensureZoneLedger(int $zonalId) {
        $ledger = $this->single(
            "SELECT * FROM Ledger WHERE owner_type = 'Zone' AND owner_id = ? LIMIT 1",
            [$zonalId]
        );
        if ($ledger) {
            return $ledger;
        }
        try {
            $this->query(
                "INSERT INTO Ledger (owner_type, owner_level, owner_id, current_balance, status)
                 VALUES ('Zone', 'Zonal', ?, 0.00, 'Active')",
                [$zonalId]
            );
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() !== '23000') {
                throw $exception;
            }
        }
        return $this->single(
            "SELECT * FROM Ledger WHERE owner_type = 'Zone' AND owner_id = ? LIMIT 1",
            [$zonalId]
        );
    }

    public function getLedgerSummary(int $ledgerId): array {
        $row = $this->single(
            "SELECT l.current_balance,
                    COALESCE(SUM(CASE WHEN le.type = 'Income' AND le.status = 'Approved' THEN le.amount ELSE 0 END), 0) AS income_total,
                    COALESCE(SUM(CASE WHEN le.type = 'Expense' AND le.status = 'Approved' THEN le.amount ELSE 0 END), 0) AS expense_total
             FROM Ledger l LEFT JOIN LedgerEntry le ON le.ledger_id = l.ledger_id
             WHERE l.ledger_id = ?",
            [$ledgerId]
        );
        return [
            'balance' => (float) ($row->current_balance ?? 0),
            'income' => (float) ($row->income_total ?? 0),
            'expenses' => (float) ($row->expense_total ?? 0),
        ];
    }

    public function getLedgerEntries(int $ledgerId): array {
        return $this->resultSet(
            "SELECT le.* FROM LedgerEntry le
             WHERE le.ledger_id = ?
             ORDER BY le.date ASC, le.entry_id ASC",
            [$ledgerId]
        );
    }

    public function logTransaction(int $ledgerId, int $userId, array $data): int {
        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0 || !in_array($data['type'] ?? '', ['Income', 'Expense'], true)) {
            throw new InvalidArgumentException('Enter a positive amount and a valid type.');
        }
        if (trim($data['description'] ?? '') === '' || trim($data['date'] ?? '') === '') {
            throw new InvalidArgumentException('Description and date are required.');
        }
        if (empty($data['attachment_url'])) {
            throw new InvalidArgumentException('A receipt upload is mandatory.');
        }
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(
                "INSERT INTO LedgerEntry
                    (ledger_id, amount, type, category, description, attachment_url,
                     status, reconciled, created_by, date, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, 'Approved', 0, ?, ?, NOW())"
            );
            $statement->execute([
                $ledgerId, $amount, $data['type'], $data['category'] ?? 'General',
                $data['description'], $data['attachment_url'], $userId, $data['date'],
            ]);
            $entryId = (int) $pdo->lastInsertId();
            $delta = $data['type'] === 'Income' ? $amount : -$amount;
            $balance = $pdo->prepare("UPDATE Ledger SET current_balance = current_balance + ? WHERE ledger_id = ? AND status = 'Active'");
            $balance->execute([$delta, $ledgerId]);
            if ($balance->rowCount() !== 1) {
                throw new RuntimeException('The zone ledger is not active.');
            }
            $audit = $pdo->prepare("INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details) VALUES (?, 'LOG_TRANSACTION', 'LedgerEntry', ?, ?)");
            $audit->execute([$userId, $entryId, substr($data['description'], 0, 200)]);
            $pdo->commit();
            return $entryId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    // ------------------------------------------------------------------
    // VOIDS (Division -> Zonal, decided by the zonal treasurer)
    // ------------------------------------------------------------------

    public function getVoidRequests(int $zonalId): array {
        return $this->resultSet(
            "SELECT vr.*, d.division_name AS division,
                    le.description, le.amount, le.date AS entry_date,
                    COALESCE(fa.reference_no, CONCAT('LDG-', LPAD(le.entry_id, 4, '0'))) AS reference,
                    CONCAT_WS(' ', u.first_name, u.last_name) AS requester_name
             FROM VoidRequest vr
             INNER JOIN LedgerEntry le ON le.entry_id = vr.entry_id
             INNER JOIN Ledger l ON l.ledger_id = le.ledger_id AND l.owner_type = 'Division'
             INNER JOIN Division d ON d.division_id = l.owner_id AND d.zonal_id = ?
             LEFT JOIN FundAllocation fa ON fa.allocation_id = le.allocation_id
             LEFT JOIN User u ON u.user_id = vr.requested_by
             WHERE vr.scope_direction = 'DivisionToZonal'
             ORDER BY (vr.status = 'Pending') DESC, vr.requested_at DESC",
            [$zonalId]
        );
    }

    public function decideVoid(int $zonalId, int $requestId, int $decidedBy, string $decision, string $remarks): void {
        if (!in_array($decision, ['approve', 'reject'], true)) {
            throw new InvalidArgumentException('Invalid decision.');
        }
        $remarks = trim($remarks);
        if ($remarks === '' || (function_exists('mb_strlen') ? mb_strlen($remarks) : strlen($remarks)) > 1000) {
            throw new InvalidArgumentException('A decision remark is required.');
        }
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $select = $pdo->prepare(
                "SELECT vr.void_request_id, vr.status AS request_status,
                        le.entry_id, le.status AS entry_status, le.amount, le.type,
                        l.ledger_id, l.status AS ledger_status
                 FROM VoidRequest vr
                 INNER JOIN LedgerEntry le ON le.entry_id = vr.entry_id
                 INNER JOIN Ledger l ON l.ledger_id = le.ledger_id AND l.owner_type = 'Division'
                 INNER JOIN Division d ON d.division_id = l.owner_id AND d.zonal_id = ?
                 WHERE vr.void_request_id = ? AND vr.scope_direction = 'DivisionToZonal'
                 FOR UPDATE"
            );
            $select->execute([$zonalId, $requestId]);
            $request = $select->fetch(PDO::FETCH_ASSOC);
            if (!$request || $request['request_status'] !== 'Pending') {
                throw new RuntimeException('This void request is no longer pending.');
            }
            if ($request['entry_status'] !== 'Approved') {
                throw new RuntimeException('The ledger entry is no longer eligible to be voided.');
            }
            if ($request['ledger_status'] !== 'Active') {
                throw new RuntimeException('The division ledger is not active.');
            }
            if ($decision === 'approve') {
                $entryUpdate = $pdo->prepare("UPDATE LedgerEntry SET status = 'Voided', reconciled = 0 WHERE entry_id = ? AND status = 'Approved'");
                $entryUpdate->execute([$request['entry_id']]);
                if ($entryUpdate->rowCount() !== 1) {
                    throw new RuntimeException('The ledger entry is no longer eligible to be voided.');
                }
                $delta = $request['type'] === 'Income' ? -(float) $request['amount'] : (float) $request['amount'];
                $pdo->prepare("UPDATE Ledger SET current_balance = current_balance + ? WHERE ledger_id = ?")
                    ->execute([$delta, $request['ledger_id']]);
            }
            $newStatus = $decision === 'approve' ? 'Approved' : 'Rejected';
            $pdo->prepare("UPDATE VoidRequest SET status = ?, remarks = ?, decided_by = ?, decided_at = NOW() WHERE void_request_id = ? AND status = 'Pending'")
                ->execute([$newStatus, $remarks, $decidedBy, $requestId]);
            $notify = $pdo->prepare(
                "INSERT INTO Notification (recipient_id, type, message, related_entity_type, related_entity_id, read_status, created_at)
                 SELECT requested_by, 'VoidRequest', ?, 'VoidRequest', ?, 0, NOW() FROM VoidRequest WHERE void_request_id = ?"
            );
            $notify->execute(["Your void request was " . strtolower($newStatus) . ".", $requestId, $requestId]);
            $audit = $pdo->prepare("INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details) VALUES (?, 'DECIDE_VOID', 'VoidRequest', ?, ?)");
            $audit->execute([$decidedBy, $requestId, substr($remarks, 0, 200)]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function writeAudit($pdo, int $userId, string $action, string $entity, int $targetId, string $details): void {
        $stmt = $pdo->prepare("INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $entity, $targetId, substr($details, 0, 500)]);
    }
}
