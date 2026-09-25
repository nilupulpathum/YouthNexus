<?php

class AttendanceModel extends Model {

    // ==================================================================
    // NYSC ADMINISTRATOR — NATIONAL SCOPE & CASCADING FILTERS
    // ==================================================================

    /**
     * Return all active zones for the cascading filter.
     *
     * @return array
     */
    public function getAllZones() {
        return $this->resultSet(
            "SELECT zonal_id, zonal_name, province, hub_name
             FROM Zone
             ORDER BY zonal_name ASC"
        );
    }

    /**
     * Return divisions, optionally filtered by zonal_id.
     *
     * @param  int|null $zonalId
     * @return array
     */
    public function getDivisionsByZone($zonalId = null) {
        if (!empty($zonalId)) {
            return $this->resultSet(
                "SELECT division_id, division_name, zonal_id
                 FROM Division
                 WHERE zonal_id = ?
                 ORDER BY division_name ASC",
                [(int)$zonalId]
            );
        }
        return $this->resultSet(
            "SELECT division_id, division_name, zonal_id
             FROM Division
             ORDER BY division_name ASC"
        );
    }

    /**
     * Return clubs, optionally filtered by division_id or zonal_id.
     *
     * @param  int|null $divisionId
     * @param  int|null $zonalId
     * @return array
     */
    public function getClubsByScope($divisionId = null, $zonalId = null) {
        if (!empty($divisionId)) {
            return $this->resultSet(
                "SELECT c.club_id, c.club_name, c.club_code, c.division_id
                 FROM Club c
                 WHERE c.division_id = ? AND c.status = 'Active'
                 ORDER BY c.club_name ASC",
                [(int)$divisionId]
            );
        }
        if (!empty($zonalId)) {
            return $this->resultSet(
                "SELECT c.club_id, c.club_name, c.club_code, c.division_id
                 FROM Club c
                 JOIN Division d ON c.division_id = d.division_id
                 WHERE d.zonal_id = ? AND c.status = 'Active'
                 ORDER BY c.club_name ASC",
                [(int)$zonalId]
            );
        }
        return $this->resultSet(
            "SELECT c.club_id, c.club_name, c.club_code, c.division_id
             FROM Club c
             WHERE c.status = 'Active'
             ORDER BY c.club_name ASC"
        );
    }

