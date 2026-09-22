<?php

class DivisionalVoidApprovalModel extends Model {
    public function getSummary(int $divisionId): array {
        $row = $this->single(
            "SELECT
                COALESCE(SUM(vr.status = 'Pending'), 0) AS pending_total,
                COALESCE(SUM(vr.status = 'Approved' AND YEAR(vr.decided_at) = YEAR(CURDATE())), 0) AS approved_total,
                COALESCE(SUM(vr.status = 'Rejected' AND YEAR(vr.decided_at) = YEAR(CURDATE())), 0) AS rejected_total
             FROM VoidRequest vr
             INNER JOIN LedgerEntry le ON le.entry_id = vr.entry_id
             INNER JOIN Ledger l ON l.ledger_id = le.ledger_id AND l.owner_type = 'Club'
             INNER JOIN Club c ON c.club_id = l.owner_id
             WHERE c.division_id = ? AND vr.scope_direction = 'ClubToDivision'",
            [$divisionId]
        );

        return [
            'pending' => (int) ($row->pending_total ?? 0),
            'approved' => (int) ($row->approved_total ?? 0),
            'rejected' => (int) ($row->rejected_total ?? 0),
        ];
    }

    public function getClubs(int $divisionId): array {
        return $this->resultSet(
            "SELECT DISTINCT c.club_id, c.club_name
             FROM VoidRequest vr
             INNER JOIN LedgerEntry le ON le.entry_id = vr.entry_id
             INNER JOIN Ledger l ON l.ledger_id = le.ledger_id AND l.owner_type = 'Club'
             INNER JOIN Club c ON c.club_id = l.owner_id
             WHERE c.division_id = ? AND vr.scope_direction = 'ClubToDivision'
             ORDER BY c.club_name",
            [$divisionId]
        );
    }

    public function getPendingRequests(int $divisionId): array {
        return $this->getRequests($divisionId, true);
    }

    public function getDecidedRequests(int $divisionId): array {
        return $this->getRequests($divisionId, false);
    }

    private function getRequests(int $divisionId, bool $pending): array {
        $statusCondition = $pending ? "vr.status = 'Pending'" : "vr.status IN ('Approved','Rejected')";
        $order = $pending
            ? 'vr.requested_at ASC, vr.void_request_id ASC'
            : 'vr.decided_at DESC, vr.void_request_id DESC';

        return $this->resultSet(
            "SELECT vr.*, le.amount, le.type, le.description, le.category, le.date AS entry_date,
                    le.attachment_url, le.reconciled, le.created_at AS entry_created_at,
                    le.allocation_id, l.ledger_id, l.current_balance,
                    COALESCE(fa.reference_no, CONCAT('LDG-', LPAD(le.entry_id, 4, '0'))) AS reference_no,
                    c.club_id, c.club_name,
                    CONCAT_WS(' ', requester.first_name, requester.last_name) AS requester_name,
                    CONCAT_WS(' ', creator.first_name, creator.last_name) AS entry_creator_name,
                    CONCAT_WS(' ', decision_user.first_name, decision_user.last_name) AS decided_by_name
             FROM VoidRequest vr
             INNER JOIN LedgerEntry le ON le.entry_id = vr.entry_id
             INNER JOIN Ledger l ON l.ledger_id = le.ledger_id AND l.owner_type = 'Club'
             INNER JOIN Club c ON c.club_id = l.owner_id
             INNER JOIN User requester ON requester.user_id = vr.requested_by
             LEFT JOIN User creator ON creator.user_id = le.created_by
             LEFT JOIN User decision_user ON decision_user.user_id = vr.decided_by
             LEFT JOIN FundAllocation fa ON fa.allocation_id = le.allocation_id
             WHERE c.division_id = ? AND vr.scope_direction = 'ClubToDivision'
               AND $statusCondition
             ORDER BY $order",
            [$divisionId]
        );
    }

