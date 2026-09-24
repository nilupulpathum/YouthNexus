<?php

/**
 * ClubHealthModel — Monitor Club Health (NYSC Administration)
 *
 * Mirrors DivisionalClubHealthModel but works nationally across all zones/divisions.
 * Scoring formula: Events 40% + Finance 30% + Attendance 30% (6-month rolling window).
 * Bands: Green > 70, Yellow 30–70, Red (Dormant) < 30.
 * Six consecutive dormant months → automatic dormancy flag + NYSC notification.
 */
class ClubHealthModel extends Model {

    private const WINDOW_MONTHS        = 6;
    private const EVENT_TARGET         = 6;
    private const FINANCE_ENTRY_TARGET = 6;

    /* ============================================================
     * FILTER HELPERS
     * ============================================================ */

    public function getZones(): array {
        return $this->resultSet(
            "SELECT zonal_id, zonal_name FROM Zone ORDER BY zonal_name ASC"
        );
    }

    public function getDivisionsByZone(int $zoneId): array {
        return $this->resultSet(
            "SELECT division_id, division_name FROM Division WHERE zonal_id = ? ORDER BY division_name ASC",
            [$zoneId]
        );
    }

    /**
     * Build a shared WHERE clause from zone / division / search / bucket filters.
     * Only Active and Flagged clubs are ever included (Disbanded appear when explicitly requested
     * via bucket=dormant with status override — but by default we exclude them).
     */
    private function buildFilter(array $f, ?array &$params = []): string {
        $params = [];
        $where  = " WHERE c.status IN ('Active','Flagged') ";

        if (!empty($f['zone']))     { $where .= " AND d.zonal_id = ? ";    $params[] = (int)$f['zone']; }
        if (!empty($f['division'])) { $where .= " AND c.division_id = ? "; $params[] = (int)$f['division']; }
        if (!empty($f['q'])) {
            $like    = '%' . trim($f['q']) . '%';
            $where  .= " AND (c.club_name LIKE ? OR c.club_code LIKE ? OR d.division_name LIKE ? OR z.zonal_name LIKE ?) ";
            array_push($params, $like, $like, $like, $like);
        }
        if (!empty($f['bucket'])) {
            switch ($f['bucket']) {
                case 'green':   $where .= " AND c.health_status = 'Green'  AND c.overall_health_score >= 70 ";  break;
                case 'yellow':  $where .= " AND c.health_status = 'Yellow' AND c.overall_health_score >= 30 AND c.overall_health_score < 70 "; break;
                case 'dormant': $where .= " AND (c.health_status = 'Red'   OR  c.overall_health_score < 30) ";  break;
            }
        }
        return $where;
    }

    /* ============================================================
     * SUMMARY KPIs
     * ============================================================ */

    public function getSummary(array $f): array {
        // Strip bucket so we always count all bands
        $base   = ['zone' => $f['zone'] ?? null, 'division' => $f['division'] ?? null, 'q' => $f['q'] ?? ''];
        $params = [];
        $where  = $this->buildFilter($base, $params);

        $row = $this->single(
            "SELECT COUNT(*) AS monitored,
                    SUM(CASE WHEN c.health_status = 'Green'  AND c.overall_health_score >= 70 THEN 1 ELSE 0 END) AS green_count,
                    SUM(CASE WHEN c.health_status = 'Yellow' AND c.overall_health_score >= 30 AND c.overall_health_score < 70 THEN 1 ELSE 0 END) AS yellow_count,
                    SUM(CASE WHEN c.health_status = 'Red'    OR  c.overall_health_score < 30 THEN 1 ELSE 0 END) AS red_count,
                    SUM(CASE WHEN c.flagged = 1 THEN 1 ELSE 0 END) AS flagged_count,
                    ROUND(AVG(c.overall_health_score), 1) AS avg_score
             FROM Club c
             JOIN Division d ON c.division_id = d.division_id
             JOIN Zone z     ON d.zonal_id    = z.zonal_id
             $where",
            $params
        );

        return [
            'monitored' => (int)($row->monitored    ?? 0),
            'green'     => (int)($row->green_count  ?? 0),
            'yellow'    => (int)($row->yellow_count ?? 0),
            'red'       => (int)($row->red_count    ?? 0),
            'flagged'   => (int)($row->flagged_count ?? 0),
            'avg'       => (float)($row->avg_score  ?? 0),
        ];
    }

    /* ============================================================
     * FILTERED CLUB LIST
     * ============================================================ */

