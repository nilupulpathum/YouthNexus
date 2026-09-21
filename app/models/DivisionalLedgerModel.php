<?php

class DivisionalLedgerModel extends Model {
    public function getDivision(int $divisionId) {
        return $this->single(
            'SELECT division_id, division_name FROM Division WHERE division_id = ? LIMIT 1',
            [$divisionId]
        );
    }

    public function ensureDivisionLedger(int $divisionId) {
        $ledger = $this->single(
            "SELECT * FROM Ledger WHERE owner_type = 'Division' AND owner_id = ? LIMIT 1",
            [$divisionId]
        );

        if ($ledger) {
            return $ledger;
        }

        try {
            $this->query(
                "INSERT INTO Ledger (owner_type, owner_level, owner_id, current_balance, status)
                 VALUES ('Division', 'Divisional', ?, 0.00, 'Active')",
                [$divisionId]
            );
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() !== '23000') {
                throw $exception;
            }
        }

        return $this->single(
            "SELECT * FROM Ledger WHERE owner_type = 'Division' AND owner_id = ? LIMIT 1",
            [$divisionId]
        );
    }

    public function getSummary(int $ledgerId): array {
        $row = $this->single(
            "SELECT
                l.current_balance,
                COALESCE(SUM(CASE WHEN le.type = 'Income' AND le.status = 'Approved'
                    AND YEAR(le.date) = YEAR(CURDATE()) THEN le.amount ELSE 0 END), 0) AS income_total,
                COALESCE(SUM(CASE WHEN le.type = 'Expense' AND le.status = 'Approved'
                    AND YEAR(le.date) = YEAR(CURDATE()) THEN le.amount ELSE 0 END), 0) AS expense_total,
                COALESCE(SUM(CASE WHEN le.status = 'Pending'
                    OR (le.type = 'Expense' AND le.status = 'Approved' AND le.attachment_url IS NULL)
                    THEN 1 ELSE 0 END), 0) AS review_count
             FROM Ledger l
             LEFT JOIN LedgerEntry le ON le.ledger_id = l.ledger_id
             WHERE l.ledger_id = ?
             GROUP BY l.ledger_id, l.current_balance",
            [$ledgerId]
        );

        return [
            'balance' => (float) ($row->current_balance ?? 0),
            'income' => (float) ($row->income_total ?? 0),
            'expenses' => (float) ($row->expense_total ?? 0),
            'review_count' => (int) ($row->review_count ?? 0),
        ];
    }

    public function getEntries(int $ledgerId): array {
        $rows = $this->resultSet(
            "SELECT le.*,
                    COALESCE(fa.reference_no, CONCAT('LDG-', LPAD(le.entry_id, 4, '0'))) AS reference_no,
                    EXISTS(
                        SELECT 1 FROM VoidRequest vr
                        WHERE vr.entry_id = le.entry_id AND vr.status = 'Pending'
                    ) AS has_pending_void
             FROM LedgerEntry le
             LEFT JOIN FundAllocation fa ON fa.allocation_id = le.allocation_id
             WHERE le.ledger_id = ?
             ORDER BY le.date ASC, le.entry_id ASC",
            [$ledgerId]
        );

        $approvedNet = 0.0;
        foreach ($rows as $row) {
            if ($row->status === 'Approved') {
                $approvedNet += $row->type === 'Income' ? (float) $row->amount : -(float) $row->amount;
            }
        }

        $ledger = $this->single('SELECT current_balance FROM Ledger WHERE ledger_id = ?', [$ledgerId]);
        $running = (float) ($ledger->current_balance ?? 0) - $approvedNet;
        foreach ($rows as $row) {
            if ($row->status === 'Approved') {
                $running += $row->type === 'Income' ? (float) $row->amount : -(float) $row->amount;
            }
            $row->running_balance = $running;
        }

        return array_reverse($rows);
    }

    public function getCategories(int $ledgerId): array {
        return $this->query(
            "SELECT DISTINCT category FROM LedgerEntry
             WHERE ledger_id = ? AND category IS NOT NULL AND category <> ''
             ORDER BY category",
            [$ledgerId]
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getEntry(int $ledgerId, int $entryId) {
        return $this->single(
            "SELECT le.*,
                    EXISTS(
                        SELECT 1 FROM VoidRequest vr
                        WHERE vr.entry_id = le.entry_id AND vr.status = 'Pending'
                    ) AS has_pending_void
             FROM LedgerEntry le
             WHERE le.ledger_id = ? AND le.entry_id = ?
             LIMIT 1",
            [$ledgerId, $entryId]
        );
    }

    public function getReconciliation(int $ledgerId): array {
        $row = $this->single(
            "SELECT
                COUNT(*) AS total_entries,
                COALESCE(SUM(le.reconciled = 1), 0) AS reconciled_entries,
                COALESCE(SUM(le.attachment_url IS NOT NULL), 0) AS receipt_entries,
                COALESCE(SUM(CASE WHEN le.reconciled = 0 AND le.status = 'Approved'
                    THEN IF(le.type = 'Income', le.amount, -le.amount) ELSE 0 END), 0) AS difference
             FROM LedgerEntry le
             WHERE le.ledger_id = ? AND le.status = 'Approved'",
            [$ledgerId]
        );

        $total = (int) ($row->total_entries ?? 0);
        $reconciled = (int) ($row->reconciled_entries ?? 0);

        return [
            'total' => $total,
            'reconciled' => $reconciled,
            'receipts' => (int) ($row->receipt_entries ?? 0),
            'percent' => $total > 0 ? (int) round(($reconciled / $total) * 100) : 100,
            'difference' => (float) ($row->difference ?? 0),
        ];
    }

    public function getPendingActions(int $ledgerId): array {
        $missing = $this->single(
            "SELECT COUNT(*) AS total FROM LedgerEntry
             WHERE ledger_id = ? AND type = 'Expense' AND status = 'Approved'
               AND attachment_url IS NULL",
            [$ledgerId]
        );
        $voids = $this->single(
            "SELECT COUNT(*) AS total
             FROM VoidRequest vr
             INNER JOIN LedgerEntry le ON le.entry_id = vr.entry_id
             WHERE le.ledger_id = ? AND vr.status = 'Pending'",
            [$ledgerId]
        );

        return [
            'missing_receipts' => (int) ($missing->total ?? 0),
            'pending_voids' => (int) ($voids->total ?? 0),
        ];
    }

    public function createEntry(int $ledgerId, int $userId, array $data): int {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();

        try {
            $statement = $pdo->prepare(
                "INSERT INTO LedgerEntry
                    (ledger_id, amount, type, category, description, attachment_url,
                     status, reconciled, created_by, date, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, 'Approved', ?, ?, ?, NOW())"
            );
            $statement->execute([
                $ledgerId,
                $data['amount'],
                $data['type'],
                $data['category'],
                $data['description'],
                $data['attachment_url'],
                $data['reconciled'],
                $userId,
                $data['date'],
            ]);
            $entryId = (int) $pdo->lastInsertId();

            $delta = $data['type'] === 'Income' ? (float) $data['amount'] : -(float) $data['amount'];
            $balance = $pdo->prepare(
                "UPDATE Ledger SET current_balance = current_balance + ?
                 WHERE ledger_id = ? AND status = 'Active'"
            );
            $balance->execute([$delta, $ledgerId]);
            if ($balance->rowCount() !== 1) {
                throw new RuntimeException('The division ledger is not active.');
            }

            $pdo->commit();
            return $entryId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function updateEntry(int $ledgerId, int $entryId, array $data): ?string {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();

        try {
            $select = $pdo->prepare(
                "SELECT le.*,
                        EXISTS(
                            SELECT 1 FROM VoidRequest vr
                            WHERE vr.entry_id = le.entry_id AND vr.status = 'Pending'
                        ) AS has_pending_void
                 FROM LedgerEntry le
                 WHERE le.ledger_id = ? AND le.entry_id = ?
                 FOR UPDATE"
            );
            $select->execute([$ledgerId, $entryId]);
            $entry = $select->fetch();

            if (!$entry || $entry->status !== 'Approved' || (int) $entry->has_pending_void === 1) {
                throw new RuntimeException('This transaction cannot be edited.');
            }

            $oldDelta = $entry->type === 'Income' ? (float) $entry->amount : -(float) $entry->amount;
            $newDelta = $data['type'] === 'Income' ? (float) $data['amount'] : -(float) $data['amount'];

            $update = $pdo->prepare(
                "UPDATE LedgerEntry
                 SET amount = ?, type = ?, category = ?, description = ?, attachment_url = ?, date = ?, reconciled = 0
                 WHERE ledger_id = ? AND entry_id = ? AND status = 'Approved'"
            );
            $update->execute([
                $data['amount'],
                $data['type'],
                $data['category'],
                $data['description'],
                $data['attachment_url'],
                $data['date'],
                $ledgerId,
                $entryId,
            ]);

            $balanceDifference = $newDelta - $oldDelta;
            if (abs($balanceDifference) > 0.00001) {
                $balance = $pdo->prepare(
                    "UPDATE Ledger SET current_balance = current_balance + ?
                     WHERE ledger_id = ? AND status = 'Active'"
                );
                $balance->execute([$balanceDifference, $ledgerId]);
                if ($balance->rowCount() !== 1) {
                    throw new RuntimeException('The division ledger is not active.');
                }
            }

            $pdo->commit();
            return $entry->attachment_url ?: null;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function setReconciled(int $ledgerId, int $entryId, bool $reconciled): bool {
        $statement = $this->query(
            "UPDATE LedgerEntry SET reconciled = ?
             WHERE entry_id = ? AND ledger_id = ? AND status = 'Approved'",
            [$reconciled ? 1 : 0, $entryId, $ledgerId]
        );
        return $statement->rowCount() === 1;
    }
}