    public function getReviewEvidence(int $divisionId, array $pendingRequests): array {
        $evidence = [];
        foreach ($pendingRequests as $request) {
            $balance = (float) $request->current_balance;
            $amount = (float) $request->amount;
            $change = $request->type === 'Income' ? -$amount : $amount;
            $evidence[(int) $request->void_request_id] = [
                'category' => $request->category ?: 'Uncategorised',
                'entry_date' => $request->entry_date,
                'entry_created_at' => $request->entry_created_at,
                'entry_creator' => trim((string) $request->entry_creator_name) ?: 'Not recorded',
                'reconciled' => (int) $request->reconciled === 1,
                'allocation_linked' => !empty($request->allocation_id),
                'receipt_path' => $request->attachment_url ?: null,
                'receipt_entry_id' => (int) $request->entry_id,
                'current_balance' => $balance,
                'balance_change' => $change,
                'projected_balance' => $balance + $change,
                'nearby_entries' => [],
                'warnings' => [],
            ];

            if (empty($request->attachment_url)) {
                $evidence[(int) $request->void_request_id]['warnings'][] = 'No receipt is attached to this ledger entry.';
            }
            if ((int) $request->reconciled === 1) {
                $evidence[(int) $request->void_request_id]['warnings'][] = 'This entry has already been marked as reconciled.';
            }
            if (!empty($request->allocation_id)) {
                $evidence[(int) $request->void_request_id]['warnings'][] = 'This entry is linked to a fund allocation.';
            }
        }

        if (!$pendingRequests) {
            return $evidence;
        }

        $nearby = $this->resultSet(
            "SELECT vr.void_request_id, nearby.entry_id, nearby.date, nearby.description,
                    nearby.amount, nearby.type, nearby.status,
                    COALESCE(nearby_fa.reference_no, CONCAT('LDG-', LPAD(nearby.entry_id, 4, '0'))) AS reference_no,
                    CASE WHEN nearby.status IN ('Pending','Approved')
                              AND nearby.amount = target.amount
                              AND nearby.type = target.type
                              AND (nearby.date = target.date
                                   OR LOWER(TRIM(nearby.description)) = LOWER(TRIM(target.description)))
                         THEN 1 ELSE 0 END AS possible_duplicate,
                    CASE WHEN target.attachment_url IS NOT NULL AND target.attachment_url <> ''
                              AND nearby.attachment_url = target.attachment_url
                         THEN 1 ELSE 0 END AS same_receipt
             FROM VoidRequest vr
             INNER JOIN LedgerEntry target ON target.entry_id = vr.entry_id
             INNER JOIN Ledger l ON l.ledger_id = target.ledger_id AND l.owner_type = 'Club'
             INNER JOIN Club c ON c.club_id = l.owner_id
             INNER JOIN LedgerEntry nearby ON nearby.ledger_id = l.ledger_id
                AND nearby.entry_id <> target.entry_id
                AND nearby.date BETWEEN DATE_SUB(target.date, INTERVAL 3 DAY)
                                    AND DATE_ADD(target.date, INTERVAL 3 DAY)
             LEFT JOIN FundAllocation nearby_fa ON nearby_fa.allocation_id = nearby.allocation_id
             WHERE c.division_id = ? AND vr.scope_direction = 'ClubToDivision'
               AND vr.status = 'Pending'
             ORDER BY vr.void_request_id, ABS(DATEDIFF(nearby.date, target.date)), nearby.entry_id DESC",
            [$divisionId]
        );

        $duplicateCounts = [];
        $receiptMatchCounts = [];
        foreach ($nearby as $entry) {
            $requestId = (int) $entry->void_request_id;
            if (!isset($evidence[$requestId]) || count($evidence[$requestId]['nearby_entries']) >= 5) {
                continue;
            }
            $isDuplicate = (int) $entry->possible_duplicate === 1;
            $sameReceipt = (int) $entry->same_receipt === 1;
            $evidence[$requestId]['nearby_entries'][] = [
                'reference' => $entry->reference_no,
                'date' => $entry->date,
                'description' => $entry->description,
                'amount' => (float) $entry->amount,
                'type' => $entry->type,
                'status' => $entry->status,
                'possible_duplicate' => $isDuplicate,
                'same_receipt' => $sameReceipt,
            ];
            if ($isDuplicate) {
                $duplicateCounts[$requestId] = ($duplicateCounts[$requestId] ?? 0) + 1;
            }
            if ($sameReceipt) {
                $receiptMatchCounts[$requestId] = ($receiptMatchCounts[$requestId] ?? 0) + 1;
            }
        }

        foreach ($duplicateCounts as $requestId => $count) {
            $evidence[$requestId]['warnings'][] = $count === 1
                ? 'One nearby entry may be a duplicate.'
                : $count . ' nearby entries may be duplicates.';
        }
        foreach ($receiptMatchCounts as $requestId => $count) {
            $evidence[$requestId]['warnings'][] = $count === 1
                ? 'The attached receipt is also used by one nearby entry.'
                : 'The attached receipt is also used by ' . $count . ' nearby entries.';
        }

        return $evidence;
    }