    public function getClubs(array $f, int $limit = 300): array {
        $params = [];
        $where  = $this->buildFilter($f, $params);
        $order  = ($f['bucket'] ?? '') === 'dormant'
            ? " c.overall_health_score ASC, c.club_name ASC "
            : " c.overall_health_score DESC, c.club_name ASC ";

        $clubs = $this->resultSet(
            "SELECT c.club_id, c.club_name, c.description, c.registration_date,
                    c.status AS club_status, c.club_code, c.overall_health_score,
                    c.health_status, c.flagged, c.disband_reason, c.disbanded_at,
                    c.no_of_members,
                    d.division_id, d.division_name, z.zonal_id, z.zonal_name,
                    ca.club_logo_path,
                    COUNT(DISTINCT CASE WHEN u.status = 'Active' THEN u.user_id END) AS active_members,
                    COUNT(DISTINCT CASE WHEN hf.status IN ('Open','UnderReview') THEN hf.health_flag_id END) AS open_flags
             FROM Club c
             JOIN Division d ON c.division_id = d.division_id
             JOIN Zone z     ON d.zonal_id    = z.zonal_id
             LEFT JOIN ClubApplication ca ON ca.application_id = c.source_application_id
             LEFT JOIN User u             ON u.club_id = c.club_id
             LEFT JOIN ClubHealthFlag hf  ON hf.club_id = c.club_id
             $where
             GROUP BY c.club_id
             ORDER BY $order
             LIMIT " . (int)$limit,
            $params
        );

        // Attach a live score sub-array so the view can use $club->score['overall_score'] etc.
        foreach ($clubs as $club) {
            $club->score = [
                'overall_score'    => (float)$club->overall_health_score,
                'health_status'    => $club->health_status,
                'event_score'      => null,
                'finance_score'    => null,
                'attendance_score' => null,
            ];
        }
        return $clubs;
    }

    /* ============================================================
     * FULL CLUB DETAILS (for the profile modal JSON blob)
     * ============================================================ */

    public function getClubDetails(int $clubId): ?array {
        $club = $this->single(
            "SELECT c.*, d.division_name, z.zonal_id, z.zonal_name,
                    ca.club_logo_path, ca.category AS club_category, ca.city AS club_city,
                    ca.date_establishment,
                    COUNT(DISTINCT CASE WHEN u.status = 'Active' THEN u.user_id END) AS active_members
             FROM Club c
             JOIN Division d ON c.division_id = d.division_id
             JOIN Zone z     ON d.zonal_id    = z.zonal_id
             LEFT JOIN ClubApplication ca ON ca.application_id = c.source_application_id
             LEFT JOIN User u             ON u.club_id = c.club_id
             WHERE c.club_id = ?
             GROUP BY c.club_id",
            [$clubId]
        );
        if (!$club) return null;

        $score   = $this->calculateScore($clubId);
        $events  = $this->getEventDetails($clubId, $score['window_start'], $score['window_end']);
        $finance = $this->getFinanceDetails($clubId, $score['window_start'], $score['window_end']);

        // Persist the recalculated score
        $this->query(
            "UPDATE Club SET overall_health_score = ?, health_status = ? WHERE club_id = ?",
            [$score['overall_score'], $score['health_status'], $clubId]
        );
        $this->upsertSnapshot($clubId, date('Y-m-01'), $score);

        $warnedRecently = (int)($this->single(
            "SELECT COUNT(*) AS n FROM Notification
             WHERE type = 'DisbandWarning' AND related_entity_type = 'Club'
               AND related_entity_id = ? AND created_at >= (NOW() - INTERVAL 30 DAY)",
            [$clubId]
        )->n ?? 0) > 0;

        return [
            'club'         => $club,
            'score'        => $score,
            'dormant'      => $score['health_status'] === 'Red',
            'warnedRecent' => $warnedRecently,
            'executives'   => $this->resultSet(
                "SELECT user_id, first_name, last_name, role, email, phone_number, profile_picture_url
                 FROM User
                 WHERE club_id = ? AND role IN ('ClubPresident','ClubSecretary','ClubTreasurer') AND status = 'Active'
                 ORDER BY FIELD(role,'ClubPresident','ClubSecretary','ClubTreasurer')",
                [$clubId]
            ),
            'events'       => $events,
            'finance'      => $finance,
            'flags'        => $this->resultSet(
                "SELECT hf.*, CONCAT_WS(' ', raiser.first_name, raiser.last_name) AS raised_by_name,
                        raiser.role AS raised_by_role
                 FROM ClubHealthFlag hf
                 LEFT JOIN User raiser ON raiser.user_id = hf.raised_by
                 WHERE hf.club_id = ? ORDER BY hf.raised_at DESC",
                [$clubId]
            ),
            'history'      => $this->resultSet(
                "SELECT score_month, event_score, finance_score, attendance_score, overall_score, health_status
                 FROM ClubHealthSnapshot WHERE club_id = ? ORDER BY score_month DESC LIMIT 6",
                [$clubId]
            ),
        ];
    }

