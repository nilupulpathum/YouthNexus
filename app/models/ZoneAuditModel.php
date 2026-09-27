<?php

/**
 * Zonal finance review (D11). Division report rollups from Division
 * ledgers plus a flag lifecycle over RedFlag rows attached to the zone's
 * open review audit: Open -> ClarificationRequested -> Resolved, with
 * NYSC escalation marking escalated_at.
 */
class ZoneAuditModel extends Model {

    public function getZone(int $zonalId) {
        return $this->single(
            "SELECT zonal_id, zonal_name FROM Zone WHERE zonal_id = ? LIMIT 1",
            [$zonalId]
        );
    }

    /**
     * The zone's open review audit (created on demand).
     */    public function ensureZoneReview(int $zonalId, int $userId) {
        $audit = $this->single(
            "SELECT * FROM Audit
             WHERE scope_level = 'Zonal' AND scope_id = ? AND locked = 0
               AND audit_status IN ('Pending', 'InProgress', 'ClarificationRequested')
             ORDER BY audit_id DESC LIMIT 1",
            [$zonalId]
        );
        if ($audit) {
            return $audit;
        }
        $this->query(
            "INSERT INTO Audit (financial_year, scope_level, scope_id, audit_status, initiated_by, audit_type)
             VALUES (YEAR(CURDATE()), 'Zonal', ?, 'InProgress', ?, 'Monthly')",
            [$zonalId, $userId]
        );
        $id = (int) $this->single("SELECT LAST_INSERT_ID() AS id")->id;
        return $this->single("SELECT * FROM Audit WHERE audit_id = ? LIMIT 1", [$id]);
    }

    public function getDivisionReports(int $zonalId): array {
        $rows = $this->resultSet(
            "SELECT d.division_id, d.division_name,
                    COALESCE(SUM(CASE WHEN le.type = 'Income' AND le.status = 'Approved' THEN le.amount ELSE 0 END), 0) AS income,
                    COALESCE(SUM(CASE WHEN le.type = 'Expense' AND le.status = 'Approved' THEN le.amount ELSE 0 END), 0) AS expenses,
                    COALESCE(SUM(CASE WHEN rf.status IN ('Open', 'ClarificationRequested') AND rf.escalated_at IS NULL THEN 1 ELSE 0 END), 0) AS open_flags
             FROM Division d
             LEFT JOIN Ledger l ON l.owner_type = 'Division' AND l.owner_id = d.division_id
             LEFT JOIN LedgerEntry le ON le.ledger_id = l.ledger_id
             LEFT JOIN Audit a ON a.scope_level = 'Divisional' AND a.scope_id = d.division_id
             LEFT JOIN RedFlag rf ON rf.audit_id = a.audit_id
             WHERE d.zonal_id = ?
             GROUP BY d.division_id
             ORDER BY d.division_name",
            [$zonalId]
        );
        $reports = [];
        foreach ($rows as $r) {
            $income = (float) $r->income;
            $expenses = (float) $r->expenses;
            $reports[] = [
                'division_id' => (int) $r->division_id,
                'division' => $r->division_name ?? '',
                'income' => $income,
                'expenses' => $expenses,
                'balance' => $income - $expenses,
                'status' => ((int) $r->open_flags > 0) ? 'Attention needed' : 'Review complete',
            ];
        }
        return $reports;
    }

    public function getZoneFlags(int $zonalId): array {
        return $this->resultSet(
            "SELECT rf.*, COALESCE(d.division_name, dz.division_name) AS division_name
             FROM RedFlag rf
             INNER JOIN Audit a ON a.audit_id = rf.audit_id
             LEFT JOIN Division d ON d.division_id = rf.division_id
             LEFT JOIN Division dz ON dz.division_id = a.scope_id AND a.scope_level = 'Divisional'
             WHERE (a.scope_level = 'Zonal' AND a.scope_id = ?)
                OR (a.scope_level = 'Divisional' AND dz.zonal_id = ?)
             ORDER BY rf.flagged_at DESC",
            [$zonalId, $zonalId]
        );
    }

