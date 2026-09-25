<?php

class DivisionalAuditModel extends Model {
    public function getQueue(int $divisionId): array {
        return $this->resultSet(
            "SELECT c.club_id, c.club_name, c.status AS club_status,
                    EXISTS(
                        SELECT 1 FROM Ledger cl
                        WHERE cl.owner_type = 'Club' AND cl.owner_id = c.club_id AND cl.status = 'Active'
                    ) AS has_active_ledger,
                    a.audit_id, a.audit_type, a.period_start, a.period_end,
                    a.audit_status, a.initiated_at, a.signed_off_at,
                    COALESCE(flags.open_flags, 0) AS open_flags
             FROM Club c
             LEFT JOIN Audit a ON a.audit_id = (
                 SELECT a2.audit_id FROM Audit a2
                 WHERE a2.scope_level = 'Club' AND a2.scope_id = c.club_id
                 ORDER BY a2.initiated_at DESC, a2.audit_id DESC LIMIT 1
             )
             LEFT JOIN (
                 SELECT audit_id, SUM(status <> 'Resolved') AS open_flags
                 FROM RedFlag GROUP BY audit_id
             ) flags ON flags.audit_id = a.audit_id
             WHERE c.division_id = ? AND c.status IN ('Active', 'Flagged')
             ORDER BY
                 CASE
                     WHEN a.audit_id IS NULL THEN 1
                     WHEN a.audit_status IN ('Pending','InProgress','Overdue','ClarificationRequested') THEN 2
                     ELSE 3
                 END,
                 c.club_name",
            [$divisionId]
        );
    }

    public function getSummary(int $divisionId): array {
        $pending = $this->single(
            "SELECT
                COALESCE(SUM(latest.audit_id IS NULL OR latest.audit_status IN ('Pending','InProgress','Overdue','ClarificationRequested')), 0) AS total
             FROM Club c
             LEFT JOIN Audit latest ON latest.audit_id = (
                 SELECT a2.audit_id FROM Audit a2
                 WHERE a2.scope_level = 'Club' AND a2.scope_id = c.club_id
                 ORDER BY a2.initiated_at DESC, a2.audit_id DESC LIMIT 1
             )
             WHERE c.division_id = ? AND c.status IN ('Active', 'Flagged')
               AND EXISTS (
                   SELECT 1 FROM Ledger cl
                   WHERE cl.owner_type = 'Club' AND cl.owner_id = c.club_id AND cl.status = 'Active'
               )",
            [$divisionId]
        );
        $completed = $this->single(
            "SELECT COUNT(*) AS total FROM Audit a
             INNER JOIN Club c ON a.scope_level = 'Club' AND a.scope_id = c.club_id
             WHERE c.division_id = ? AND a.audit_status = 'Completed'
               AND YEAR(a.signed_off_at) = YEAR(CURDATE())",
            [$divisionId]
        );
        $flagged = $this->single(
            "SELECT COUNT(DISTINCT a.audit_id) AS total
             FROM Audit a
             INNER JOIN Club c ON a.scope_level = 'Club' AND a.scope_id = c.club_id
             INNER JOIN RedFlag rf ON rf.audit_id = a.audit_id AND rf.status <> 'Resolved'
             WHERE c.division_id = ?",
            [$divisionId]
        );

        return [
            'pending' => (int) ($pending->total ?? 0),
            'completed' => (int) ($completed->total ?? 0),
            'flagged' => (int) ($flagged->total ?? 0),
        ];
    }

    public function getClubs(int $divisionId): array {
        return $this->resultSet(
            "SELECT c.club_id, c.club_name
             FROM Club c
             INNER JOIN Ledger l ON l.owner_type = 'Club' AND l.owner_id = c.club_id
             WHERE c.division_id = ? AND c.status IN ('Active', 'Flagged')
               AND l.status = 'Active'
             ORDER BY c.club_name",
            [$divisionId]
        );
    }

