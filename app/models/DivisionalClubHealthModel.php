<?php

class DivisionalClubHealthModel extends Model {
    protected const WINDOW_MONTHS = 6;
    protected const EVENT_TARGET = 6;
    protected const FINANCE_ENTRY_TARGET = 6;

    public function getDivision(int $divisionId) {
        return $this->single(
            "SELECT d.division_id, d.division_name, d.zonal_id, z.zonal_name
             FROM Division d INNER JOIN Zone z ON z.zonal_id = d.zonal_id
             WHERE d.division_id = ?",
            [$divisionId]
        );
    }

    public function refreshDivision(int $divisionId): array {
        $clubs = $this->resultSet(
            "SELECT c.club_id, c.registration_date
             FROM Club c WHERE c.division_id = ? AND c.status IN ('Active','Flagged') ORDER BY c.club_id",
            [$divisionId]
        );
        $pdo = Database::getInstance()->getConnection();
        $currentScores = [];

        foreach ($clubs as $club) {
            $history = [];
            $currentMonth = new DateTimeImmutable('first day of this month');
            for ($offset = 5; $offset >= 0; $offset--) {
                $periodEnd = $offset === 0
                    ? new DateTimeImmutable('today')
                    : $currentMonth->modify("-$offset months")->modify('last day of this month');
                $scoreMonth = $periodEnd->modify('first day of this month')->format('Y-m-d');
                $score = $this->calculateScore((int) $club->club_id, $periodEnd);
                $history[] = $score;
                $this->upsertSnapshot($pdo, (int) $club->club_id, $scoreMonth, $score);
            }

            $current = $history[count($history) - 1];
            $currentScores[(int) $club->club_id] = $current;
            $pdo->prepare("UPDATE Club SET overall_health_score = ?, health_status = ? WHERE club_id = ? AND division_id = ?")
                ->execute([$current['overall_score'], $current['health_status'], $club->club_id, $divisionId]);

            $registeredLongEnough = strtotime((string) $club->registration_date) <= strtotime($history[0]['window_start']);
            $sixDormantMonths = $registeredLongEnough && count(array_filter($history, fn($score) => $score['overall_score'] < 30)) === 6;
            if ($sixDormantMonths) {
                $this->createAutomaticDormancyFlag($pdo, (int) $club->club_id);
            }
        }

        return $currentScores;
    }

    public function getClubs(int $divisionId, array $scores): array {
        $clubs = $this->resultSet(
            "SELECT c.club_id, c.club_name, c.description, c.registration_date, c.status,
                    c.club_code, c.overall_health_score, c.health_status, c.flagged,
                    d.division_name, ca.club_logo_path,
                    COUNT(DISTINCT CASE WHEN u.status = 'Active' THEN u.user_id END) AS active_members,
                    COUNT(DISTINCT CASE WHEN hf.status IN ('Open','UnderReview') THEN hf.health_flag_id END) AS open_flags
             FROM Club c
             INNER JOIN Division d ON d.division_id = c.division_id
             LEFT JOIN ClubApplication ca ON ca.club_name COLLATE utf8mb4_unicode_ci = c.club_name COLLATE utf8mb4_unicode_ci
             LEFT JOIN User u ON u.club_id = c.club_id
             LEFT JOIN ClubHealthFlag hf ON hf.club_id = c.club_id
             WHERE c.division_id = ? AND c.status IN ('Active','Flagged')
             GROUP BY c.club_id
             ORDER BY c.overall_health_score DESC, c.club_name",
            [$divisionId]
        );
        foreach ($clubs as $club) {
            $club->score = $scores[(int) $club->club_id] ?? $this->calculateScore((int) $club->club_id, new DateTimeImmutable('today'));
        }
        return $clubs;
    }

    public function getSummary(array $clubs): array {
        $summary = ['green' => 0, 'yellow' => 0, 'red' => 0, 'flagged' => 0];
        foreach ($clubs as $club) {
            $key = strtolower($club->score['health_status']);
            $summary[$key]++;
            if ((int) $club->open_flags > 0) $summary['flagged']++;
        }
        return $summary;
    }