    /**
     * Return Approved events for NYSC Admin with multi-level filtering:
     * - zonal level (zone_id)
     * - divisional level (division_id)
     * - club level (club_id)
     * - organizer level ('National' | 'Zonal' | 'Division' | 'Club')
     * - event_type
     * - search keyword
     *
     * @param  array $filters
     * @return array
     */
    public function getApprovedEventsNational(array $filters = []) {
        $sql = "SELECT
                    e.event_id,
                    e.title,
                    e.description,
                    e.event_type,
                    e.start_datetime,
                    e.end_datetime,
                    e.location,
                    e.target_scope,
                    e.max_attendance,
                    e.organizer_club_id,
                    e.organizer_division_id,
                    e.organizer_zonal_id,
                    c.club_name      AS organizer_club_name,
                    c.club_code      AS organizer_club_code,
                    d.division_name  AS organizer_division_name,
                    d.division_id    AS event_division_id,
                    z.zonal_name     AS organizer_zonal_name,
                    z.zonal_id       AS event_zonal_id,
                    CASE
                        WHEN e.organizer_club_id IS NOT NULL THEN 'Club'
                        WHEN e.organizer_division_id IS NOT NULL THEN 'Division'
                        WHEN e.organizer_zonal_id IS NOT NULL THEN 'Zonal'
                        ELSE 'National'
                    END AS organizer_level,
                    (SELECT COUNT(*) FROM Attendance a WHERE a.event_id = e.event_id) AS attendance_recorded,
                    (SELECT COUNT(*) FROM Attendance a WHERE a.event_id = e.event_id AND a.status = 'Present') AS present_count,
                    (SELECT COUNT(*) FROM Attendance a WHERE a.event_id = e.event_id AND a.status = 'Absent')  AS absent_count
                FROM Event e
                LEFT JOIN Club     c ON e.organizer_club_id     = c.club_id
                LEFT JOIN Division d ON (e.organizer_division_id = d.division_id OR c.division_id = d.division_id)
                LEFT JOIN Zone     z ON (e.organizer_zonal_id   = z.zonal_id    OR d.zonal_id = z.zonal_id)
                WHERE e.status = 'Approved'";

        $params = [];

        // 1. Zonal filter
        if (!empty($filters['zone_id'])) {
            $sql .= " AND (z.zonal_id = :zone_id_1 OR e.organizer_zonal_id = :zone_id_2)";
            $params['zone_id_1'] = (int)$filters['zone_id'];
            $params['zone_id_2'] = (int)$filters['zone_id'];
        }

        // 2. Divisional filter
        if (!empty($filters['division_id'])) {
            $sql .= " AND (d.division_id = :division_id_1 OR e.organizer_division_id = :division_id_2)";
            $params['division_id_1'] = (int)$filters['division_id'];
            $params['division_id_2'] = (int)$filters['division_id'];
        }

        // 3. Club filter
        if (!empty($filters['club_id'])) {
            $sql .= " AND e.organizer_club_id = :club_id";
            $params['club_id'] = (int)$filters['club_id'];
        }

        // 4. Organizer Level filter ('National', 'Zonal', 'Division', 'Club')
        if (!empty($filters['level']) && $filters['level'] !== 'all') {
            $lvl = strtolower($filters['level']);
            if ($lvl === 'national') {
                $sql .= " AND e.organizer_club_id IS NULL AND e.organizer_division_id IS NULL AND e.organizer_zonal_id IS NULL";
            } elseif ($lvl === 'zonal') {
                $sql .= " AND e.organizer_zonal_id IS NOT NULL";
            } elseif ($lvl === 'division' || $lvl === 'divisional') {
                $sql .= " AND e.organizer_division_id IS NOT NULL";
            } elseif ($lvl === 'club') {
                $sql .= " AND e.organizer_club_id IS NOT NULL";
            }
        }

        // 5. Event Type filter
        if (!empty($filters['event_type'])) {
            $sql .= " AND e.event_type = :event_type";
            $params['event_type'] = $filters['event_type'];
        }

        // 6. Search keyword
        if (!empty($filters['search'])) {
            $s = '%' . trim($filters['search']) . '%';
            $sql .= " AND (e.title LIKE :s_title
                           OR e.location LIKE :s_loc
                           OR c.club_name LIKE :s_club
                           OR d.division_name LIKE :s_div
                           OR z.zonal_name LIKE :s_zone)";
            $params['s_title'] = $s;
            $params['s_loc']   = $s;
            $params['s_club']  = $s;
            $params['s_div']   = $s;
            $params['s_zone']  = $s;
        }

        $sql .= " ORDER BY e.start_datetime DESC";

