<?php

class EventModel extends Model {

    /**
     * Create a new event record.
     *
     * @param  array $data
     * @return int   Inserted event_id
     */
    public function createEvent(array $data) {
        $sql = "INSERT INTO Event (
                    title, description, event_type, max_attendance,
                    start_datetime, end_datetime, location,
                    organizer_division_id, organizer_club_id, organizer_zonal_id,
                    target_scope, status, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $data['title'],
            $data['description'] ?? null,
            $data['event_type'] ?? null,
            !empty($data['max_attendance']) ? (int)$data['max_attendance'] : null,
            $data['start_datetime'],
            $data['end_datetime'],
            $data['location'] ?? null,
            $data['organizer_division_id'] ?? null,
            $data['organizer_club_id'] ?? null,
            $data['organizer_zonal_id'] ?? null,
            $data['target_scope'] ?? 'AllInScope',
            $data['status'] ?? 'PendingApproval',
            $data['created_by'],
        ];

        $this->query($sql, $params);
        return (int)Database::getInstance()->getConnection()->lastInsertId();
    }

    /**
     * Update an existing event created by the current user.
     *
     * @param  int      $eventId
     * @param  int|null $divisionId
     * @param  int      $userId
     * @param  array    $data
     * @return bool
     */
    public function updateEvent($eventId, $divisionId, $userId, array $data) {
        $sql = "UPDATE Event SET
                    title = ?,
                    description = ?,
                    event_type = ?,
                    max_attendance = ?,
                    start_datetime = ?,
                    end_datetime = ?,
                    location = ?,
                    target_scope = ?
                WHERE event_id = ?
                  AND created_by = ?
                  AND (status = 'PendingApproval' OR status = 'Approved')";

        $params = [
            $data['title'],
            $data['description'] ?? null,
            $data['event_type'] ?? null,
            !empty($data['max_attendance']) ? (int)$data['max_attendance'] : null,
            $data['start_datetime'],
            $data['end_datetime'],
            $data['location'] ?? null,
            $data['target_scope'] ?? 'AllInScope',
            $eventId,
            $userId,
        ];

        if ($divisionId !== null && $divisionId > 0) {
            $sql .= " AND organizer_division_id = ?";
            $params[] = $divisionId;
        }

        $stmt = $this->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Find full details of a single event by ID.
     * Does NOT join EventTarget here — caller should use EventTargetModel::findByEventId().
     *
     * @param  int $eventId
     * @return object|false
     */
    public function findById($eventId) {
        $sql = "SELECT 
                    e.*,
                    d.division_name AS organizer_division_name,
                    z.zonal_name AS organizer_zonal_name,
                    c.club_name AS organizer_club_name,
                    c.club_code AS organizer_club_code,
                    CONCAT(uc.first_name, ' ', uc.last_name) AS creator_name,
                    uc.role AS creator_role,
                    uc.email AS creator_email,
                    CONCAT(ua.first_name, ' ', ua.last_name) AS approver_name,
                    ua.role AS approver_role
                FROM Event e
                LEFT JOIN Division d ON e.organizer_division_id = d.division_id
                LEFT JOIN Zone z ON (e.organizer_zonal_id = z.zonal_id OR d.zonal_id = z.zonal_id)
                LEFT JOIN Club c ON e.organizer_club_id = c.club_id
                LEFT JOIN User uc ON e.created_by = uc.user_id
                LEFT JOIN User ua ON e.approved_by = ua.user_id
                WHERE e.event_id = ?
                LIMIT 1";

        return $this->single($sql, [$eventId]);
    }

    /**
     * Retrieve all events within a division (both divisional events and club events, plus national events),
     * with optional search and filtering. Uses GROUP BY to avoid duplicates from multi-target events.
     *
     * @param  int   $divisionId
     * @param  array $filters
     * @return array
     */
    public function getEventsByDivision($divisionId, array $filters = []) {
        $sql = "SELECT 
                    e.*,
                    d.division_name AS organizer_division_name,
                    c.club_name AS organizer_club_name,
                    GROUP_CONCAT(DISTINCT tc.club_name ORDER BY tc.club_name ASC SEPARATOR ', ') AS target_club_names,
                    GROUP_CONCAT(DISTINCT et.target_club_id ORDER BY et.target_club_id ASC SEPARATOR ',') AS target_club_ids,
                    CONCAT(uc.first_name, ' ', uc.last_name) AS creator_name
                FROM Event e
                LEFT JOIN Division d ON e.organizer_division_id = d.division_id
                LEFT JOIN Club c ON e.organizer_club_id = c.club_id
                LEFT JOIN EventTarget et ON e.event_id = et.event_id
                LEFT JOIN Club tc ON et.target_club_id = tc.club_id
                LEFT JOIN User uc ON e.created_by = uc.user_id
                WHERE (e.organizer_division_id = :division_id 
                       OR e.organizer_club_id IN (SELECT club_id FROM Club WHERE division_id = :division_id_clubs)
                       OR (e.organizer_division_id IS NULL AND e.organizer_club_id IS NULL AND e.organizer_zonal_id IS NULL))";

        $params = [
            'division_id'       => $divisionId,
            'division_id_clubs' => $divisionId,
        ];

        // Filter: Status
        if (!empty($filters['status']) && $filters['status'] !== 'All') {
            $sql .= " AND e.status = :status";
            $params['status'] = $filters['status'];
        }

        // Filter: Event Type
        if (!empty($filters['event_type'])) {
            $sql .= " AND e.event_type = :event_type";
            $params['event_type'] = $filters['event_type'];
        }

        // Filter: Target Audience scope (AllInScope vs SelectedClubs)
        if (!empty($filters['target_scope'])) {
            if ($filters['target_scope'] === 'AllInScope') {
                $sql .= " AND e.target_scope = 'AllInScope'";
            } elseif ($filters['target_scope'] === 'SelectedClubs') {
                $sql .= " AND e.target_scope = 'SelectedClubs'";
            }
        }

        // Filter: Specific club within SelectedClubs events
        if (!empty($filters['target_club_id'])) {
            $sql .= " AND et.target_club_id = :target_club_id";
            $params['target_club_id'] = (int)$filters['target_club_id'];
        }

        // Filter: Date Range
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(e.start_datetime) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(e.start_datetime) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        // Filter: Free-text search
        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $sql .= " AND (e.title LIKE :s_title 
                           OR e.description LIKE :s_desc 
                           OR e.location LIKE :s_loc 
                           OR e.event_type LIKE :s_type
                           OR c.club_name LIKE :s_club
                           OR tc.club_name LIKE :s_tclub)";
            $params['s_title'] = $search;
            $params['s_desc']  = $search;
            $params['s_loc']   = $search;
            $params['s_type']  = $search;
            $params['s_club']  = $search;
            $params['s_tclub'] = $search;
        }

        $sql .= " GROUP BY e.event_id ORDER BY e.start_datetime DESC, e.event_id DESC";

        return $this->resultSet($sql, $params);
    }

    /**
     * Retrieve all events nationwide across all levels (National, Zonal, Divisional, Club),
     * with comprehensive filtering options for NYSC Administrator.
     *
     * @param  array $filters
     * @return array
     */
    public function getAllEventsForAdmin(array $filters = []) {
        $sql = "SELECT 
                    e.*,
                    d.division_name AS organizer_division_name,
                    z.zonal_name AS organizer_zonal_name,
                    c.club_name AS organizer_club_name,
                    c.club_code AS organizer_club_code,
                    c.division_id AS club_division_id,
                    cd.division_name AS club_division_name,
                    cz.zonal_name AS club_zonal_name,
                    CASE
                        WHEN e.organizer_division_id IS NULL AND e.organizer_club_id IS NULL AND e.organizer_zonal_id IS NULL THEN 'National'
                        WHEN e.organizer_zonal_id IS NOT NULL THEN 'Zonal'
                        WHEN e.organizer_division_id IS NOT NULL THEN 'Divisional'
                        WHEN e.organizer_club_id IS NOT NULL THEN 'Club'
                        ELSE 'National'
                    END AS event_level,
                    GROUP_CONCAT(DISTINCT tc.club_name ORDER BY tc.club_name ASC SEPARATOR ', ') AS target_club_names,
                    GROUP_CONCAT(DISTINCT et.target_club_id ORDER BY et.target_club_id ASC SEPARATOR ',') AS target_club_ids,
                    CONCAT(uc.first_name, ' ', uc.last_name) AS creator_name,
                    uc.role AS creator_role,
                    CONCAT(ua.first_name, ' ', ua.last_name) AS approver_name
                FROM Event e
                LEFT JOIN Division d ON e.organizer_division_id = d.division_id
                LEFT JOIN Zone z ON (e.organizer_zonal_id = z.zonal_id OR d.zonal_id = z.zonal_id)
                LEFT JOIN Club c ON e.organizer_club_id = c.club_id
                LEFT JOIN Division cd ON c.division_id = cd.division_id
                LEFT JOIN Zone cz ON cd.zonal_id = cz.zonal_id
                LEFT JOIN EventTarget et ON e.event_id = et.event_id
                LEFT JOIN Club tc ON et.target_club_id = tc.club_id
                LEFT JOIN User uc ON e.created_by = uc.user_id
                LEFT JOIN User ua ON e.approved_by = ua.user_id
                WHERE 1=1";

        $params = [];

        // Filter: Event Level (National, Zonal, Divisional, Club)
        if (!empty($filters['event_level']) && $filters['event_level'] !== 'All') {
            switch ($filters['event_level']) {
                case 'National':
                    $sql .= " AND (e.organizer_division_id IS NULL AND e.organizer_club_id IS NULL AND e.organizer_zonal_id IS NULL)";
                    break;
                case 'Zonal':
                    $sql .= " AND e.organizer_zonal_id IS NOT NULL";
                    break;
                case 'Divisional':
                    $sql .= " AND e.organizer_division_id IS NOT NULL";
                    break;
                case 'Club':
                    $sql .= " AND e.organizer_club_id IS NOT NULL";
                    break;
            }
        }

        // Filter: Zone
        if (!empty($filters['zone_id'])) {
            $sql .= " AND (e.organizer_zonal_id = :zone_id OR d.zonal_id = :zone_id_d OR cd.zonal_id = :zone_id_c)";
            $params['zone_id']   = (int)$filters['zone_id'];
            $params['zone_id_d'] = (int)$filters['zone_id'];
            $params['zone_id_c'] = (int)$filters['zone_id'];
        }

        // Filter: Division
        if (!empty($filters['division_id'])) {
            $sql .= " AND (e.organizer_division_id = :division_id OR c.division_id = :division_id_c)";
            $params['division_id']   = (int)$filters['division_id'];
            $params['division_id_c'] = (int)$filters['division_id'];
        }

        // Filter: Club (either organizer club or target club)
        if (!empty($filters['club_id'])) {
            $sql .= " AND (e.organizer_club_id = :club_id OR et.target_club_id = :club_id_t)";
            $params['club_id']   = (int)$filters['club_id'];
            $params['club_id_t'] = (int)$filters['club_id'];
        }

        // Filter: Status
        if (!empty($filters['status']) && $filters['status'] !== 'All') {
            $sql .= " AND e.status = :status";
            $params['status'] = $filters['status'];
        }

        // Filter: Event Type
        if (!empty($filters['event_type'])) {
            $sql .= " AND e.event_type = :event_type";
            $params['event_type'] = $filters['event_type'];
        }

        // Filter: Target Audience scope
        if (!empty($filters['target_scope'])) {
            if ($filters['target_scope'] === 'AllInScope') {
                $sql .= " AND e.target_scope = 'AllInScope'";
            } elseif ($filters['target_scope'] === 'SelectedClubs') {
                $sql .= " AND e.target_scope = 'SelectedClubs'";
            }
        }

        // Filter: Specific Target Club within SelectedClubs
        if (!empty($filters['target_club_id'])) {
            $sql .= " AND et.target_club_id = :target_club_id";
            $params['target_club_id'] = (int)$filters['target_club_id'];
        }

        // Filter: Date Range
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(e.start_datetime) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(e.start_datetime) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        // Filter: Free-text search
        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $sql .= " AND (e.title LIKE :s_title 
                           OR e.description LIKE :s_desc 
                           OR e.location LIKE :s_loc 
                           OR e.event_type LIKE :s_type
                           OR c.club_name LIKE :s_club
                           OR d.division_name LIKE :s_div
                           OR z.zonal_name LIKE :s_zone
                           OR tc.club_name LIKE :s_tclub
                           OR CONCAT(uc.first_name, ' ', uc.last_name) LIKE :s_creator)";
            $params['s_title']   = $search;
            $params['s_desc']    = $search;
            $params['s_loc']     = $search;
            $params['s_type']    = $search;
            $params['s_club']    = $search;
            $params['s_div']     = $search;
            $params['s_zone']    = $search;
            $params['s_tclub']   = $search;
            $params['s_creator'] = $search;
        }

        $sql .= " GROUP BY e.event_id ORDER BY e.start_datetime DESC, e.event_id DESC";

        return $this->resultSet($sql, $params);
    }

    /**
     * Compute national stat card metrics across all levels.
     *
     * @return array
     */
    public function getNationalStats() {
        // 1. Awaiting Approval (All PendingApproval events)
        $awaiting = $this->single(
            "SELECT COUNT(*) AS total FROM Event WHERE status = 'PendingApproval'"
        );

        // 2. Approved / Upcoming (All Approved events with end_datetime >= NOW())
        $approvedUpcoming = $this->single(
            "SELECT COUNT(*) AS total FROM Event WHERE status = 'Approved' AND end_datetime >= NOW()"
        );

        // 3. Hosted This Year (All events with start_datetime in current calendar year)
        $hostedThisYear = $this->single(
            "SELECT COUNT(*) AS total FROM Event WHERE YEAR(start_datetime) = YEAR(CURDATE())"
        );

        // 4. Total National Events
        $nationalEvents = $this->single(
            "SELECT COUNT(*) AS total FROM Event WHERE organizer_division_id IS NULL AND organizer_club_id IS NULL AND organizer_zonal_id IS NULL"
        );

        return [
            'awaiting_approval' => (int)($awaiting->total ?? 0),
            'approved_upcoming' => (int)($approvedUpcoming->total ?? 0),
            'hosted_this_year'  => (int)($hostedThisYear->total ?? 0),
            'national_events'   => (int)($nationalEvents->total ?? 0),
        ];
    }

    /**
     * Fetch all zones for filter dropdowns.
     *
     * @return array
     */
    public function getAllZones() {
        return $this->resultSet("SELECT zonal_id, zonal_name FROM Zone ORDER BY zonal_name ASC");
    }

    /**
     * Fetch all divisions with zone details for filter dropdowns.
     *
     * @return array
     */
    public function getAllDivisions() {
        return $this->resultSet("SELECT d.division_id, d.division_name, d.zonal_id, z.zonal_name 
                                  FROM Division d 
                                  LEFT JOIN Zone z ON d.zonal_id = z.zonal_id 
                                  ORDER BY d.division_name ASC");
    }

    /**
     * Fetch all active clubs nationwide with division and zone info.
     *
     * @return array
     */
    public function getAllClubs() {
        return $this->resultSet("SELECT c.club_id, c.club_name, c.club_code, c.division_id, d.division_name, d.zonal_id, z.zonal_name
                                  FROM Club c
                                  LEFT JOIN Division d ON c.division_id = d.division_id
                                  LEFT JOIN Zone z ON d.zonal_id = z.zonal_id
                                  WHERE c.status != 'Disbanded'
                                  ORDER BY c.club_name ASC");
    }

    /**
     * Fetch all distinct event types nationwide across all events.
     *
     * @return array
     */
    public function getAllUniqueEventTypes() {
        return $this->resultSet("SELECT DISTINCT event_type 
                                  FROM Event 
                                  WHERE event_type IS NOT NULL AND event_type != '' 
                                  ORDER BY event_type ASC");
    }

    /**
     * Compute stat card metrics for the division.
     *
     * @param  int $divisionId
     * @return array
     */
    public function getDivisionStats($divisionId) {
        $scopeSql = "(e.organizer_division_id = ? OR e.organizer_club_id IN (SELECT club_id FROM Club WHERE division_id = ?))";

        // 1. Awaiting Approval (PendingApproval)
        $awaiting = $this->single(
            "SELECT COUNT(*) AS total FROM Event e WHERE {$scopeSql} AND e.status = 'PendingApproval'",
            [$divisionId, $divisionId]
        );

        // 2. Approved / Upcoming (Approved and end_datetime >= NOW())
        $approvedUpcoming = $this->single(
            "SELECT COUNT(*) AS total FROM Event e WHERE {$scopeSql} AND e.status = 'Approved' AND e.end_datetime >= NOW()",
            [$divisionId, $divisionId]
        );

        // 3. Hosted This Year (Current calendar year start_datetime)
        $hostedThisYear = $this->single(
            "SELECT COUNT(*) AS total FROM Event e WHERE {$scopeSql} AND YEAR(e.start_datetime) = YEAR(CURDATE())",
            [$divisionId, $divisionId]
        );

        return [
            'awaiting_approval' => (int)($awaiting->total ?? 0),
            'approved_upcoming' => (int)($approvedUpcoming->total ?? 0),
            'hosted_this_year'  => (int)($hostedThisYear->total ?? 0),
        ];
    }

    /**
     * Fetch all clubs in a division for the Target Audience picker.
     *
     * @param  int $divisionId
     * @return array
     */
    public function getClubsByDivision($divisionId) {
        $sql = "SELECT club_id, club_name, club_code, status 
                FROM Club 
                WHERE division_id = ? AND status != 'Disbanded' 
                ORDER BY club_name ASC";

        return $this->resultSet($sql, [$divisionId]);
    }

    /**
     * Fetch distinct event types that exist in a division's events.
     * Used to populate the Event Type filter dropdown.
     *
     * @param  int $divisionId
     * @return array
     */
    public function getUniqueEventTypes($divisionId) {
        $sql = "SELECT DISTINCT event_type 
                FROM Event 
                WHERE (organizer_division_id = ? 
                       OR organizer_club_id IN (SELECT club_id FROM Club WHERE division_id = ?))
                  AND event_type IS NOT NULL 
                  AND event_type != ''
                ORDER BY event_type ASC";

        return $this->resultSet($sql, [$divisionId, $divisionId]);
    }

    /**
     * Fetch division details by division_id.
     *
     * @param  int $divisionId
     * @return object|false
     */
    public function getDivisionById($divisionId) {
        return $this->single(
            "SELECT division_id, division_name, zonal_id FROM Division WHERE division_id = ? LIMIT 1",
            [$divisionId]
        );
    }

    /**
     * Find pending events for a division awaiting coordinator approval.
     * Includes both Divisional events and Club events in this division.
     *
     * @param  int $divisionId
     * @return array
     */
    public function findPendingClubEventsByDivision($divisionId) {
        $sql = "SELECT e.*, c.club_name, c.club_code,
                       d.division_name AS organizer_division_name,
                       u.first_name, u.last_name, u.role AS creator_role,
                       CONCAT(u.first_name, ' ', u.last_name) AS creator_name
                FROM Event e
                LEFT JOIN Club c ON e.organizer_club_id = c.club_id
                LEFT JOIN Division d ON e.organizer_division_id = d.division_id
                JOIN User u ON e.created_by = u.user_id
                WHERE (e.organizer_division_id = ? OR c.division_id = ?)
                  AND e.status = 'PendingApproval'
                ORDER BY e.created_at ASC";

        return $this->resultSet($sql, [$divisionId, $divisionId]);
    }

    /**
     * Find events for a division filtered by status.
     *
     * @param  int    $divisionId
     * @param  string $status  PendingApproval | Approved | Rejected
     * @return array
     */
    public function findClubEventsByDivisionAndStatus($divisionId, $status) {
        $sql = "SELECT e.*, c.club_name, c.club_code,
                       d.division_name AS organizer_division_name,
                       u.first_name, u.last_name, u.role AS creator_role,
                       CONCAT(u.first_name, ' ', u.last_name) AS creator_name,
                       appr.first_name AS approver_first_name, appr.last_name AS approver_last_name,
                       CONCAT(appr.first_name, ' ', appr.last_name) AS approver_name
                FROM Event e
                LEFT JOIN Club c ON e.organizer_club_id = c.club_id
                LEFT JOIN Division d ON e.organizer_division_id = d.division_id
                JOIN User u ON e.created_by = u.user_id
                LEFT JOIN User appr ON e.approved_by = appr.user_id
                WHERE (e.organizer_division_id = ? OR c.division_id = ?)
                  AND e.status = ?
                ORDER BY e.start_datetime DESC";

        return $this->resultSet($sql, [(int)$divisionId, (int)$divisionId, $status]);
    }

    /**
     * Count events in a division by status.
     *
     * @param  int    $divisionId
     * @param  string $status
     * @return int
     */
    public function countClubEventsByDivisionAndStatus($divisionId, $status) {
        $sql = "SELECT COUNT(*) AS total
                FROM Event e
                LEFT JOIN Club c ON e.organizer_club_id = c.club_id
                WHERE (e.organizer_division_id = ? OR c.division_id = ?)
                  AND e.status = ?";

        $res = $this->single($sql, [$divisionId, $divisionId, $status]);
        return (int)($res->total ?? 0);
    }

    /**
     * Update the status and approval details of an event.
     *
     * @param  int         $eventId
     * @param  string      $status
     * @param  int         $userId
     * @param  string|null $remarks
     * @return void
     */
    public function updateEventStatus($eventId, $status, $userId, $remarks = null) {
        $sql    = "UPDATE Event SET status = ?, approved_by = ?";
        $params = [$status, $userId];

        if ($remarks !== null) {
            $sql    .= ", rejection_remarks = ?";
            $params[] = $remarks;
        }

        $sql    .= " WHERE event_id = ?";
        $params[] = $eventId;

        $this->query($sql, $params);
    }
}
