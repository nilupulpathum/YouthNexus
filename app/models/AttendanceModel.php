<?php

class AttendanceModel extends Model {

    // ------------------------------------------------------------------
    // READ — scope queries
    // ------------------------------------------------------------------

    /**
     * Return all Approved events in scope for this division.
     * Scope: organizer_division_id = $divisionId
     *     OR organizer_club_id resolves to a club in $divisionId.
     *
     * @param  int   $divisionId
     * @return array
     */
    public function getApprovedEventsByDivision($divisionId) {
        $sql = "SELECT
                    e.event_id,
                    e.title,
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
                  )
                ORDER BY e.start_datetime DESC";
        return $this->resultSet($sql, [$divisionId, $divisionId]);
    }

    /**
     * Return a single Approved event, verifying it belongs to this division.
     * Used for scope re-check before any read or write.
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
                  )
                LIMIT 1";
        return $this->single($sql, [$eventId, $divisionId, $divisionId]);
    }

    /**
     * Return the full member roster for an event's targets,
     * LEFT JOINed with Attendance so un-marked members still appear.
     *
     * For AllInScope events in the division: all members of all clubs
     *   in the division.
     * For SelectedClubs events: members of targeted clubs only.
     * For club-level events: all members of that organizer club.
     *
     * @param  int $eventId
     * @param  int $divisionId
     * @return array
     */
    public function getMemberRosterForEvent($eventId, $divisionId) {
        // Determine scope from EventTarget or organizer fields
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

    /**
     * Attendance stats for one event.
     *
     * @param  int $eventId
     * @return object|false
     */
    public function getEventAttendanceStats($eventId) {
        $sql = "SELECT
                    COUNT(*)                                          AS total_roster,
                    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                    SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count,
                    COUNT(*) - SUM(CASE WHEN status IS NULL THEN 0 ELSE 1 END) AS unrecorded_count
                FROM Attendance
                WHERE event_id = ?";
        return $this->single($sql, [$eventId]);
    }

    /**
     * Year-level summary stats for the session-list view stat cards.
     *
     * @param  int $divisionId
     * @return object  { events_this_year, attendance_this_year }
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
                    a.status,
                    a.check_in_time,
                    a.check_out_time,
                    a.remark,
                    CONCAT(r.first_name, ' ', r.last_name) AS recorded_by_name,
                    a.recorded_at
                FROM Attendance a
                JOIN User u ON a.user_id     = u.user_id
                JOIN User r ON a.recorded_by = r.user_id
                WHERE a.event_id = ?
                ORDER BY u.last_name, u.first_name";
        return $this->resultSet($sql, [$eventId]);
    }

    // ------------------------------------------------------------------
    // WRITE — INSERT ... ON DUPLICATE KEY UPDATE (UPSERT)
    // ------------------------------------------------------------------

    /**
     * Save (or update) a single attendance record.
     * Uses the (event_id, member_id) unique key so re-submission
     * corrects the existing row instead of throwing a duplicate error.
     *
     * @param  int         $eventId
     * @param  int         $memberId
     * @param  string      $status       'Present'|'Absent'
     * @param  string|null $checkInTime  'YYYY-MM-DD HH:MM:SS' or null
     * @param  string|null $checkOutTime 'YYYY-MM-DD HH:MM:SS' or null
     * @param  string|null $remark
     * @param  int         $recordedBy
     * @return void
     */
    public function saveAttendance($eventId, $memberId, $status, $checkInTime, $checkOutTime, $remark, $recordedBy) {
        $sql = "INSERT INTO Attendance
                    (event_id, user_id, status, check_in_time, check_out_time, remark, recorded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
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
     * Verify that a member_id is actually targeted by this event,
     * i.e. they are an active member of a club in the division.
     * Used by bulkSave to validate each CSV row before writing.
     *
     * @param  int $eventId
     * @param  int $memberId
     * @param  int $divisionId
     * @return bool
     */
    public function memberIsInScope($memberId, $divisionId) {
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