    /**
     * Read-only current score for one club (no snapshot writes).
     */
    public function scoreClub(int $clubId): array {
        return $this->calculateScore($clubId, new DateTimeImmutable('today'));
    }

    public function getClubDetails(int $divisionId, int $clubId): ?array {
        $club = $this->single(
            "SELECT c.*, d.division_name, z.zonal_name, ca.club_logo_path,
                    ca.category AS club_category, ca.city AS club_city,
                    ca.date_establishment,
                    COUNT(DISTINCT CASE WHEN u.status = 'Active' THEN u.user_id END) AS active_members
             FROM Club c
             INNER JOIN Division d ON d.division_id = c.division_id
             INNER JOIN Zone z ON z.zonal_id = d.zonal_id
             LEFT JOIN ClubApplication ca ON ca.club_name COLLATE utf8mb4_unicode_ci = c.club_name COLLATE utf8mb4_unicode_ci
             LEFT JOIN User u ON u.club_id = c.club_id
             WHERE c.club_id = ? AND c.division_id = ? AND c.status IN ('Active','Flagged')
             GROUP BY c.club_id",
            [$clubId, $divisionId]
        );
        if (!$club) return null;

        $end = new DateTimeImmutable('today');
        $score = $this->calculateScore($clubId, $end);
        $events = $this->getEventDetails($clubId, $score['window_start'], $score['window_end']);
        $finance = $this->getFinanceDetails($clubId, $score['window_start'], $score['window_end']);

        return [
            'club' => $club,
            'score' => $score,
            'executives' => $this->resultSet(
                "SELECT user_id, first_name, last_name, role, email, phone_number,
                        profile_picture_url
                 FROM User WHERE club_id = ? AND role IN ('ClubPresident','ClubSecretary','ClubTreasurer')
                   AND status = 'Active'
                 ORDER BY FIELD(role, 'ClubPresident','ClubSecretary','ClubTreasurer')",
                [$clubId]
            ),
            'events' => $events,
            'finance' => $finance,
            'flags' => $this->resultSet(
                "SELECT hf.*, CONCAT_WS(' ', raiser.first_name, raiser.last_name) AS raised_by_name,
                        raiser.role AS raised_by_role
                 FROM ClubHealthFlag hf
                 LEFT JOIN User raiser ON raiser.user_id = hf.raised_by
                 WHERE hf.club_id = ? ORDER BY hf.raised_at DESC",
                [$clubId]
            ),
            'history' => $this->resultSet(
                "SELECT score_month, event_score, finance_score, attendance_score, overall_score, health_status
                 FROM ClubHealthSnapshot WHERE club_id = ? ORDER BY score_month DESC LIMIT 6",
                [$clubId]
            ),
        ];
    }

    public function getAllowedFlagCategories(string $role): array {
        $categoriesByRole = [
            'DivisionalTreasurer' => ['FinancialConcern'],
            'DivisionalSecretary' => ['EventAttendanceConcern'],
            'DivisionalCoordinator' => [
                'FinancialConcern',
                'EventAttendanceConcern',
                'GovernanceConcern',
            ],
        ];

        return $categoriesByRole[$role] ?? [];
    }

