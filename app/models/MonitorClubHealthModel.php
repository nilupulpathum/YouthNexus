<?php

class MonitorClubHealthModel extends Model {

    /**
     * Fetch all clubs for a specific division with their division name and live member counts.
     *
     * @param  int   $divisionId
     * @return array Array of club objects
     */
    public function getClubsByDivision($divisionId) {
        $sql = "
            SELECT 
                c.club_id,
                c.club_name,
                c.description,
                c.division_id,
                c.registration_date,
                c.status,
                c.no_of_members,
                c.club_code,
                c.overall_health_score,
                c.health_status,
                c.flagged,
                d.division_name,
                COALESCE(u.active_members, 0) AS live_members
            FROM Club c
            JOIN Division d ON c.division_id = d.division_id
            LEFT JOIN (
                SELECT club_id, COUNT(*) AS active_members
                FROM User
                WHERE club_id IS NOT NULL
                GROUP BY club_id
            ) u ON c.club_id = u.club_id
            WHERE c.division_id = ?
            ORDER BY c.overall_health_score DESC, c.club_name ASC
        ";

        return $this->resultSet($sql, [(int)$divisionId]);
    }

    /**
     * Aggregate health status counts (Green, Yellow, Red) for a division.
     *
     * @param  int   $divisionId
     * @return array Associative array with Green, Yellow, Red, and Total counts
     */
    public function getHealthStatusCounts($divisionId) {
        $sql = "
            SELECT 
                COALESCE(SUM(CASE WHEN health_status = 'Green' THEN 1 ELSE 0 END), 0) AS Green,
                COALESCE(SUM(CASE WHEN health_status = 'Yellow' THEN 1 ELSE 0 END), 0) AS Yellow,
                COALESCE(SUM(CASE WHEN health_status = 'Red' THEN 1 ELSE 0 END), 0) AS Red,
                COUNT(*) AS Total
            FROM Club
            WHERE division_id = ?
        ";

        $row = $this->single($sql, [(int)$divisionId]);

        return [
            'Green'  => (int)($row->Green ?? 0),
            'Yellow' => (int)($row->Yellow ?? 0),
            'Red'    => (int)($row->Red ?? 0),
            'Total'  => (int)($row->Total ?? 0),
        ];
    }

    /**
     * Retrieve the display name of a division by ID.
     *
     * @param  int    $divisionId
     * @return string Division name or fallback 'General Division'
     */
    public function getDivisionName($divisionId) {
        $row = $this->single("SELECT division_name FROM Division WHERE division_id = ? LIMIT 1", [(int)$divisionId]);
        return $row->division_name ?? 'General Division';
    }
}
