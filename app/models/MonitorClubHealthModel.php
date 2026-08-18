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

    /**
     * Fetch executive committee members for all clubs in a division (batched for performance).
     *
     * @param  int   $divisionId
     * @return array Associative array indexed by club_id containing array of committee members
     */
    public function getExecutiveCommitteesByDivision($divisionId) {
        $sql = "
            SELECT u.user_id, u.first_name, u.last_name, u.role, u.club_id, u.profile_picture_url
            FROM User u
            JOIN Club c ON u.club_id = c.club_id
            WHERE c.division_id = ? AND u.role IN ('ClubPresident', 'ClubSecretary', 'ClubTreasurer')
            ORDER BY FIELD(u.role, 'ClubPresident', 'ClubSecretary', 'ClubTreasurer'), u.user_id ASC
        ";

        $rows = $this->resultSet($sql, [(int)$divisionId]);
        $committees = [];

        foreach ($rows as $row) {
            $roleLabel = 'Member';
            if ($row->role === 'ClubPresident') {
                $roleLabel = 'President';
            } elseif ($row->role === 'ClubSecretary') {
                $roleLabel = 'Secretary';
            } elseif ($row->role === 'ClubTreasurer') {
                $roleLabel = 'Treasurer';
            }

            $firstName = trim($row->first_name ?? '');
            $lastName  = trim($row->last_name ?? '');
            $initials  = '';
            if (!empty($firstName)) $initials .= strtoupper($firstName[0]);
            if (!empty($lastName))  $initials .= strtoupper($lastName[0]);
            if (empty($initials))   $initials = 'U';

            $committees[$row->club_id][] = [
                'user_id'   => (int)$row->user_id,
                'name'      => trim($firstName . ' ' . $lastName),
                'role_type' => $roleLabel,
                'photo'     => $row->profile_picture_url ?? null,
                'initials'  => $initials,
            ];
        }

        return $committees;
    }

    /**
     * Fetch executive committee members for a single club.
     *
     * @param  int   $clubId
     * @return array
     */
    public function getExecutiveCommitteeByClub($clubId) {
        $sql = "
            SELECT user_id, first_name, last_name, role, profile_picture_url
            FROM User
            WHERE club_id = ? AND role IN ('ClubPresident', 'ClubSecretary', 'ClubTreasurer')
            ORDER BY FIELD(role, 'ClubPresident', 'ClubSecretary', 'ClubTreasurer'), user_id ASC
        ";

        return $this->resultSet($sql, [(int)$clubId]);
    }
}
