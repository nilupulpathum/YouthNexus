<?php

/**
 * Zonal coordinator overview (D8). Zone-scoped reads over Division / Club /
 * User / Event / Attendance. Scores come from
 * DivisionalClubHealthModel::scoreClub (same formula as divisional).
 * Club-level monitoring and concerns live in ZoneClubHealthModel.
 */
class ZoneMonitorModel extends Model {

    public function getZone(int $zonalId) {
        return $this->single(
            "SELECT zonal_id, zonal_name FROM Zone WHERE zonal_id = ? LIMIT 1",
            [$zonalId]
        );
    }

    public function getDivisions(int $zonalId): array {
        return $this->resultSet(
            "SELECT division_id, division_name FROM Division
             WHERE zonal_id = ? ORDER BY division_name",
            [$zonalId]
        );
    }

    public function getClubs(int $zonalId): array {
        return $this->resultSet(
            "SELECT c.club_id, c.club_name, c.description, c.registration_date,
                    c.status, c.club_code, d.division_id, d.division_name,
                    COUNT(DISTINCT CASE WHEN u.status = 'Active' THEN u.user_id END) AS active_members,
                    COUNT(DISTINCT CASE WHEN hf.status IN ('Open','UnderReview') THEN hf.health_flag_id END) AS open_flags
             FROM Club c
             INNER JOIN Division d ON d.division_id = c.division_id
             LEFT JOIN User u ON u.club_id = c.club_id
             LEFT JOIN ClubHealthFlag hf ON hf.club_id = c.club_id
             WHERE d.zonal_id = ? AND c.status IN ('Active', 'Flagged')
             GROUP BY c.club_id
             ORDER BY c.club_name",
            [$zonalId]
        );
    }

    public function getExecutives(int $clubId): array {
        return $this->resultSet(
            "SELECT user_id, first_name, last_name, role FROM User
             WHERE club_id = ? AND status = 'Active'
               AND role IN ('ClubPresident', 'ClubSecretary', 'ClubTreasurer')
             ORDER BY FIELD(role, 'ClubPresident', 'ClubSecretary', 'ClubTreasurer')",
            [(int) $clubId]
        );
    }

    public function getRecentEvents(int $clubId, int $limit = 3): array {
        return $this->resultSet(
            "SELECT event_id, title, start_datetime, status FROM Event
             WHERE organizer_club_id = ?
             ORDER BY start_datetime DESC LIMIT ?",
            [(int) $clubId, (int) $limit]
        );
    }

    /**
     * Zone-wide attendance totals over every event in the zone's scope
     * (zonal, divisional and club events). Single query, read-only.
     *
     * @return array{rate: float, present: int, recorded: int, sessions: int}
     */
    public function getZoneAttendance(int $zonalId): array {
        $row = $this->single(
            "SELECT COUNT(DISTINCT e.event_id) AS sessions,
                    COUNT(a.attendance_id) AS recorded,
                    COALESCE(SUM(a.status = 'Present'), 0) AS present_count
             FROM Event e
             LEFT JOIN Attendance a ON a.event_id = e.event_id
             LEFT JOIN Club c ON c.club_id = e.organizer_club_id
             WHERE e.organizer_zonal_id = ?
                OR e.organizer_division_id IN (SELECT division_id FROM Division WHERE zonal_id = ?)
                OR c.division_id IN (SELECT division_id FROM Division WHERE zonal_id = ?)",
            [(int) $zonalId, (int) $zonalId, (int) $zonalId]
        );
        $recorded = (int) ($row->recorded ?? 0);
        $present = (int) ($row->present_count ?? 0);
        return [
            'rate' => $recorded > 0 ? round($present * 100 / $recorded, 1) : 0,
            'present' => $present,
            'recorded' => $recorded,
            'sessions' => (int) ($row->sessions ?? 0),
        ];
    }

    /**
     * Per-division attendance rollup for the zone secretary's read-only page.
     * Division and club events in the last $days days; zonal-organized events
     * belong to the zone, not to any division, so they are excluded here.
     *
     * @return list<array{name: string, clubs: int, sessions: int, present: int, possible: int, rate: float}>
     */
    public function getDivisionAttendance(int $zonalId, int $days): array {
        $rows = $this->resultSet(
            "SELECT d.division_id, d.division_name,
                    (SELECT COUNT(*) FROM Club c
                      WHERE c.division_id = d.division_id AND c.status IN ('Active','Flagged')) AS clubs,
                    COUNT(DISTINCT e.event_id) AS sessions,
                    COUNT(a.attendance_id) AS recorded,
                    COALESCE(SUM(a.status = 'Present'), 0) AS present_count
             FROM Division d
             LEFT JOIN Event e
               ON (e.organizer_division_id = d.division_id
                   OR e.organizer_club_id IN (SELECT club_id FROM Club WHERE division_id = d.division_id))
               AND DATE(e.start_datetime) >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             LEFT JOIN Attendance a ON a.event_id = e.event_id
             WHERE d.zonal_id = ?
             GROUP BY d.division_id, d.division_name
             ORDER BY d.division_name",
            [(int) $days, (int) $zonalId]
        );
        $out = [];
        foreach ($rows as $r) {
            $recorded = (int) $r->recorded;
            $present = (int) $r->present_count;
            $out[] = [
                'name' => $r->division_name,
                'clubs' => (int) $r->clubs,
                'sessions' => (int) $r->sessions,
                'present' => $present,
                'possible' => $recorded,
                'rate' => $recorded > 0 ? round($present * 100 / $recorded, 1) : 0,
            ];
        }
        return $out;
    }

    public function getAttendanceRate(int $clubId): ?float {
        $row = $this->single(
            "SELECT COALESCE(SUM(a.status = 'Present'), 0) AS present_count,
                    COUNT(a.attendance_id) AS recorded_count
             FROM Event e
             INNER JOIN Attendance a ON a.event_id = e.event_id
             WHERE e.organizer_club_id = ? AND e.status = 'Completed'",
            [(int) $clubId]
        );
        $recorded = (int) ($row->recorded_count ?? 0);
        if ($recorded === 0) {
            return null;
        }
        return round((int) ($row->present_count ?? 0) * 100 / $recorded, 1);
    }

}