    public function raiseFlag(
        int $divisionId,
        int $clubId,
        int $userId,
        string $role,
        string $requestedCategory,
        string $reason
    ): void {
        $allowedCategories = $this->getAllowedFlagCategories($role);
        if (!$allowedCategories) throw new RuntimeException('Your role cannot raise a club health concern.');
        if ($requestedCategory === '' && count($allowedCategories) === 1) {
            $requestedCategory = $allowedCategories[0];
        }
        if (!in_array($requestedCategory, $allowedCategories, true)) {
            throw new RuntimeException('Select a permitted concern type.');
        }
        $club = $this->single("SELECT club_id, club_name FROM Club WHERE club_id = ? AND division_id = ? AND status IN ('Active','Flagged')", [$clubId, $divisionId]);
        if (!$club) throw new RuntimeException('The selected club is outside your division.');
        $reason = trim($reason);
        $reasonLength = function_exists('mb_strlen') ? mb_strlen($reason) : strlen($reason);
        if ($reasonLength < 10 || $reasonLength > 1000) throw new InvalidArgumentException('Provide a clear reason between 10 and 1000 characters.');

        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare(
                "INSERT INTO ClubHealthFlag (club_id, flag_category, source, reason, status, raised_by)
                 VALUES (?, ?, 'Manual', ?, 'Open', ?)"
            );
            $insert->execute([$clubId, $requestedCategory, $reason, $userId]);
            $flagId = (int) $pdo->lastInsertId();
            $pdo->prepare("UPDATE Club SET flagged = 1 WHERE club_id = ?")->execute([$clubId]);

            $recipients = $pdo->query("SELECT user_id FROM User WHERE role = 'NYSCAdministrator' AND status = 'Active'")->fetchAll(PDO::FETCH_COLUMN);
            $notify = $pdo->prepare(
                "INSERT INTO Notification (recipient_id, type, message, related_entity_type, related_entity_id, read_status)
                 VALUES (?, 'ClubHealthFlag', ?, 'ClubHealthFlag', ?, 0)"
            );
            foreach ($recipients as $recipientId) {
                $notify->execute([$recipientId, "A club health concern was raised for {$club->club_name}.", $flagId]);
            }
            $pdo->prepare("INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details) VALUES (?, 'ClubHealthFlagRaised', 'ClubHealthFlag', ?, ?)")
                ->execute([$userId, $flagId, "{$requestedCategory} raised for club {$clubId}."]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    protected function calculateScore(int $clubId, DateTimeImmutable $periodEnd): array {
        $windowEnd = $periodEnd->format('Y-m-d');
        $windowStart = $periodEnd->modify('-6 months')->modify('+1 day')->format('Y-m-d');

        $event = $this->single(
            "SELECT COUNT(*) AS completed_events
             FROM Event WHERE organizer_club_id = ? AND status = 'Completed'
               AND DATE(end_datetime) BETWEEN ? AND ?",
            [$clubId, $windowStart, $windowEnd]
        );
        $completedEvents = (int) ($event->completed_events ?? 0);
        $eventScore = min(100, ($completedEvents / self::EVENT_TARGET) * 100);

        $attendance = $this->single(
            "SELECT COALESCE(SUM(a.status = 'Present'), 0) AS present_count, COUNT(a.attendance_id) AS recorded_count
             FROM Event e INNER JOIN Attendance a ON a.event_id = e.event_id
             WHERE e.organizer_club_id = ? AND e.status = 'Completed'
               AND DATE(e.end_datetime) BETWEEN ? AND ?",
            [$clubId, $windowStart, $windowEnd]
        );
        $present = (int) ($attendance->present_count ?? 0);
        $recorded = (int) ($attendance->recorded_count ?? 0);
        $attendanceScore = $recorded > 0 ? ($present / $recorded) * 100 : 0;

        $finance = $this->single(
            "SELECT COUNT(le.entry_id) AS approved_entries,
                    COALESCE(SUM(le.type = 'Expense'), 0) AS expense_entries,
                    COALESCE(SUM(le.type = 'Expense' AND le.attachment_url IS NOT NULL AND le.attachment_url <> ''), 0) AS receipted_expenses,
                    COALESCE(SUM(le.reconciled = 1), 0) AS reconciled_entries
             FROM Ledger l LEFT JOIN LedgerEntry le ON le.ledger_id = l.ledger_id
                AND le.status = 'Approved' AND le.date BETWEEN ? AND ?
             WHERE l.owner_type = 'Club' AND l.owner_id = ?",
            [$windowStart, $windowEnd, $clubId]
        );
        $entries = (int) ($finance->approved_entries ?? 0);
        $expenses = (int) ($finance->expense_entries ?? 0);
        $receipted = (int) ($finance->receipted_expenses ?? 0);
        $reconciled = (int) ($finance->reconciled_entries ?? 0);
        $activityScore = min(100, ($entries / self::FINANCE_ENTRY_TARGET) * 100);
        $receiptScore = $expenses > 0 ? ($receipted / $expenses) * 100 : ($entries > 0 ? 100 : 0);
        $reconciliationScore = $entries > 0 ? ($reconciled / $entries) * 100 : 0;
        $financeScore = ($activityScore * .40) + ($receiptScore * .30) + ($reconciliationScore * .30);

        $overall = ($eventScore * .40) + ($financeScore * .30) + ($attendanceScore * .30);
        $status = $overall > 70 ? 'Green' : ($overall < 30 ? 'Red' : 'Yellow');

        return [
            'window_start' => $windowStart,
            'window_end' => $windowEnd,
            'event_score' => round($eventScore, 2),
            'finance_score' => round($financeScore, 2),
            'attendance_score' => round($attendanceScore, 2),
            'overall_score' => round($overall, 2),
            'health_status' => $status,
            'completed_events' => $completedEvents,
            'attendance_present' => $present,
            'attendance_recorded' => $recorded,
            'approved_entries' => $entries,
            'expense_entries' => $expenses,
            'receipted_expenses' => $receipted,
            'reconciled_entries' => $reconciled,
            'finance_activity_score' => round($activityScore, 2),
            'receipt_score' => round($receiptScore, 2),
            'reconciliation_score' => round($reconciliationScore, 2),
        ];
    }

    protected function upsertSnapshot(PDO $pdo, int $clubId, string $scoreMonth, array $score): void {
        $stmt = $pdo->prepare(
            "INSERT INTO ClubHealthSnapshot
                (club_id, score_month, window_start, window_end, event_score, finance_score, attendance_score,
                 overall_score, health_status, completed_events, attendance_present, attendance_recorded,
                 approved_entries, expense_entries, receipted_expenses, reconciled_entries)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE window_start = VALUES(window_start), window_end = VALUES(window_end),
                event_score = VALUES(event_score), finance_score = VALUES(finance_score),
                attendance_score = VALUES(attendance_score), overall_score = VALUES(overall_score),
                health_status = VALUES(health_status), completed_events = VALUES(completed_events),
                attendance_present = VALUES(attendance_present), attendance_recorded = VALUES(attendance_recorded),
                approved_entries = VALUES(approved_entries), expense_entries = VALUES(expense_entries),
                receipted_expenses = VALUES(receipted_expenses), reconciled_entries = VALUES(reconciled_entries)"
        );
        $stmt->execute([$clubId, $scoreMonth, $score['window_start'], $score['window_end'], $score['event_score'],
            $score['finance_score'], $score['attendance_score'], $score['overall_score'], $score['health_status'],
            $score['completed_events'], $score['attendance_present'], $score['attendance_recorded'],
            $score['approved_entries'], $score['expense_entries'], $score['receipted_expenses'], $score['reconciled_entries']]);
    }

    protected function createAutomaticDormancyFlag(PDO $pdo, int $clubId): void {
        $check = $pdo->prepare(
            "SELECT health_flag_id FROM ClubHealthFlag
             WHERE club_id = ? AND flag_category = 'AutomaticDormancy' AND status IN ('Open','UnderReview') LIMIT 1"
        );
        $check->execute([$clubId]);
        if ($check->fetchColumn()) return;
        $pdo->prepare(
            "INSERT INTO ClubHealthFlag (club_id, flag_category, source, reason, status, raised_by)
             VALUES (?, 'AutomaticDormancy', 'System', 'Health score remained below 30 for six consecutive monthly calculations.', 'Open', NULL)"
        )->execute([$clubId]);
        $flagId = (int) $pdo->lastInsertId();
        $pdo->prepare("UPDATE Club SET flagged = 1 WHERE club_id = ?")->execute([$clubId]);

        $club = $pdo->prepare("SELECT club_name FROM Club WHERE club_id = ?");
        $club->execute([$clubId]);
        $clubName = (string) ($club->fetchColumn() ?: 'A club');
        $recipients = $pdo->query("SELECT user_id FROM User WHERE role = 'NYSCAdministrator' AND status = 'Active'")
            ->fetchAll(PDO::FETCH_COLUMN);
        $notify = $pdo->prepare(
            "INSERT INTO Notification (recipient_id, type, message, related_entity_type, related_entity_id, read_status)
             VALUES (?, 'ClubHealthFlag', ?, 'ClubHealthFlag', ?, 0)"
        );
        foreach ($recipients as $recipientId) {
            $notify->execute([$recipientId, "$clubName has remained dormant for six monthly calculations.", $flagId]);
        }
    }

    protected function getEventDetails(int $clubId, string $start, string $end): array {
        return $this->resultSet(
            "SELECT e.event_id, e.title, e.event_type, e.start_datetime, e.end_datetime, e.location,
                    e.status, e.max_attendance,
                    COUNT(a.attendance_id) AS attendance_recorded,
                    COALESCE(SUM(a.status = 'Present'), 0) AS present_count,
                    COALESCE(SUM(a.status = 'Absent'), 0) AS absent_count
             FROM Event e LEFT JOIN Attendance a ON a.event_id = e.event_id
             WHERE e.organizer_club_id = ? AND DATE(e.end_datetime) BETWEEN ? AND ?
             GROUP BY e.event_id ORDER BY e.start_datetime DESC",
            [$clubId, $start, $end]
        );
    }

    protected function getFinanceDetails(int $clubId, string $start, string $end): array {
        $ledger = $this->single("SELECT ledger_id, current_balance, status FROM Ledger WHERE owner_type = 'Club' AND owner_id = ? LIMIT 1", [$clubId]);
        if (!$ledger) return ['ledger' => null, 'totals' => null, 'entries' => [], 'audits' => [], 'red_flags' => []];
        $totals = $this->single(
            "SELECT COALESCE(SUM(CASE WHEN type = 'Income' AND status = 'Approved' THEN amount ELSE 0 END), 0) AS income,
                    COALESCE(SUM(CASE WHEN type = 'Expense' AND status = 'Approved' THEN amount ELSE 0 END), 0) AS expenses,
                    COUNT(CASE WHEN status = 'Approved' THEN 1 END) AS approved_entries,
                    COUNT(CASE WHEN status = 'Approved' AND type = 'Expense' AND attachment_url IS NOT NULL AND attachment_url <> '' THEN 1 END) AS receipted_expenses,
                    COUNT(CASE WHEN status = 'Approved' AND reconciled = 1 THEN 1 END) AS reconciled_entries
             FROM LedgerEntry WHERE ledger_id = ? AND date BETWEEN ? AND ?",
            [$ledger->ledger_id, $start, $end]
        );
        $entries = $this->resultSet(
            "SELECT entry_id, date, type, category, description, amount, status, attachment_url, reconciled
             FROM LedgerEntry WHERE ledger_id = ? AND date BETWEEN ? AND ?
             ORDER BY date DESC, entry_id DESC LIMIT 10",
            [$ledger->ledger_id, $start, $end]
        );
        $audits = $this->resultSet(
            "SELECT audit_id, audit_type, period_start, period_end, audit_status, math_check_status,
                    expected_closing_balance, actual_closing_balance, auditor_notes
             FROM Audit WHERE scope_level = 'Club' AND scope_id = ? ORDER BY period_end DESC, audit_id DESC LIMIT 6",
            [$clubId]
        );
        $redFlags = $this->resultSet(
            "SELECT rf.red_flag_id, rf.flag_type, rf.description, rf.status, rf.flagged_at
             FROM RedFlag rf INNER JOIN Audit a ON a.audit_id = rf.audit_id
             WHERE a.scope_level = 'Club' AND a.scope_id = ? ORDER BY rf.flagged_at DESC LIMIT 10",
            [$clubId]
        );
        return ['ledger' => $ledger, 'totals' => $totals, 'entries' => $entries, 'audits' => $audits, 'red_flags' => $redFlags];
    }
}