        return $this->resultSet($sql, $params);
    }

    /**
     * National Attendance Stats for NYSC Admin dashboard cards.
     *
     * @return object
     */
    public function getNationalAttendanceStats() {
        $sql = "SELECT
                    (SELECT COUNT(*) FROM Event WHERE status = 'Approved' AND YEAR(start_datetime) = YEAR(CURDATE())) AS events_this_year,
                    (SELECT COUNT(*) FROM Attendance WHERE YEAR(recorded_at) = YEAR(CURDATE())) AS attendance_this_year,
                    (SELECT COUNT(*) FROM Attendance WHERE status = 'Present' AND YEAR(recorded_at) = YEAR(CURDATE())) AS present_this_year,
                    (SELECT COUNT(*) FROM Attendance WHERE status = 'Absent' AND YEAR(recorded_at) = YEAR(CURDATE())) AS absent_this_year";

        $res = $this->single($sql);
        $total = (int)($res->attendance_this_year ?? 0);
        $present = (int)($res->present_this_year ?? 0);
        $rate = $total > 0 ? round(($present / $total) * 100) : 0;

        if ($res) {
            $res->national_rate = $rate;
        }
        return $res;
    }

    /**
     * Return single approved event by ID with all geographic hierarchy info.
     *
     * @param  int $eventId
     * @return object|false
     */
    public function getApprovedEventNational($eventId) {
        $sql = "SELECT e.*,
                       c.club_name       AS organizer_club_name,
                       c.club_code       AS organizer_club_code,
                       c.division_id     AS club_division_id,
                       d.division_name   AS organizer_division_name,
                       d.zonal_id        AS division_zonal_id,
                       z.zonal_name      AS organizer_zonal_name,
                       CASE
                           WHEN e.organizer_club_id IS NOT NULL THEN 'Club'
                           WHEN e.organizer_division_id IS NOT NULL THEN 'Division'
                           WHEN e.organizer_zonal_id IS NOT NULL THEN 'Zonal'
                           ELSE 'National'
                       END AS organizer_level
                FROM Event e
                LEFT JOIN Club     c ON e.organizer_club_id     = c.club_id
                LEFT JOIN Division d ON (e.organizer_division_id = d.division_id OR c.division_id = d.division_id)
                LEFT JOIN Zone     z ON (e.organizer_zonal_id   = z.zonal_id    OR d.zonal_id = z.zonal_id)
                WHERE e.event_id = ? AND e.status = 'Approved'
                LIMIT 1";
        return $this->single($sql, [(int)$eventId]);
    }

    /**
     * Member roster for NYSC Admin view:
     * Resolves all members relevant to event scope, with attendance status,
     * check-in time, remark, recorder user name, and recorded_at timestamp.
     *
     * @param  int $eventId
     * @return array
     */
    public function getMemberRosterForEventNational($eventId) {
        $event = $this->getApprovedEventNational($eventId);
        if (!$event) {
            return [];
        }

        // If target_scope is SelectedClubs, retrieve targeted clubs
        if ($event->target_scope === 'SelectedClubs') {
            require_once __DIR__ . '/EventTargetModel.php';
            $eventTargetModel = new EventTargetModel();
            $targets = $eventTargetModel->findByEventId($eventId);
            $clubIds = [];
            foreach ($targets as $t) {
                if (!empty($t->target_club_id)) {
                    $clubIds[] = (int)$t->target_club_id;
                }
            }
            if (!empty($clubIds)) {
                $placeholders = implode(',', array_fill(0, count($clubIds), '?'));
                $sql = "SELECT
                            u.user_id,
                            u.user_id AS member_id,
                            u.first_name,
                            u.last_name,
                            CONCAT(u.first_name, ' ', u.last_name) AS member_name,
                            u.email,
                            u.role,
                            cu.club_name,
                            cu.club_code,
                            dv.division_name,
                            zn.zonal_name,
                            a.attendance_id,
                            a.status        AS att_status,
                            a.check_in_time,
                            a.check_out_time,
                            a.remark,
                            a.location      AS att_location,
                            a.photo_url,
                            a.recorded_at,
                            CONCAT(rec.first_name, ' ', rec.last_name) AS recorded_by_name,
                            rec.role        AS recorded_by_role
                        FROM User u
                        JOIN Club cu          ON cu.club_id     = u.club_id
                        LEFT JOIN Division dv ON cu.division_id = dv.division_id
                        LEFT JOIN Zone zn     ON dv.zonal_id    = zn.zonal_id
                        LEFT JOIN Attendance a ON (a.event_id = ? AND a.user_id = u.user_id)
                        LEFT JOIN User rec    ON a.recorded_by  = rec.user_id
                        WHERE cu.club_id IN ($placeholders)
                          AND u.membership_status = 'Active'
                        ORDER BY cu.club_name, u.last_name, u.first_name";
                return $this->resultSet($sql, array_merge([$eventId], $clubIds));
            }
        }

        // If organizer is a club: roster defaults to that club's members + any logged attendance
        if (!empty($event->organizer_club_id)) {
            $sql = "SELECT
                        u.user_id,
                        u.user_id AS member_id,
                        u.first_name,
                        u.last_name,
                        CONCAT(u.first_name, ' ', u.last_name) AS member_name,
                        u.email,
                        u.role,
                        cu.club_name,
                        cu.club_code,
                        dv.division_name,
                        zn.zonal_name,
                        a.attendance_id,
                        a.status        AS att_status,
                        a.check_in_time,
                        a.check_out_time,
                        a.remark,
                        a.location      AS att_location,
                        a.photo_url,
                        a.recorded_at,
                        CONCAT(rec.first_name, ' ', rec.last_name) AS recorded_by_name,
                        rec.role        AS recorded_by_role
                    FROM User u
                    JOIN Club cu          ON cu.club_id     = u.club_id
                    LEFT JOIN Division dv ON cu.division_id = dv.division_id
                    LEFT JOIN Zone zn     ON dv.zonal_id    = zn.zonal_id
                    LEFT JOIN Attendance a ON (a.event_id = ? AND a.user_id = u.user_id)
                    LEFT JOIN User rec    ON a.recorded_by  = rec.user_id
                    WHERE cu.club_id = ?
                      AND u.membership_status = 'Active'
                    ORDER BY cu.club_name, u.last_name, u.first_name";
            return $this->resultSet($sql, [$eventId, $event->organizer_club_id]);
        }

        // If organizer is a division: roster of clubs in that division
        if (!empty($event->organizer_division_id)) {
            $sql = "SELECT
                        u.user_id,
                        u.user_id AS member_id,
                        u.first_name,
                        u.last_name,
                        CONCAT(u.first_name, ' ', u.last_name) AS member_name,
                        u.email,
                        u.role,
                        cu.club_name,
                        cu.club_code,
                        dv.division_name,
                        zn.zonal_name,
                        a.attendance_id,
                        a.status        AS att_status,
                        a.check_in_time,
                        a.check_out_time,
                        a.remark,
                        a.location      AS att_location,
                        a.photo_url,
                        a.recorded_at,
                        CONCAT(rec.first_name, ' ', rec.last_name) AS recorded_by_name,
                        rec.role        AS recorded_by_role
                    FROM User u
                    JOIN Club cu          ON cu.club_id     = u.club_id
                    LEFT JOIN Division dv ON cu.division_id = dv.division_id
                    LEFT JOIN Zone zn     ON dv.zonal_id    = zn.zonal_id
                    LEFT JOIN Attendance a ON (a.event_id = ? AND a.user_id = u.user_id)
                    LEFT JOIN User rec    ON a.recorded_by  = rec.user_id
                    WHERE cu.division_id = ?
                      AND u.membership_status = 'Active'
                    ORDER BY cu.club_name, u.last_name, u.first_name";
            return $this->resultSet($sql, [$eventId, $event->organizer_division_id]);
        }

        // For Zonal or National events: union of all attendees logged + active members
        $sql = "SELECT
                    u.user_id,
                    u.user_id AS member_id,
                    u.first_name,
                    u.last_name,
                    CONCAT(u.first_name, ' ', u.last_name) AS member_name,
                    u.email,
                    u.role,
                    cu.club_name,
                    cu.club_code,
                    dv.division_name,
                    zn.zonal_name,
                    a.attendance_id,
                    a.status        AS att_status,
                    a.check_in_time,
                    a.check_out_time,
                    a.remark,
                    a.location      AS att_location,
                    a.photo_url,
                    a.recorded_at,
                    CONCAT(rec.first_name, ' ', rec.last_name) AS recorded_by_name,
                    rec.role        AS recorded_by_role
                FROM Attendance a
                JOIN User u           ON a.user_id      = u.user_id
                LEFT JOIN Club cu     ON u.club_id      = cu.club_id
                LEFT JOIN Division dv ON cu.division_id = dv.division_id
                LEFT JOIN Zone zn     ON dv.zonal_id    = zn.zonal_id
                LEFT JOIN User rec    ON a.recorded_by  = rec.user_id
                WHERE a.event_id = ?
                ORDER BY cu.club_name, u.last_name, u.first_name";
        $logged = $this->resultSet($sql, [$eventId]);

        if (!empty($logged)) {
            return $logged;
        }

        // Fallback: active members from system
        $sqlFallback = "SELECT
                            u.user_id,
                            u.user_id AS member_id,
                            u.first_name,
                            u.last_name,
                            CONCAT(u.first_name, ' ', u.last_name) AS member_name,
                            u.email,
                            u.role,
                            cu.club_name,
                            cu.club_code,
                            dv.division_name,
                            zn.zonal_name,
                            a.attendance_id,
                            a.status        AS att_status,
                            a.check_in_time,
                            a.check_out_time,
                            a.remark,
                            a.location      AS att_location,
                            a.photo_url,
                            a.recorded_at,
                            NULL            AS recorded_by_name,
                            NULL            AS recorded_by_role
                        FROM User u
                        JOIN Club cu          ON cu.club_id     = u.club_id
                        LEFT JOIN Division dv ON cu.division_id = dv.division_id
                        LEFT JOIN Zone zn     ON dv.zonal_id    = zn.zonal_id
                        LEFT JOIN Attendance a ON (a.event_id = ? AND a.user_id = u.user_id)
                        WHERE u.membership_status = 'Active'
                        LIMIT 50";
        return $this->resultSet($sqlFallback, [$eventId]);
    }

    // ==================================================================
    // DIVISIONAL SECRETARY — SCOPED QUERIES (Preserved exactly as-is)
    // ==================================================================

    /**
     * Return all Approved events in scope for this division.
     *
     * @param  int   $divisionId
     * @return array
     */
    public function getApprovedEventsByDivision($divisionId) {
        $sql = "SELECT
                    e.event_id,
                    e.title,
                    e.description,
                    e.event_type,
                    e.start_datetime,
                    e.end_datetime,
                    e.location,
                    e.target_scope,
                    e.organizer_division_id,
                    e.organizer_club_id,
                    c.club_name   AS organizer_club_name,
                    c.club_code   AS organizer_club_code,
                    d.division_name AS organizer_division_name,
                    'Division' AS organizer_level,
                    (SELECT COUNT(*) FROM Attendance a WHERE a.event_id = e.event_id)                        AS attendance_recorded,
                    (SELECT COUNT(*) FROM Attendance a WHERE a.event_id = e.event_id AND a.status = 'Present') AS present_count,
                    e.max_attendance
                FROM Event e
                LEFT JOIN Club     c ON e.organizer_club_id     = c.club_id
                LEFT JOIN Division d ON e.organizer_division_id = d.division_id
                WHERE e.status = 'Approved'
                  AND (
                        e.organizer_division_id = ?
                        OR
                        (e.organizer_club_id IS NOT NULL AND c.division_id = ?)
                        OR
                        (e.organizer_division_id IS NULL AND e.organizer_club_id IS NULL AND e.organizer_zonal_id IS NULL)
                  )
                ORDER BY e.start_datetime DESC";
        return $this->resultSet($sql, [$divisionId, $divisionId]);
    }

    /**
     * Return a single Approved event, verifying it belongs to this division or is a national event.
     *
     * @param  int $eventId
     * @param  int $divisionId
     * @return object|false
     */
    public function getApprovedEventInScope($eventId, $divisionId) {
        $sql = "SELECT e.*,
                       c.club_name   AS organizer_club_name,
                       c.club_code   AS organizer_club_code,
                       c.division_id AS club_division_id,
                       d.division_name AS organizer_division_name
                FROM Event e
                LEFT JOIN Club     c ON e.organizer_club_id     = c.club_id
                LEFT JOIN Division d ON e.organizer_division_id = d.division_id
                WHERE e.event_id = ?
                  AND e.status   = 'Approved'
                  AND (
                        e.organizer_division_id = ?
                        OR
                        (e.organizer_club_id IS NOT NULL AND c.division_id = ?)
                        OR
                        (e.organizer_division_id IS NULL AND e.organizer_club_id IS NULL AND e.organizer_zonal_id IS NULL)
                  )
                LIMIT 1";
        return $this->single($sql, [$eventId, $divisionId, $divisionId]);
    }

    /**
     * Return member roster for an event in division scope.
     *
     * @param  int    $eventId
     * @param  int    $divisionId
     * @param  string $targetScope
     * @return array
     */
    public function getMemberRosterForEvent($eventId, $divisionId, $targetScope = 'AllInScope') {
        if ($targetScope === 'SelectedClubs') {
            require_once __DIR__ . '/EventTargetModel.php';
            $eventTargetModel = new EventTargetModel();
            $targets = $eventTargetModel->findByEventId($eventId);
            $clubIds = [];
            foreach ($targets as $t) {
                if (!empty($t->target_club_id)) {
                    $clubIds[] = (int)$t->target_club_id;
                }
            }
            if (empty($clubIds)) {
                return [];
            }
            $placeholders = implode(',', array_fill(0, count($clubIds), '?'));
            $sql = "SELECT
                        u.user_id,
                        u.first_name,
                        u.last_name,
                        CONCAT(u.first_name, ' ', u.last_name) AS member_name,
                        u.email,
                        u.role,
                        cu.club_name,
                        cu.club_code,
                        a.attendance_id,
                        a.status        AS att_status,
                        a.check_in_time,
                        a.check_out_time,
                        a.remark,
                        a.recorded_at
                    FROM User u
                    JOIN Club           cu ON cu.club_id  = u.club_id
                    LEFT JOIN Attendance a ON a.event_id = ? AND a.user_id = u.user_id
                    WHERE cu.club_id IN ($placeholders)
                      AND u.status = 'Active'
                      AND u.membership_status = 'Active'
                    GROUP BY u.user_id
                    ORDER BY cu.club_name, u.last_name, u.first_name";
            return $this->resultSet($sql, array_merge([$eventId], $clubIds));
        } else {
            $sql = "SELECT
                        u.user_id,
                        u.first_name,
                        u.last_name,
                        CONCAT(u.first_name, ' ', u.last_name) AS member_name,
                        u.email,
                        u.role,
                        cu.club_name,
                        cu.club_code,
                        a.attendance_id,
                        a.status        AS att_status,
                        a.check_in_time,
                        a.check_out_time,
                        a.remark,
                        a.recorded_at
                    FROM User u
                    JOIN Club           cu ON cu.club_id  = u.club_id
                    LEFT JOIN Attendance a ON a.event_id = ? AND a.user_id = u.user_id
                    WHERE cu.division_id = ?
                      AND u.status = 'Active'
                      AND u.membership_status = 'Active'
                    GROUP BY u.user_id
                    ORDER BY cu.club_name, u.last_name, u.first_name";
            return $this->resultSet($sql, [$eventId, $divisionId]);
        }
    }

    /**
     * Attendance stats for one event.
     *
     * @param  int $eventId
     * @return object|false
     */
    public function getEventAttendanceStats($eventId) {
        $sql = "SELECT
                    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                    SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count
                FROM Attendance
                WHERE event_id = ?";
        return $this->single($sql, [(int)$eventId]);
    }

    /**
     * Year-level summary stats for the session-list view stat cards.
     *
     * @param  int $divisionId
     * @return object
     */
    public function getDivisionAttendanceStats($divisionId) {
        $sql = "SELECT
                    (SELECT COUNT(DISTINCT e.event_id)
                     FROM Event e
                     LEFT JOIN Club c ON e.organizer_club_id = c.club_id
                     WHERE e.status = 'Approved'
                       AND YEAR(e.start_datetime) = YEAR(CURDATE())
                       AND (e.organizer_division_id = ? OR c.division_id = ?)
                    ) AS events_this_year,
                    (SELECT COUNT(*)
                     FROM Attendance a
                     JOIN Event e ON a.event_id = e.event_id
                     LEFT JOIN Club c ON e.organizer_club_id = c.club_id
                     WHERE YEAR(a.recorded_at) = YEAR(CURDATE())
                       AND (e.organizer_division_id = ? OR c.division_id = ?)
                    ) AS attendance_this_year";
        return $this->single($sql, [$divisionId, $divisionId, $divisionId, $divisionId]);
    }

    /**
     * Return all attendance rows for an event (used for CSV download).
     *
     * @param  int $eventId
     * @return array
     */
    public function getAttendanceForEvent($eventId) {
        $sql = "SELECT
                    a.attendance_id,
                    a.user_id AS member_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS member_name,
                    u.email,
                    c.club_name,
                    a.status,
                    a.check_in_time,
                    a.check_out_time,
                    a.remark,
                    CONCAT(r.first_name, ' ', r.last_name) AS recorded_by_name,
                    r.role AS recorded_by_role,
                    a.recorded_at
                FROM Attendance a
                JOIN User u       ON a.user_id     = u.user_id
                LEFT JOIN Club c  ON u.club_id     = c.club_id
                LEFT JOIN User r  ON a.recorded_by = r.user_id
                WHERE a.event_id = ?
                ORDER BY u.last_name, u.first_name";
        return $this->resultSet($sql, [(int)$eventId]);
    }

    /**
     * Save (or update) a single attendance record.
     * Uses (event_id, user_id) unique key so re-submission updates existing row.
     *
     * @param  int         $eventId
     * @param  int         $memberId
     * @param  string      $status       'Present'|'Absent'
     * @param  string|null $checkInTime
     * @param  string|null $checkOutTime
     * @param  string|null $remark
     * @param  int         $recordedBy
     * @return void
     */
    public function saveAttendance($eventId, $memberId, $status, $checkInTime, $checkOutTime, $remark, $recordedBy) {
        $sql = "INSERT INTO Attendance
                    (event_id, user_id, status, check_in_time, check_out_time, remark, recorded_by, recorded_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    status         = VALUES(status),
                    check_in_time  = VALUES(check_in_time),
                    check_out_time = VALUES(check_out_time),
                    remark         = VALUES(remark),
                    recorded_by    = VALUES(recorded_by),
                    recorded_at    = CURRENT_TIMESTAMP";
        $this->query($sql, [
            (int)$eventId,
            (int)$memberId,
            $status,
            $checkInTime  ?: null,
            $checkOutTime ?: null,
            $remark       ?: null,
            (int)$recordedBy,
        ]);
    }

    /**
     * Verify that member is active and targeted by event.
     *
     * @param  int      $memberId
     * @param  int      $eventId
     * @param  int|null $divisionId
     * @param  string   $targetScope
     * @return bool
     */
    public function memberIsInScope($memberId, $eventId, $divisionId = null, $targetScope = null) {
        // If no divisionId provided (NYSC Admin national scope), check if user exists and is active
        if ($divisionId === null) {
            $user = $this->single("SELECT user_id FROM User WHERE user_id = ? AND membership_status = 'Active' LIMIT 1", [(int)$memberId]);
            return (bool)$user;
        }

        if ($targetScope === null) {
            $event = $this->single("SELECT target_scope FROM Event WHERE event_id = ? LIMIT 1", [$eventId]);
            $targetScope = $event ? $event->target_scope : 'AllInScope';
        }

        if ($targetScope === 'SelectedClubs') {
            require_once __DIR__ . '/EventTargetModel.php';
            $eventTargetModel = new EventTargetModel();
            $targets = $eventTargetModel->findByEventId($eventId);
            $clubIds = [];
            foreach ($targets as $t) {
                if (!empty($t->target_club_id)) {
                    $clubIds[] = (int)$t->target_club_id;
                }
            }
            if (empty($clubIds)) {
                return false;
            }
            $placeholders = implode(',', array_fill(0, count($clubIds), '?'));
            $sql = "SELECT 1
                    FROM User u
                    JOIN Club c ON u.club_id = c.club_id
                    WHERE u.user_id    = ?
                      AND c.club_id IN ($placeholders)
                      AND u.membership_status = 'Active'
                    LIMIT 1";
            return (bool)$this->single($sql, array_merge([(int)$memberId], $clubIds));
        } else {
            $sql = "SELECT 1
                    FROM User u
                    JOIN Club c ON u.club_id = c.club_id
                    WHERE u.user_id    = ?
                      AND c.division_id = ?
                      AND u.membership_status = 'Active'
                    LIMIT 1";
            return (bool)$this->single($sql, [(int)$memberId, (int)$divisionId]);
        }
    }

    // ==================================================================
    // CLUB SCOPE (D3: club dashboards mark their own attendance)
    // ==================================================================

    /**
     * Markable club events (approved or completed), newest first.
     */
    public function getClubMarkableEvents($clubId) {
        return $this->resultSet(
            "SELECT event_id, title, start_datetime, status
             FROM Event
             WHERE organizer_club_id = ? AND status IN ('Approved', 'Completed')
             ORDER BY start_datetime DESC",
            [(int) $clubId]
        );
    }

    /**
     * One event if it belongs to the club and is markable, else false.
     */
    public function getClubEventInScope($clubId, $eventId) {
        return $this->single(
            "SELECT event_id, title, start_datetime, status FROM Event
             WHERE event_id = ? AND organizer_club_id = ?
               AND status IN ('Approved', 'Completed') LIMIT 1",
            [(int) $eventId, (int) $clubId]
        );
    }

    /**
     * Active roster with this event's records (unmarked members included).
     */
    public function getClubEventRoster($clubId, $eventId) {
        return $this->resultSet(
            "SELECT u.user_id, u.first_name, u.last_name,
                    a.status AS att_status, a.check_in_time, a.check_out_time, a.remark
             FROM User u
             LEFT JOIN Attendance a ON a.user_id = u.user_id AND a.event_id = ?
             WHERE u.club_id = ? AND u.status = 'Active'
               AND COALESCE(u.membership_status, 'Active') = 'Active'
             ORDER BY u.first_name, u.last_name",
            [(int) $eventId, (int) $clubId]
        );
    }

    /**
     * Scoped upsert: event and member must both belong to the club.
     * Returns true on write, false on scope mismatch.
     */
    public function saveClubAttendance($clubId, $eventId, $memberId, $status, $in, $out, $remark, $by) {
        if (!in_array($status, ['Present', 'Absent'], true)) {
            return false;
        }
        $event = $this->getClubEventInScope($clubId, $eventId);
        if (!$event) {
            return false;
        }
        $member = $this->single(
            "SELECT user_id FROM User
             WHERE user_id = ? AND club_id = ? AND status = 'Active'
               AND COALESCE(membership_status, 'Active') = 'Active' LIMIT 1",
            [(int) $memberId, (int) $clubId]
        );
        if (!$member) {
            return false;
        }
        $this->saveAttendance($eventId, $memberId, $status, $in, $out, $remark, $by);
        return true;
    }

    /**
     * Member's own summary across club events.
     */
    public function getClubMemberSummary($clubId, $userId) {
        $history = $this->resultSet(
            "SELECT e.title, e.start_datetime, a.status
             FROM Attendance a
             JOIN Event e ON a.event_id = e.event_id
             WHERE a.user_id = ? AND e.organizer_club_id = ?
             ORDER BY e.start_datetime DESC",
            [(int) $userId, (int) $clubId]
        );
        $present = 0;
        foreach ($history as $h) {
            if ($h->status === 'Present') {
                $present++;
            }
        }
        $sessions = count($history);
        return [
            'sessions' => $sessions,
            'rate'     => $sessions > 0 ? (int) round($present * 100 / $sessions) . '%' : '—',
            'absent'   => $sessions - $present,
            'history'  => $history,
        ];
    }
}