    /* ============================================================
     * SCORE CALCULATION (6-month rolling window, same formula as divisional)
     * ============================================================ */

    private function calculateScore(int $clubId): array {
        $end   = new DateTimeImmutable('today');
        $start = $end->modify('-' . self::WINDOW_MONTHS . ' months + 1 day');
        $windowStart = $start->format('Y-m-d');
        $windowEnd   = $end->format('Y-m-d');

        // --- Events (40%) ---
        $evRow = $this->single(
            "SELECT COUNT(*) AS completed
             FROM Event
             WHERE organizer_club_id = ? AND status = 'Completed'
               AND DATE(end_datetime) BETWEEN ? AND ?",
            [$clubId, $windowStart, $windowEnd]
        );
        $completedEvents = (int)($evRow->completed ?? 0);
        $eventScore = min(100, ($completedEvents / self::EVENT_TARGET) * 100);

        // --- Attendance (30%) ---
        $attRow = $this->single(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(a.status = 'Present'), 0) AS present
             FROM Attendance a
             JOIN Event e ON e.event_id = a.event_id
             WHERE e.organizer_club_id = ? AND e.status = 'Completed'
               AND DATE(e.end_datetime) BETWEEN ? AND ?",
            [$clubId, $windowStart, $windowEnd]
        );
        $total           = (int)($attRow->total   ?? 0);
        $present         = (int)($attRow->present ?? 0);
        $attendanceScore = $total > 0 ? ($present / $total) * 100 : 0;

        // --- Finance (30%) ---
        $ledger = $this->single(
            "SELECT ledger_id, current_balance FROM Ledger
             WHERE owner_type = 'Club' AND owner_id = ? LIMIT 1",
            [$clubId]
        );
        $balance  = $ledger ? (float)$ledger->current_balance : 0.0;
        $ledgerId = $ledger ? (int)$ledger->ledger_id : null;

        $entries = $expenses = $receipted = $reconciled = 0;
        if ($ledgerId) {
            $fin = $this->single(
                "SELECT COUNT(CASE WHEN status = 'Approved' THEN 1 END) AS approved_entries,
                        COUNT(CASE WHEN status = 'Approved' AND type = 'Expense' THEN 1 END) AS expense_entries,
                        COUNT(CASE WHEN status = 'Approved' AND type = 'Expense' AND attachment_url IS NOT NULL AND attachment_url <> '' THEN 1 END) AS receipted_expenses,
                        COUNT(CASE WHEN status = 'Approved' AND reconciled = 1 THEN 1 END) AS reconciled_entries
                 FROM LedgerEntry WHERE ledger_id = ? AND date BETWEEN ? AND ?",
                [$ledgerId, $windowStart, $windowEnd]
            );
            $entries    = (int)($fin->approved_entries  ?? 0);
            $expenses   = (int)($fin->expense_entries   ?? 0);
            $receipted  = (int)($fin->receipted_expenses ?? 0);
            $reconciled = (int)($fin->reconciled_entries ?? 0);
        }

        $activityScore     = min(100, ($entries / self::FINANCE_ENTRY_TARGET) * 100);
        $receiptScore      = $expenses > 0 ? ($receipted / $expenses) * 100 : ($entries > 0 ? 100 : 0);
        $reconciliationScore = $entries > 0 ? ($reconciled / $entries) * 100 : 0;
        $financeScore      = ($activityScore * .40) + ($receiptScore * .30) + ($reconciliationScore * .30);

        $overall = ($eventScore * .40) + ($financeScore * .30) + ($attendanceScore * .30);
        $status  = $overall > 70 ? 'Green' : ($overall < 30 ? 'Red' : 'Yellow');

