<?php

class DivisionalDashboardModel extends Model {
    public function getDivision(int $divisionId) {
        return $this->single(
            "SELECT d.division_id, d.division_name, d.zonal_id, z.zonal_name
             FROM Division d
             INNER JOIN Zone z ON z.zonal_id = d.zonal_id
             WHERE d.division_id = ?
             LIMIT 1",
            [$divisionId]
        );
    }

    public function getSummary(int $divisionId): array {
        $ledger = $this->single(
            "SELECT current_balance
             FROM Ledger
             WHERE owner_type = 'Division' AND owner_id = ? AND status = 'Active'
             LIMIT 1",
            [$divisionId]
        );
        $voids = $this->single(
            "SELECT COUNT(*) AS total
             FROM VoidRequest vr
             INNER JOIN LedgerEntry le ON le.entry_id = vr.entry_id
             INNER JOIN Ledger l ON l.ledger_id = le.ledger_id AND l.owner_type = 'Club'
             INNER JOIN Club c ON c.club_id = l.owner_id
             WHERE c.division_id = ?
               AND vr.scope_direction = 'ClubToDivision'
               AND vr.status = 'Pending'",
            [$divisionId]
        );
        $funds = $this->single(
            "SELECT COUNT(*) AS total
             FROM FundAllocation fa
             INNER JOIN Club c ON c.club_id = fa.to_id AND fa.to_level = 'Club'
             WHERE fa.from_level = 'Divisional'
               AND fa.from_id = ?
               AND c.division_id = ?
               AND fa.status = 'PendingApproval'",
            [$divisionId, $divisionId]
        );
        $clubs = $this->single(
            "SELECT COUNT(DISTINCT c.club_id) AS total
             FROM Club c
             LEFT JOIN ClubHealthFlag hf ON hf.club_id = c.club_id
               AND hf.status IN ('Open','UnderReview')
             WHERE c.division_id = ?
               AND c.status IN ('Active','Flagged')
               AND (c.health_status IN ('Yellow','Red') OR c.flagged = 1 OR hf.health_flag_id IS NOT NULL)",
            [$divisionId]
        );

        return [
            'balance' => (float) ($ledger->current_balance ?? 0),
            'pending_voids' => (int) ($voids->total ?? 0),
            'pending_funds' => (int) ($funds->total ?? 0),
            'attention_clubs' => (int) ($clubs->total ?? 0),
        ];
    }

    public function getCoordinatorSummary(int $divisionId): array {
        $clubs = $this->single(
            "SELECT COUNT(*) AS total FROM Club
             WHERE division_id = ? AND status IN ('Active','Flagged')",
            [$divisionId]
        );
        $applications = $this->single(
            "SELECT COUNT(*) AS total FROM ClubApplication
             WHERE proposed_division_id = ? AND status = 'Pending'",
            [$divisionId]
        );
        $events = $this->single(
            "SELECT COUNT(DISTINCT e.event_id) AS total
             FROM Event e
             LEFT JOIN Club c ON c.club_id = e.organizer_club_id
             WHERE (e.organizer_division_id = ? OR c.division_id = ?)
               AND e.status = 'PendingApproval'",
            [$divisionId, $divisionId]
        );
        $attention = $this->single(
            "SELECT COUNT(DISTINCT c.club_id) AS total
             FROM Club c
             LEFT JOIN ClubHealthFlag hf ON hf.club_id = c.club_id
               AND hf.status IN ('Open','UnderReview')
             WHERE c.division_id = ? AND c.status IN ('Active','Flagged')
               AND (c.health_status IN ('Yellow','Red') OR c.flagged = 1 OR hf.health_flag_id IS NOT NULL)",
            [$divisionId]
        );

        return [
            'clubs' => (int) ($clubs->total ?? 0),
            'pending_applications' => (int) ($applications->total ?? 0),
            'pending_events' => (int) ($events->total ?? 0),
            'attention_clubs' => (int) ($attention->total ?? 0),
        ];
    }

    public function getPendingClubApplications(int $divisionId): array {
        return $this->resultSet(
            "SELECT ca.application_id, ca.club_name, ca.category, ca.no_of_members,
                    ca.submitted_at, CONCAT_WS(' ', u.first_name, u.last_name) AS proposer_name
             FROM ClubApplication ca
             INNER JOIN User u ON u.user_id = ca.proposer_user_id
             WHERE ca.proposed_division_id = ? AND ca.status = 'Pending'
             ORDER BY ca.submitted_at ASC, ca.application_id ASC LIMIT 5",
            [$divisionId]
        );
    }

