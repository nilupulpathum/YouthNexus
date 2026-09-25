<?php

class DivisionalVoidRequestModel extends Model {
    public function getRecipient(int $divisionId) {
        return $this->single(
            "SELECT u.user_id, u.first_name, u.last_name, u.email, z.zonal_id, z.zonal_name
             FROM Division d
             INNER JOIN Zone z ON z.zonal_id = d.zonal_id
             INNER JOIN User u ON u.zonal_id = z.zonal_id
                AND u.role = 'ZonalTreasurer' AND u.status = 'Active'
             WHERE d.division_id = ?
             ORDER BY u.user_id
             LIMIT 1",
            [$divisionId]
        );
    }

    public function getSummary(int $divisionId): array {
        $row = $this->single(
            "SELECT
                COALESCE(SUM(vr.requested_by IS NOT NULL AND
                    YEAR(vr.requested_at) = YEAR(CURDATE()) AND
                    MONTH(vr.requested_at) = MONTH(CURDATE())), 0) AS sent_this_month,
                COALESCE(SUM(vr.status = 'Pending'), 0) AS pending_total,
                COALESCE(SUM(vr.status = 'Approved'), 0) AS approved_total
             FROM VoidRequest vr
             INNER JOIN LedgerEntry le ON le.entry_id = vr.entry_id
             INNER JOIN Ledger l ON l.ledger_id = le.ledger_id
             WHERE l.owner_type = 'Division' AND l.owner_id = ?
               AND vr.scope_direction = 'DivisionToZonal'",
            [$divisionId]
        );

        return [
            'sent_this_month' => (int) ($row->sent_this_month ?? 0),
            'pending' => (int) ($row->pending_total ?? 0),
            'approved' => (int) ($row->approved_total ?? 0),
        ];
    }

    public function getEligibleEntries(int $divisionId): array {
        return $this->resultSet(
            "SELECT le.entry_id, le.amount, le.type, le.description, le.category, le.date,
                    COALESCE(fa.reference_no, CONCAT('LDG-', LPAD(le.entry_id, 4, '0'))) AS reference_no
             FROM LedgerEntry le
             INNER JOIN Ledger l ON l.ledger_id = le.ledger_id
             LEFT JOIN FundAllocation fa ON fa.allocation_id = le.allocation_id
             WHERE l.owner_type = 'Division' AND l.owner_id = ?
               AND l.status = 'Active' AND le.status = 'Approved'
               AND NOT EXISTS (
                   SELECT 1 FROM VoidRequest vr
                   WHERE vr.entry_id = le.entry_id AND vr.status = 'Pending'
               )
             ORDER BY le.date DESC, le.entry_id DESC",
            [$divisionId]
        );
    }

    public function getRequests(int $divisionId): array {
        return $this->resultSet(
            "SELECT vr.*, le.amount, le.type, le.description, le.date AS entry_date,
                    COALESCE(fa.reference_no, CONCAT('LDG-', LPAD(le.entry_id, 4, '0'))) AS reference_no,
                    CONCAT_WS(' ', recipient.first_name, recipient.last_name) AS recipient_name,
                    z.zonal_name,
                    CONCAT_WS(' ', decision_user.first_name, decision_user.last_name) AS decided_by_name
             FROM VoidRequest vr
             INNER JOIN LedgerEntry le ON le.entry_id = vr.entry_id
             INNER JOIN Ledger l ON l.ledger_id = le.ledger_id
             LEFT JOIN FundAllocation fa ON fa.allocation_id = le.allocation_id
             INNER JOIN User recipient ON recipient.user_id = vr.requested_to
             LEFT JOIN Zone z ON z.zonal_id = recipient.zonal_id
             LEFT JOIN User decision_user ON decision_user.user_id = vr.decided_by
             WHERE l.owner_type = 'Division' AND l.owner_id = ?
               AND vr.scope_direction = 'DivisionToZonal'
             ORDER BY vr.requested_at DESC, vr.void_request_id DESC",
            [$divisionId]
        );
    }

