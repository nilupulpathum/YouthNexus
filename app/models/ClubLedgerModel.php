<?php

/**
 * Club-scoped general ledger (D5). Same Ledger/LedgerEntry/VoidRequest
 * tables as the divisional flow with owner_type = 'Club'. Void requests
 * go Club -> Division and are decided on the divisional side.
 */
class ClubLedgerModel extends Model {

    public function ensureClubLedger(int $clubId) {
        $ledger = $this->single(
            "SELECT * FROM Ledger WHERE owner_type = 'Club' AND owner_id = ? LIMIT 1",
            [$clubId]
        );
        if ($ledger) {
            return $ledger;
        }
        try {
            $this->query(
                "INSERT INTO Ledger (owner_type, owner_level, owner_id, current_balance, status)
                 VALUES ('Club', 'Club', ?, 0.00, 'Active')",
                [$clubId]
            );
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() !== '23000') {
                throw $exception;
            }
        }
        return $this->single(
            "SELECT * FROM Ledger WHERE owner_type = 'Club' AND owner_id = ? LIMIT 1",
            [$clubId]
        );
    }

    public function getSummary(int $ledgerId): array {
        $row = $this->single(
            "SELECT
                l.current_balance,
                COALESCE(SUM(CASE WHEN le.type = 'Income' AND le.status = 'Approved'
                    THEN le.amount ELSE 0 END), 0) AS income_total,
                COALESCE(SUM(CASE WHEN le.type = 'Expense' AND le.status = 'Approved'
                    THEN le.amount ELSE 0 END), 0) AS expense_total
             FROM Ledger l
             LEFT JOIN LedgerEntry le ON le.ledger_id = l.ledger_id
             WHERE l.ledger_id = ?",
            [$ledgerId]
        );
        return [
            'balance' => (float) ($row->current_balance ?? 0),
            'income'  => (float) ($row->income_total ?? 0),
            'expenses' => (float) ($row->expense_total ?? 0),
        ];
    }

    public function getPendingVoidCount(int $ledgerId): int {
        $row = $this->single(
            "SELECT COUNT(*) AS open_voids FROM VoidRequest vr
             INNER JOIN LedgerEntry le ON le.entry_id = vr.entry_id
             WHERE le.ledger_id = ? AND vr.status = 'Pending'",
            [$ledgerId]
        );
        return (int) ($row->open_voids ?? 0);
    }

    public function getEntries(int $ledgerId): array {
        return $this->resultSet(
            "SELECT le.*,
                    (vr.void_request_id IS NOT NULL) AS has_pending_void
             FROM LedgerEntry le
             LEFT JOIN VoidRequest vr ON vr.entry_id = le.entry_id AND vr.status = 'Pending'
             WHERE le.ledger_id = ?
             ORDER BY le.date ASC, le.entry_id ASC",
            [$ledgerId]
        );
    }

    public function createEntry(int $ledgerId, int $userId, array $data): int {
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
            $balance = $pdo->prepare(
                "UPDATE Ledger SET current_balance = current_balance + ?
                 WHERE ledger_id = ? AND status = 'Active'"
            );
            $balance->execute([$delta, $ledgerId]);
            if ($balance->rowCount() !== 1) {
                throw new RuntimeException('The club ledger is not active.');
            }
            $audit = $pdo->prepare(
                "INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details)
                 VALUES (?, 'LOG_TRANSACTION', 'LedgerEntry', ?, ?)"
            );
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

    /**
     * Treasurer requests a void; decided on the divisional side.
     * Returns the new void_request_id. Throws on scope/duplicate problems.
     */
    public function requestVoid(int $clubId, int $entryId, int $requestedBy, int $requestedTo, string $reason): int {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A reason is required for void requests.');
        }
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $entry = $pdo->prepare(
                "SELECT le.entry_id FROM LedgerEntry le
                 INNER JOIN Ledger l ON l.ledger_id = le.ledger_id
                 WHERE le.entry_id = ? AND l.owner_type = 'Club' AND l.owner_id = ?
                   AND le.status = 'Approved' LIMIT 1"
            );
            $entry->execute([$entryId, $clubId]);
            if (!$entry->fetch()) {
                throw new InvalidArgumentException('Entry not found in your club ledger.');
            }
            $dup = $pdo->prepare(
                "SELECT void_request_id FROM VoidRequest
                 WHERE entry_id = ? AND status = 'Pending' LIMIT 1"
            );
            $dup->execute([$entryId]);
            if ($dup->fetch()) {
                throw new InvalidArgumentException('A void request is already pending for this entry.');
            }
            $insert = $pdo->prepare(
                "INSERT INTO VoidRequest
                    (entry_id, requested_by, requested_to, scope_direction, reason, status, requested_at)
                 VALUES (?, ?, ?, 'ClubToDivision', ?, 'Pending', NOW())"
            );
            $insert->execute([$entryId, $requestedBy, $requestedTo, $reason]);
            $requestId = (int) $pdo->lastInsertId();
            $notify = $pdo->prepare(
                "INSERT INTO Notification
                    (recipient_id, type, message, related_entity_type, related_entity_id, read_status, created_at)
                 VALUES (?, 'VoidRequest', ?, 'VoidRequest', ?, 0, NOW())"
            );
            $notify->execute([$requestedTo, 'A club void request needs your decision.', $requestId]);
            $audit = $pdo->prepare(
                "INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details)
                 VALUES (?, 'REQUEST_VOID', 'LedgerEntry', ?, ?)"
            );
            $audit->execute([$requestedBy, $entryId, substr($reason, 0, 200)]);
            $pdo->commit();
            return $requestId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