    public function startAudit(int $divisionId, int $userId, array $data): int {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $club = $this->lockClubLedger($pdo, $divisionId, (int) $data['club_id']);
            $duplicate = $pdo->prepare(
                "SELECT audit_id FROM Audit
                 WHERE scope_level = 'Club' AND scope_id = ?
                   AND audit_status IN ('Pending','InProgress','ClarificationRequested','Overdue')
                 LIMIT 1 FOR UPDATE"
            );
            $duplicate->execute([$club->club_id]);
            if ($duplicate->fetchColumn()) {
                throw new RuntimeException('This club already has an open audit.');
            }

            $totals = $this->calculateTotals($pdo, (int) $club->ledger_id, $data['period_start'], $data['period_end']);
            $insert = $pdo->prepare(
                "INSERT INTO Audit
                    (financial_year, scope_level, scope_id, opening_balance, total_income,
                     total_transfers_received, total_expenses, total_transfers_distributed,
                     expected_closing_balance, actual_closing_balance, math_check_status,
                     receipt_threshold_amount, hoarding_margin_pct, void_rate_threshold_pct,
                     audit_status, initiated_by, locked, audit_type, period_start, period_end)
                 VALUES (?, 'Club', ?, ?, ?, 0, ?, 0, ?, ?, ?, 5000, 80, 10,
                         'InProgress', ?, 0, ?, ?, ?)"
            );
            $insert->execute([
                (int) date('Y', strtotime($data['period_end'])),
                $club->club_id,
                $totals['opening'],
                $totals['income'],
                $totals['expenses'],
                $totals['expected'],
                $totals['actual'],
                $totals['math_status'],
                $userId,
                $data['audit_type'],
                $data['period_start'],
                $data['period_end'],
            ]);
            $auditId = (int) $pdo->lastInsertId();
            $this->syncMissingReceiptFlags($pdo, $auditId, (int) $club->ledger_id, $data['period_start'], $data['period_end']);
            $pdo->commit();
            return $auditId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function getAudit(int $divisionId, int $auditId) {
        $audit = $this->single(
            "SELECT a.*, c.club_name, c.club_id, l.ledger_id,
                    CONCAT(COALESCE(init.first_name, ''), ' ', COALESCE(init.last_name, '')) AS initiated_by_name,
                    CONCAT(COALESCE(signoff.first_name, ''), ' ', COALESCE(signoff.last_name, '')) AS signed_by_name
             FROM Audit a
             INNER JOIN Club c ON a.scope_level = 'Club' AND a.scope_id = c.club_id
             INNER JOIN Ledger l ON l.owner_type = 'Club' AND l.owner_id = c.club_id
             LEFT JOIN User init ON init.user_id = a.initiated_by
             LEFT JOIN User signoff ON signoff.user_id = a.signed_off_by
             WHERE a.audit_id = ? AND c.division_id = ?
             LIMIT 1",
            [$auditId, $divisionId]
        );
        if (!$audit) {
            return null;
        }
        $audit->difference = round((float) $audit->actual_closing_balance - (float) $audit->expected_closing_balance, 2);
        $audit->flags = $this->getFlags($divisionId, $auditId);
        return $audit;
    }

    public function getEntries(int $divisionId, int $auditId): array {
        return $this->resultSet(
            "SELECT le.*,
                    COALESCE(fa.reference_no, CONCAT('LDG-', LPAD(le.entry_id, 4, '0'))) AS reference_no
             FROM Audit a
             INNER JOIN Club c ON a.scope_level = 'Club' AND a.scope_id = c.club_id
             INNER JOIN Ledger l ON l.owner_type = 'Club' AND l.owner_id = c.club_id
             INNER JOIN LedgerEntry le ON le.ledger_id = l.ledger_id
             LEFT JOIN FundAllocation fa ON fa.allocation_id = le.allocation_id
             WHERE a.audit_id = ? AND c.division_id = ?
               AND le.date BETWEEN a.period_start AND a.period_end
             ORDER BY le.date DESC, le.entry_id DESC",
            [$auditId, $divisionId]
        );
    }

    public function getFlags(int $divisionId, int $auditId): array {
        return $this->resultSet(
            "SELECT rf.*, le.attachment_url, le.amount, le.description AS entry_description
             FROM RedFlag rf
             INNER JOIN Audit a ON a.audit_id = rf.audit_id
             INNER JOIN Club c ON a.scope_level = 'Club' AND a.scope_id = c.club_id
             LEFT JOIN LedgerEntry le ON le.entry_id = rf.entry_id
             WHERE rf.audit_id = ? AND c.division_id = ?
             ORDER BY rf.status = 'Resolved', rf.flagged_at DESC",
            [$auditId, $divisionId]
        );
    }

    public function refreshAudit(int $divisionId, int $auditId): void {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $audit = $this->lockAudit($pdo, $divisionId, $auditId);
            if ($audit->audit_status === 'Completed') {
                throw new RuntimeException('Completed audits cannot be recalculated.');
            }
            $totals = $this->calculateTotals($pdo, (int) $audit->ledger_id, $audit->period_start, $audit->period_end);
            $update = $pdo->prepare(
                "UPDATE Audit SET opening_balance = ?, total_income = ?, total_expenses = ?,
                    expected_closing_balance = ?, actual_closing_balance = ?, math_check_status = ?
                 WHERE audit_id = ?"
            );
            $update->execute([
                $totals['opening'], $totals['income'], $totals['expenses'],
                $totals['expected'], $totals['actual'], $totals['math_status'], $auditId,
            ]);
            $this->syncMissingReceiptFlags($pdo, $auditId, (int) $audit->ledger_id, $audit->period_start, $audit->period_end);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function sendAuditNote(int $divisionId, int $auditId, string $reason, string $notes): void {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $audit = $this->lockAudit($pdo, $divisionId, $auditId);
            if ($audit->audit_status === 'Completed') {
                throw new RuntimeException('Completed audits cannot receive audit notes.');
            }
            $recipient = $pdo->prepare(
                "SELECT user_id FROM User
                 WHERE club_id = ? AND role = 'ClubTreasurer' AND status = 'Active'
                 ORDER BY user_id LIMIT 1"
            );
            $recipient->execute([$audit->club_id]);
            $recipientId = $recipient->fetchColumn();
            if (!$recipientId) {
                throw new RuntimeException('The club does not have an active treasurer.');
            }

            $update = $pdo->prepare(
                "UPDATE Audit SET audit_status = 'ClarificationRequested',
                    clarification_reason = ?, auditor_notes = ? WHERE audit_id = ?"
            );
            $update->execute([$reason, $notes, $auditId]);
            $pdo->prepare(
                "UPDATE RedFlag SET status = 'ClarificationRequested'
                 WHERE audit_id = ? AND status = 'Open'"
            )->execute([$auditId]);
            $message = 'Audit clarification for ' . $audit->club_name . ': ' . $reason . '. ' . $notes;
            $message = function_exists('mb_substr') ? mb_substr($message, 0, 500) : substr($message, 0, 500);
            $pdo->prepare(
                "INSERT INTO Notification
                    (recipient_id, type, message, related_entity_type, related_entity_id, read_status, created_at)
                 VALUES (?, 'AuditClarification', ?, 'Audit', ?, 0, NOW())"
            )->execute([$recipientId, $message, $auditId]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function completeAudit(int $divisionId, int $auditId, int $userId, string $notes): void {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $audit = $this->lockAudit($pdo, $divisionId, $auditId);
            if ($audit->audit_status === 'Completed') {
                throw new RuntimeException('This audit is already completed.');
            }
            $totals = $this->calculateTotals($pdo, (int) $audit->ledger_id, $audit->period_start, $audit->period_end);
            $this->syncMissingReceiptFlags($pdo, $auditId, (int) $audit->ledger_id, $audit->period_start, $audit->period_end);
            $openFlags = $pdo->prepare("SELECT COUNT(*) FROM RedFlag WHERE audit_id = ? AND status <> 'Resolved'");
            $openFlags->execute([$auditId]);
            if (abs($totals['actual'] - $totals['expected']) >= 0.01 || (int) $openFlags->fetchColumn() > 0) {
                throw new RuntimeException('Resolve the audit difference and open flags before completion.');
            }
            $update = $pdo->prepare(
                "UPDATE Audit SET opening_balance = ?, total_income = ?, total_expenses = ?,
                    expected_closing_balance = ?, actual_closing_balance = ?, math_check_status = 'Passed',
                    audit_status = 'Completed', signed_off_by = ?, signed_off_at = NOW(),
                    locked = 1, auditor_notes = ? WHERE audit_id = ?"
            );
            $update->execute([
                $totals['opening'], $totals['income'], $totals['expenses'],
                $totals['expected'], $totals['actual'], $userId, $notes ?: null, $auditId,
            ]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function lockClubLedger(PDO $pdo, int $divisionId, int $clubId) {
        $statement = $pdo->prepare(
            "SELECT c.club_id, c.club_name, l.ledger_id
             FROM Club c INNER JOIN Ledger l ON l.owner_type = 'Club' AND l.owner_id = c.club_id
             WHERE c.club_id = ? AND c.division_id = ? AND c.status IN ('Active','Flagged')
               AND l.status = 'Active' FOR UPDATE"
        );
        $statement->execute([$clubId, $divisionId]);
        $club = $statement->fetch();
        if (!$club) {
            throw new RuntimeException('Select a club with an active ledger from your division.');
        }
        return $club;
    }

    private function lockAudit(PDO $pdo, int $divisionId, int $auditId) {
        $statement = $pdo->prepare(
            "SELECT a.*, c.club_id, c.club_name, l.ledger_id
             FROM Audit a
             INNER JOIN Club c ON a.scope_level = 'Club' AND a.scope_id = c.club_id
             INNER JOIN Ledger l ON l.owner_type = 'Club' AND l.owner_id = c.club_id
             WHERE a.audit_id = ? AND c.division_id = ? FOR UPDATE"
        );
        $statement->execute([$auditId, $divisionId]);
        $audit = $statement->fetch();
        if (!$audit) {
            throw new RuntimeException('The audit record was not found.');
        }
        return $audit;
    }

    private function calculateTotals(PDO $pdo, int $ledgerId, string $start, string $end): array {
        $openingQuery = $pdo->prepare(
            "SELECT COALESCE(SUM(CASE WHEN type = 'Income' THEN amount ELSE -amount END), 0)
             FROM LedgerEntry WHERE ledger_id = ? AND status = 'Approved' AND date < ?"
        );
        $openingQuery->execute([$ledgerId, $start]);
        $opening = (float) $openingQuery->fetchColumn();

        $period = $pdo->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN type = 'Income' AND status = 'Approved' THEN amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN type = 'Expense' AND status = 'Approved' THEN amount ELSE 0 END), 0) AS expenses
             FROM LedgerEntry WHERE ledger_id = ? AND date BETWEEN ? AND ?"
        );
        $period->execute([$ledgerId, $start, $end]);
        $totals = $period->fetch();
        $income = (float) ($totals->income ?? 0);
        $expenses = (float) ($totals->expenses ?? 0);
        $expected = $opening + $income - $expenses;

        $actualQuery = $pdo->prepare(
            "SELECT COALESCE(SUM(CASE WHEN type = 'Income' THEN amount ELSE -amount END), 0)
             FROM LedgerEntry WHERE ledger_id = ? AND status = 'Approved' AND date <= ?"
        );
        $actualQuery->execute([$ledgerId, $end]);
        $actual = (float) $actualQuery->fetchColumn();

        return [
            'opening' => $opening,
            'income' => $income,
            'expenses' => $expenses,
            'expected' => $expected,
            'actual' => $actual,
            'math_status' => abs($actual - $expected) < 0.01 ? 'Passed' : 'Mismatch',
        ];
    }

    private function syncMissingReceiptFlags(PDO $pdo, int $auditId, int $ledgerId, string $start, string $end): void {
        $pdo->prepare(
            "UPDATE RedFlag rf
             INNER JOIN LedgerEntry le ON le.entry_id = rf.entry_id
             SET rf.status = 'Resolved'
             WHERE rf.audit_id = ? AND rf.flag_type = 'MissingReceipt'
               AND le.attachment_url IS NOT NULL AND le.attachment_url <> ''"
        )->execute([$auditId]);

        $missing = $pdo->prepare(
            "SELECT le.entry_id, le.amount, le.description
             FROM LedgerEntry le
             WHERE le.ledger_id = ? AND le.type = 'Expense' AND le.status = 'Approved'
               AND le.amount >= 5000 AND (le.attachment_url IS NULL OR le.attachment_url = '')
               AND le.date BETWEEN ? AND ?"
        );
        $missing->execute([$ledgerId, $start, $end]);
        $exists = $pdo->prepare(
            "SELECT red_flag_id, status FROM RedFlag
             WHERE audit_id = ? AND entry_id = ? AND flag_type = 'MissingReceipt' LIMIT 1"
        );
        $reopen = $pdo->prepare("UPDATE RedFlag SET status = 'Open' WHERE red_flag_id = ? AND status = 'Resolved'");
        $insert = $pdo->prepare(
            "INSERT INTO RedFlag (audit_id, entry_id, flag_type, description, status, flagged_at)
             VALUES (?, ?, 'MissingReceipt', ?, 'Open', NOW())"
        );
        foreach ($missing->fetchAll() as $entry) {
            $exists->execute([$auditId, $entry->entry_id]);
            $existing = $exists->fetch();
            if (!$existing) {
                $description = 'Receipt required for ' . $entry->description . ' (Rs. ' . number_format((float) $entry->amount, 2) . ')';
                $insert->execute([$auditId, $entry->entry_id, $description]);
            } elseif ($existing->status === 'Resolved') {
                $reopen->execute([$existing->red_flag_id]);
            }
        }
    }
}