    public function getPendingEventApprovals(int $divisionId): array {
        return $this->resultSet(
            "SELECT e.event_id, e.title, e.event_type, e.start_datetime,
                    COALESCE(c.club_name, d.division_name) AS organiser
             FROM Event e
             LEFT JOIN Club c ON c.club_id = e.organizer_club_id
             LEFT JOIN Division d ON d.division_id = e.organizer_division_id
             WHERE (e.organizer_division_id = ? OR c.division_id = ?)
               AND e.status = 'PendingApproval'
             ORDER BY e.created_at ASC, e.event_id ASC LIMIT 5",
            [$divisionId, $divisionId]
        );
    }

    public function getSecretarySummary(int $divisionId): array {
        $events = $this->single(
            "SELECT COUNT(DISTINCT e.event_id) AS total
             FROM Event e LEFT JOIN Club c ON c.club_id = e.organizer_club_id
             WHERE (e.organizer_division_id = ? OR c.division_id = ?)
               AND e.status = 'Approved' AND e.start_datetime >= NOW()",
            [$divisionId, $divisionId]
        );
        $completed = $this->single(
            "SELECT COUNT(DISTINCT e.event_id) AS total
             FROM Event e LEFT JOIN Club c ON c.club_id = e.organizer_club_id
             WHERE (e.organizer_division_id = ? OR c.division_id = ?)
               AND (e.status = 'Completed' OR e.end_datetime < NOW())
               AND NOT EXISTS (SELECT 1 FROM Attendance a WHERE a.event_id = e.event_id)",
            [$divisionId, $divisionId]
        );
        $attendance = $this->single(
            "SELECT COUNT(a.attendance_id) AS recorded,
                    COALESCE(SUM(a.status = 'Present'), 0) AS present
             FROM Attendance a
             INNER JOIN Event e ON e.event_id = a.event_id
             LEFT JOIN Club c ON c.club_id = e.organizer_club_id
             WHERE (e.organizer_division_id = ? OR c.division_id = ?)
               AND YEAR(e.start_datetime) = YEAR(CURDATE())",
            [$divisionId, $divisionId]
        );
        $clubs = $this->single(
            "SELECT COUNT(*) AS total FROM Club
             WHERE division_id = ? AND status IN ('Active','Flagged')",
            [$divisionId]
        );
        $recorded = (int) ($attendance->recorded ?? 0);
        $present = (int) ($attendance->present ?? 0);

        return [
            'upcoming_events' => (int) ($events->total ?? 0),
            'attendance_pending' => (int) ($completed->total ?? 0),
            'attendance_rate' => $recorded > 0 ? round($present * 100 / $recorded, 1) : 0,
            'clubs' => (int) ($clubs->total ?? 0),
        ];
    }

    public function getUpcomingEvents(int $divisionId): array {
        return $this->resultSet(
            "SELECT e.event_id, e.title, e.event_type, e.start_datetime, e.location,
                    COALESCE(c.club_name, d.division_name) AS organiser
             FROM Event e
             LEFT JOIN Club c ON c.club_id = e.organizer_club_id
             LEFT JOIN Division d ON d.division_id = e.organizer_division_id
             WHERE (e.organizer_division_id = ? OR c.division_id = ?)
               AND e.status = 'Approved' AND e.start_datetime >= NOW()
             ORDER BY e.start_datetime ASC, e.event_id ASC LIMIT 5",
            [$divisionId, $divisionId]
        );
    }

    public function getAttendanceFollowUps(int $divisionId): array {
        return $this->resultSet(
            "SELECT e.event_id, e.title, e.end_datetime,
                    COALESCE(c.club_name, d.division_name) AS organiser,
                    COUNT(a.attendance_id) AS recorded,
                    COALESCE(SUM(a.status = 'Present'), 0) AS present
             FROM Event e
             LEFT JOIN Club c ON c.club_id = e.organizer_club_id
             LEFT JOIN Division d ON d.division_id = e.organizer_division_id
             LEFT JOIN Attendance a ON a.event_id = e.event_id
             WHERE (e.organizer_division_id = ? OR c.division_id = ?)
               AND (e.status = 'Completed' OR e.end_datetime < NOW())
             GROUP BY e.event_id, e.title, e.end_datetime, c.club_name, d.division_name
             ORDER BY (COUNT(a.attendance_id) = 0) DESC, e.end_datetime DESC LIMIT 5",
            [$divisionId, $divisionId]
        );
    }