    public function raiseFlag(int $zonalId, int $userId, int $divisionId, string $type, string $reference, float $amount, string $reason): int {        $allowed = ['MissingReceipt', 'FundHoarding', 'HighVoidRate'];
        if (!in_array($type, $allowed, true)) {
            throw new InvalidArgumentException('Select a permitted discrepancy type.');
        }
        $division = $this->single(
            "SELECT division_id, division_name FROM Division
             WHERE division_id = ? AND zonal_id = ? LIMIT 1",
            [$divisionId, $zonalId]
        );
        if (!$division) {
            throw new InvalidArgumentException('Select a division in your zone.');
        }
        if (trim($reference) === '' || $amount <= 0) {
            throw new InvalidArgumentException('Provide a report reference and a positive amount.');
        }
        $reason = trim($reason);
        $len = function_exists('mb_strlen') ? mb_strlen($reason) : strlen($reason);
        if ($reason === '' || $len > 1000) {
            throw new InvalidArgumentException('Provide a discrepancy reason of 1000 characters or fewer.');
        }
        $audit = $this->ensureZoneReview($zonalId, $userId);
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare(
                "INSERT INTO RedFlag (audit_id, entry_id, division_id, flag_type, reference, amount, description, status)
                 VALUES (?, NULL, ?, ?, ?, ?, ?, 'Open')"
            );
            $insert->execute([(int) $audit->audit_id, $divisionId, $type, $reference, $amount, $reason]);
            $flagId = (int) $pdo->lastInsertId();
            $this->writeAudit($pdo, $userId, 'FLAG_DISCREPANCY', 'RedFlag', $flagId, "Flagged {$division->division_name}: {$reason}");
            $pdo->commit();
            return $flagId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function clarifyFlag(int $zonalId, int $flagId, int $userId, string $query): void {
        $flag = $this->getZoneFlag($zonalId, $flagId);
        if (!$flag || $flag->status === 'Resolved' || $flag->escalated_at !== null) {
            throw new InvalidArgumentException('Enter a clarification request for an open flag.');
        }
        $query = trim($query);
        $len = function_exists('mb_strlen') ? mb_strlen($query) : strlen($query);
        if ($query === '' || $len > 1000) {
            throw new InvalidArgumentException('Enter a clarification request of 1000 characters or fewer.');
        }
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE RedFlag SET status = 'ClarificationRequested', note = ? WHERE red_flag_id = ?")
                ->execute([$query, $flagId]);
            $this->notifyDivision($pdo, $zonalId, $flagId, 'A zonal clarification request needs your response.');
            $this->writeAudit($pdo, $userId, 'REQUEST_CLARIFICATION', 'RedFlag', $flagId, $query);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function resolveFlag(int $zonalId, int $flagId, int $userId, string $note): void {
        $flag = $this->getZoneFlag($zonalId, $flagId);
        if (!$flag || $flag->status === 'Resolved' || $flag->escalated_at !== null) {
            throw new InvalidArgumentException('Only an unresolved flag can be resolved.');
        }
        $note = trim($note);
        $len = function_exists('mb_strlen') ? mb_strlen($note) : strlen($note);
        if ($note === '' || $len > 1000) {
            throw new InvalidArgumentException('A resolution note is required.');
        }
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE RedFlag SET status = 'Resolved', note = ? WHERE red_flag_id = ?")
                ->execute([$note, $flagId]);
            $this->writeAudit($pdo, $userId, 'RESOLVE_FLAG', 'RedFlag', $flagId, $note);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function escalateFlag(int $zonalId, int $flagId, int $userId, string $reason): void {
        $flag = $this->getZoneFlag($zonalId, $flagId);
        if (!$flag || $flag->status === 'Resolved' || $flag->escalated_at !== null) {
            throw new InvalidArgumentException('Provide an escalation reason for an unresolved flag.');
        }
        $reason = trim($reason);
        $len = function_exists('mb_strlen') ? mb_strlen($reason) : strlen($reason);
        if ($reason === '' || $len > 1000) {
            throw new InvalidArgumentException('Provide an escalation reason of 1000 characters or fewer.');
        }
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE RedFlag SET escalated_at = NOW(), note = ? WHERE red_flag_id = ?")
                ->execute([$reason, $flagId]);
            $admins = $pdo->query("SELECT user_id FROM User WHERE role = 'NYSCAdministrator' AND status = 'Active'");
            $notify = $pdo->prepare(
                "INSERT INTO Notification
                    (recipient_id, type, message, related_entity_type, related_entity_id, read_status, created_at)
                 VALUES (?, 'RedFlag', ?, 'RedFlag', ?, 0, NOW())"
            );
            foreach ($admins->fetchAll() as $admin) {
                $notify->execute([(int) $admin->user_id, 'A zonal audit flag was escalated to NYSC.', $flagId]);
            }
            $this->writeAudit($pdo, $userId, 'ESCALATE_FLAG', 'RedFlag', $flagId, $reason);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function getZoneFlag(int $zonalId, int $flagId) {
        $flags = $this->getZoneFlags($zonalId);
        foreach ($flags as $flag) {
            if ((int) $flag->red_flag_id === $flagId) {
                return $flag;
            }
        }
        return false;
    }

    private function notifyDivision($pdo, int $zonalId, int $flagId, string $message): void {
        $recipients = $pdo->prepare(
            "SELECT user_id FROM User
             WHERE role IN ('DivisionalTreasurer', 'DivisionalCoordinator')
               AND division_id IN (SELECT division_id FROM Division WHERE zonal_id = ?)
               AND status = 'Active'"
        );
        $recipients->execute([$zonalId]);
        $notify = $pdo->prepare(
            "INSERT INTO Notification
                (recipient_id, type, message, related_entity_type, related_entity_id, read_status, created_at)
             VALUES (?, 'RedFlag', ?, 'RedFlag', ?, 0, NOW())"
        );
        foreach ($recipients->fetchAll() as $recipient) {
            $notify->execute([(int) $recipient->user_id, $message, $flagId]);
        }
    }

    private function writeAudit($pdo, int $userId, string $action, string $entity, int $targetId, string $details): void {
        $stmt = $pdo->prepare("INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $entity, $targetId, substr($details, 0, 500)]);
    }
}