    public function createRequest(
        int $divisionId,
        int $entryId,
        int $requestedBy,
        int $requestedTo,
        string $reason
    ): int {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();

        try {
            $entryStatement = $pdo->prepare(
                "SELECT le.entry_id, le.status,
                        COALESCE(fa.reference_no, CONCAT('LDG-', LPAD(le.entry_id, 4, '0'))) AS reference_no
                 FROM LedgerEntry le
                 INNER JOIN Ledger l ON l.ledger_id = le.ledger_id
                 LEFT JOIN FundAllocation fa ON fa.allocation_id = le.allocation_id
                 WHERE le.entry_id = ? AND l.owner_type = 'Division' AND l.owner_id = ?
                   AND l.status = 'Active'
                 FOR UPDATE"
            );
            $entryStatement->execute([$entryId, $divisionId]);
            $entry = $entryStatement->fetch();
            if (!$entry || $entry->status !== 'Approved') {
                throw new RuntimeException('Select an approved entry from your division ledger.');
            }

            $recipientStatement = $pdo->prepare(
                "SELECT u.user_id
                 FROM Division d
                 INNER JOIN User u ON u.zonal_id = d.zonal_id
                    AND u.role = 'ZonalTreasurer' AND u.status = 'Active'
                 WHERE d.division_id = ? AND u.user_id = ?
                 LIMIT 1 FOR UPDATE"
            );
            $recipientStatement->execute([$divisionId, $requestedTo]);
            if (!$recipientStatement->fetch()) {
                throw new RuntimeException('The Zonal Treasurer is not available.');
            }

            $pendingStatement = $pdo->prepare(
                "SELECT void_request_id FROM VoidRequest
                 WHERE entry_id = ? AND status = 'Pending'
                 LIMIT 1 FOR UPDATE"
            );
            $pendingStatement->execute([$entryId]);
            if ($pendingStatement->fetch()) {
                throw new RuntimeException('A void request is already pending for this entry.');
            }

            $insert = $pdo->prepare(
                "INSERT INTO VoidRequest
                    (entry_id, requested_by, requested_to, scope_direction, reason, status, requested_at)
                 VALUES (?, ?, ?, 'DivisionToZonal', ?, 'Pending', NOW())"
            );
            $insert->execute([$entryId, $requestedBy, $requestedTo, $reason]);
            $requestId = (int) $pdo->lastInsertId();

            $notification = $pdo->prepare(
                "INSERT INTO Notification
                    (recipient_id, type, message, related_entity_type, related_entity_id, read_status, created_at)
                 VALUES (?, 'VoidRequest', ?, 'VoidRequest', ?, 0, NOW())"
            );
            $notification->execute([
                $requestedTo,
                'A divisional ledger void request requires review for entry ' . $entry->reference_no . '.',
                $requestId,
            ]);

            $pdo->commit();
            return $requestId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function withdrawRequest(int $divisionId, int $requestId, int $userId): void {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();

        try {
            $select = $pdo->prepare(
                "SELECT vr.void_request_id, vr.status, vr.requested_to,
                        COALESCE(fa.reference_no, CONCAT('LDG-', LPAD(le.entry_id, 4, '0'))) AS reference_no
                 FROM VoidRequest vr
                 INNER JOIN LedgerEntry le ON le.entry_id = vr.entry_id
                 INNER JOIN Ledger l ON l.ledger_id = le.ledger_id
                 LEFT JOIN FundAllocation fa ON fa.allocation_id = le.allocation_id
                 WHERE vr.void_request_id = ? AND vr.requested_by = ?
                   AND vr.scope_direction = 'DivisionToZonal'
                   AND l.owner_type = 'Division' AND l.owner_id = ?
                 FOR UPDATE"
            );
            $select->execute([$requestId, $userId, $divisionId]);
            $request = $select->fetch();
            if (!$request || $request->status !== 'Pending') {
                throw new RuntimeException('Only a pending void request can be withdrawn.');
            }

            $update = $pdo->prepare(
                "UPDATE VoidRequest
                 SET status = 'Withdrawn', decided_at = NOW(), decided_by = ?
                 WHERE void_request_id = ? AND status = 'Pending'"
            );
            $update->execute([$userId, $requestId]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('The void request is no longer pending.');
            }

            $notification = $pdo->prepare(
                "INSERT INTO Notification
                    (recipient_id, type, message, related_entity_type, related_entity_id, read_status, created_at)
                 VALUES (?, 'VoidRequest', ?, 'VoidRequest', ?, 0, NOW())"
            );
            $notification->execute([
                $request->requested_to,
                'The void request for entry ' . $request->reference_no . ' was withdrawn.',
                $requestId,
            ]);

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