    public function getPendingVoidRequests(int $divisionId): array {
        return $this->resultSet(
            "SELECT vr.void_request_id, vr.reason, vr.requested_at, c.club_name,
                    COALESCE(fa.reference_no, CONCAT('LDG-', LPAD(le.entry_id, 4, '0'))) AS reference_no,
                    le.amount, le.type
             FROM VoidRequest vr
             INNER JOIN LedgerEntry le ON le.entry_id = vr.entry_id
             INNER JOIN Ledger l ON l.ledger_id = le.ledger_id AND l.owner_type = 'Club'
             INNER JOIN Club c ON c.club_id = l.owner_id
             LEFT JOIN FundAllocation fa ON fa.allocation_id = le.allocation_id
             WHERE c.division_id = ?
               AND vr.scope_direction = 'ClubToDivision'
               AND vr.status = 'Pending'
             ORDER BY vr.requested_at ASC, vr.void_request_id ASC
             LIMIT 5",
            [$divisionId]
        );
    }

    public function getRecentAllocations(int $divisionId): array {
        return $this->resultSet(
            "SELECT fa.allocation_id, fa.reference_no, fa.amount, fa.fund_category,
                    fa.transfer_date, fa.status, c.club_name
             FROM FundAllocation fa
             INNER JOIN Club c ON c.club_id = fa.to_id AND fa.to_level = 'Club'
             WHERE fa.from_level = 'Divisional'
               AND fa.from_id = ?
               AND c.division_id = ?
             ORDER BY COALESCE(fa.transfer_date, DATE(fa.created_at)) DESC, fa.allocation_id DESC
             LIMIT 5",
            [$divisionId, $divisionId]
        );
    }

    public function getClubHealthOverview(int $divisionId): array {
        return $this->resultSet(
            "SELECT c.club_id, c.club_name, c.overall_health_score, c.health_status,
                    COUNT(DISTINCT CASE WHEN hf.status IN ('Open','UnderReview')
                        THEN hf.health_flag_id END) AS open_flags
             FROM Club c
             LEFT JOIN ClubHealthFlag hf ON hf.club_id = c.club_id
             WHERE c.division_id = ? AND c.status IN ('Active','Flagged')
             GROUP BY c.club_id
             ORDER BY c.overall_health_score ASC, open_flags DESC, c.club_name ASC
             LIMIT 5",
            [$divisionId]
        );
    }

    public function getAuditReminders(int $divisionId): array {
        return $this->resultSet(
            "SELECT c.club_id, c.club_name, latest.audit_id, latest.audit_type,
                    latest.audit_status, latest.period_end, latest.initiated_at,
                    COALESCE(flags.open_flags, 0) AS open_flags
             FROM Club c
             LEFT JOIN Audit latest ON latest.audit_id = (
                 SELECT a2.audit_id
                 FROM Audit a2
                 WHERE a2.scope_level = 'Club' AND a2.scope_id = c.club_id
                 ORDER BY a2.initiated_at DESC, a2.audit_id DESC
                 LIMIT 1
             )
             LEFT JOIN (
                 SELECT audit_id, SUM(status <> 'Resolved') AS open_flags
                 FROM RedFlag
                 GROUP BY audit_id
             ) flags ON flags.audit_id = latest.audit_id
             WHERE c.division_id = ? AND c.status IN ('Active','Flagged')
               AND (latest.audit_id IS NULL
                    OR latest.audit_status IN ('Pending','InProgress','Overdue','ClarificationRequested')
                    OR COALESCE(flags.open_flags, 0) > 0)
             ORDER BY
                 CASE
                     WHEN latest.audit_status = 'Overdue' THEN 1
                     WHEN COALESCE(flags.open_flags, 0) > 0 THEN 2
                     WHEN latest.audit_status = 'ClarificationRequested' THEN 3
                     WHEN latest.audit_id IS NULL THEN 4
                     ELSE 5
                 END,
                 latest.period_end ASC,
                 c.club_name ASC
             LIMIT 5",
            [$divisionId]
        );
    }
}