    public function decide(
        int $divisionId,
        int $requestId,
        int $decidedBy,
        string $decision,
        ?string $remarks
    ): void {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();

        try {
            $select = $pdo->prepare(
                "SELECT vr.void_request_id, vr.status AS request_status, vr.requested_by,
                        le.entry_id, le.status AS entry_status, le.amount, le.type,
                        l.ledger_id, l.status AS ledger_status,
                        c.club_name,
                        COALESCE(fa.reference_no, CONCAT('LDG-', LPAD(le.entry_id, 4, '0'))) AS reference_no
                 FROM VoidRequest vr
                 INNER JOIN LedgerEntry le ON le.entry_id = vr.entry_id
                 INNER JOIN Ledger l ON l.ledger_id = le.ledger_id AND l.owner_type = 'Club'
                 INNER JOIN Club c ON c.club_id = l.owner_id
                 INNER JOIN User requester ON requester.user_id = vr.requested_by
                    AND requester.club_id = c.club_id AND requester.role = 'ClubTreasurer'
                 INNER JOIN User recipient ON recipient.user_id = vr.requested_to
                    AND recipient.role = 'DivisionalTreasurer' AND recipient.division_id = c.division_id
                 LEFT JOIN FundAllocation fa ON fa.allocation_id = le.allocation_id
                 WHERE vr.void_request_id = ? AND vr.scope_direction = 'ClubToDivision'
                   AND c.division_id = ?
                 FOR UPDATE"
            );
            $select->execute([$requestId, $divisionId]);
            $request = $select->fetch();

            if (!$request || $request->request_status !== 'Pending') {
                throw new RuntimeException('This void request is no longer pending.');
            }
            if ($request->entry_status !== 'Approved') {
                throw new RuntimeException('The ledger entry is no longer eligible to be voided.');
            }
            if ($request->ledger_status !== 'Active') {
                throw new RuntimeException('The club ledger is not active.');
            }

            $newStatus = $decision === 'approve' ? 'Approved' : 'Rejected';
            if ($decision === 'approve') {
                $entryUpdate = $pdo->prepare(
                    "UPDATE LedgerEntry SET status = 'Voided', reconciled = 0
                     WHERE entry_id = ? AND status = 'Approved'"
                );
                $entryUpdate->execute([$request->entry_id]);
                if ($entryUpdate->rowCount() !== 1) {
                    throw new RuntimeException('The ledger entry is no longer eligible to be voided.');
                }

                $balanceChange = $request->type === 'Income'
                    ? -(float) $request->amount
                    : (float) $request->amount;
                $ledgerUpdate = $pdo->prepare(
                    "UPDATE Ledger SET current_balance = current_balance + ?
                     WHERE ledger_id = ? AND status = 'Active'"
                );
                $ledgerUpdate->execute([$balanceChange, $request->ledger_id]);
                if ($ledgerUpdate->rowCount() !== 1) {
                    throw new RuntimeException('The club ledger is not active.');
                }
            }

            $requestUpdate = $pdo->prepare(
                "UPDATE VoidRequest
                 SET status = ?, remarks = ?, decided_at = NOW(), decided_by = ?
                 WHERE void_request_id = ? AND status = 'Pending'"
            );
            $requestUpdate->execute([$newStatus, $remarks, $decidedBy, $requestId]);
            if ($requestUpdate->rowCount() !== 1) {
                throw new RuntimeException('This void request is no longer pending.');
            }

            $action = $decision === 'approve' ? 'approved' : 'rejected';
            $notification = $pdo->prepare(
                "INSERT INTO Notification
                    (recipient_id, type, message, related_entity_type, related_entity_id, read_status, created_at)
                 VALUES (?, 'VoidRequest', ?, 'VoidRequest', ?, 0, NOW())"
            );
            $notification->execute([
                $request->requested_by,
                'The void request for entry ' . $request->reference_no . ' was ' . $action . '.',
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