        return [
            'window_start'         => $windowStart,
            'window_end'           => $windowEnd,
            'event_score'          => round($eventScore, 2),
            'finance_score'        => round($financeScore, 2),
            'attendance_score'     => round($attendanceScore, 2),
            'overall_score'        => round($overall, 2),
            'health_status'        => $status,
            'completed_events'     => $completedEvents,
            'attendance_present'   => $present,
            'attendance_recorded'  => $total,
            'approved_entries'     => $entries,
            'expense_entries'      => $expenses,
            'receipted_expenses'   => $receipted,
            'reconciled_entries'   => $reconciled,
            'finance_activity_score'  => round($activityScore, 2),
            'receipt_score'           => round($receiptScore, 2),
            'reconciliation_score'    => round($reconciliationScore, 2),
            'balance'              => $balance,
        ];
    }

    private function upsertSnapshot(int $clubId, string $scoreMonth, array $score): void {
        $this->query(
            "INSERT INTO ClubHealthScore
                (club_id, calculated_date, health_status, overall_score,
                 governance_score, activity_score, finance_score, reporting_score)
             VALUES (?, ?, ?, ?, 0, ?, ?, 0)
             ON DUPLICATE KEY UPDATE
                health_status = VALUES(health_status),
                overall_score = VALUES(overall_score),
                activity_score = VALUES(activity_score),
                finance_score  = VALUES(finance_score)",
            [$clubId, $scoreMonth, $score['health_status'], $score['overall_score'],
             $score['attendance_score'], $score['finance_score']]
        );
    }

    /* ============================================================
     * EVENT & FINANCE DETAILS (for profile modal)
     * ============================================================ */

    private function getEventDetails(int $clubId, string $start, string $end): array {
        return $this->resultSet(
            "SELECT e.event_id, e.title, e.event_type, e.start_datetime, e.end_datetime,
                    e.location, e.status, e.max_attendance,
                    COUNT(a.attendance_id) AS attendance_recorded,
                    COALESCE(SUM(a.status = 'Present'), 0) AS present_count,
                    COALESCE(SUM(a.status = 'Absent'),  0) AS absent_count
             FROM Event e LEFT JOIN Attendance a ON a.event_id = e.event_id
             WHERE e.organizer_club_id = ? AND DATE(e.end_datetime) BETWEEN ? AND ?
             GROUP BY e.event_id ORDER BY e.start_datetime DESC",
            [$clubId, $start, $end]
        );
    }

    private function getFinanceDetails(int $clubId, string $start, string $end): array {
        $ledger = $this->single(
            "SELECT ledger_id, current_balance, status FROM Ledger
             WHERE owner_type = 'Club' AND owner_id = ? LIMIT 1",
            [$clubId]
        );
        if (!$ledger) return ['ledger' => null, 'totals' => null, 'entries' => [], 'audits' => [], 'red_flags' => []];

        $totals = $this->single(
            "SELECT COALESCE(SUM(CASE WHEN type='Income'  AND status='Approved' THEN amount ELSE 0 END), 0) AS income,
                    COALESCE(SUM(CASE WHEN type='Expense' AND status='Approved' THEN amount ELSE 0 END), 0) AS expenses,
                    COUNT(CASE WHEN status='Approved' THEN 1 END) AS approved_entries,
                    COUNT(CASE WHEN status='Approved' AND type='Expense' AND attachment_url IS NOT NULL AND attachment_url <> '' THEN 1 END) AS receipted_expenses,
                    COUNT(CASE WHEN status='Approved' AND reconciled = 1 THEN 1 END) AS reconciled_entries
             FROM LedgerEntry WHERE ledger_id = ? AND date BETWEEN ? AND ?",
            [(int)$ledger->ledger_id, $start, $end]
        );
        $entries = $this->resultSet(
            "SELECT entry_id, date, type, category, description, amount, status, attachment_url, reconciled
             FROM LedgerEntry WHERE ledger_id = ? AND date BETWEEN ? AND ?
             ORDER BY date DESC, entry_id DESC LIMIT 10",
            [(int)$ledger->ledger_id, $start, $end]
        );
        $audits = $this->resultSet(
            "SELECT audit_id, audit_type, period_start, period_end, audit_status, math_check_status,
                    expected_closing_balance, actual_closing_balance, auditor_notes
             FROM Audit WHERE scope_level = 'Club' AND scope_id = ?
             ORDER BY period_end DESC, audit_id DESC LIMIT 6",
            [$clubId]
        );
        $redFlags = $this->resultSet(
            "SELECT rf.red_flag_id, rf.flag_type, rf.description, rf.status, rf.flagged_at
             FROM RedFlag rf INNER JOIN Audit a ON a.audit_id = rf.audit_id
             WHERE a.scope_level = 'Club' AND a.scope_id = ?
             ORDER BY rf.flagged_at DESC LIMIT 10",
            [$clubId]
        );
        return ['ledger' => $ledger, 'totals' => $totals, 'entries' => $entries, 'audits' => $audits, 'red_flags' => $redFlags];
    }

    /* ============================================================
     * CLUB NAME LOOKUP
     * ============================================================ */

    public function getClubName(int $clubId): ?string {
        $row = $this->single("SELECT club_name FROM Club WHERE club_id = ?", [$clubId]);
        return $row ? $row->club_name : null;
    }

    /* ============================================================
     * DISBAND WARNING
     * ============================================================ */

    public function markWarning(int $clubId, int $adminId): int {
        $club = $this->single("SELECT club_name, club_code FROM Club WHERE club_id = ?", [$clubId]);
        if (!$club) return 0;

        $executives = $this->resultSet(
            "SELECT user_id FROM User
             WHERE club_id = ? AND role IN ('ClubPresident','ClubSecretary','ClubTreasurer') AND status = 'Active'",
            [$clubId]
        );
        $message = "DISBAND WARNING — '{$club->club_name}' ({$club->club_code}) is in the Dormant (Red) health band. " .
                   "Improve event participation, attendance and financial integrity immediately, " .
                   "or the club will be disbanded by the NYSC administration.";
        $count = 0;
        foreach ($executives as $ex) {
            $this->query(
                "INSERT INTO Notification (recipient_id, type, message, related_entity_type, related_entity_id, read_status)
                 VALUES (?, 'DisbandWarning', ?, 'Club', ?, 0)",
                [(int)$ex->user_id, $message, $clubId]
            );
            $count++;
        }
        return $count;
    }

    public function getWarningRecipients(int $clubId): array {
        return $this->resultSet(
            "SELECT first_name, last_name, email, role FROM User
             WHERE club_id = ? AND role IN ('ClubPresident','ClubSecretary') AND status = 'Active'",
            [$clubId]
        );
    }

    /* ============================================================
     * EXECUTE DISBAND
     * ============================================================ */

    public function disbandClub(int $clubId, int $adminId, string $reason): array {
        $club = $this->single("SELECT club_id, club_name, club_code, status FROM Club WHERE club_id = ?", [$clubId]);
        if (!$club)                        return ['success' => false, 'message' => 'Club not found.'];
        if ($club->status === 'Disbanded') return ['success' => false, 'message' => 'This club is already disbanded.'];

        $this->query("START TRANSACTION");
        try {
            // 1. Clear fund balance
            $balanceCleared = 0.0;
            $ledger = $this->single(
                "SELECT ledger_id, current_balance FROM Ledger WHERE owner_type = 'Club' AND owner_id = ? LIMIT 1",
                [$clubId]
            );
            if ($ledger && (float)$ledger->current_balance > 0) {
                $balanceCleared = (float)$ledger->current_balance;
                $this->query(
                    "INSERT INTO LedgerEntry (ledger_id, amount, type, description, status, `date`)
                     VALUES (?, ?, 'Expense', ?, 'Approved', CURDATE())",
                    [(int)$ledger->ledger_id, $balanceCleared,
                     "Fund Transfer Out — club '{$club->club_name}' disbanded; residual balance cleared by NYSC administration."]
                );
                $this->query(
                    "UPDATE Ledger SET current_balance = 0 WHERE ledger_id = ?",
                    [(int)$ledger->ledger_id]
                );
            }

            // 2. Archive the club
            $this->query(
                "UPDATE Club SET status = 'Disbanded', flagged = 1,
                                 disband_reason = ?, disbanded_at = NOW(), disbanded_by = ?
                 WHERE club_id = ?",
                [$reason, $adminId, $clubId]
            );

            // 3. Revoke all attached users
            $stmt = $this->query(
                "UPDATE User SET role = 'UnassignedUser', club_id = NULL, membership_status = 'Inactive'
                 WHERE club_id = ?",
                [$clubId]
            );
            $usersRevoked = (int)$stmt->rowCount();

            $this->query("COMMIT");
            return [
                'success'        => true,
                'balanceCleared' => $balanceCleared,
                'usersRevoked'   => $usersRevoked,
                'clubName'       => $club->club_name,
            ];
        } catch (\Exception $e) {
            $this->query("ROLLBACK");
            return ['success' => false, 'message' => 'Disband failed: ' . $e->getMessage()];
        }
    }
}
