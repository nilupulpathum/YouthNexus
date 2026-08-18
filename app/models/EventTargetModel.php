<?php

class EventTargetModel extends Model {

    /**
     * Insert an event target record.
     * Now supports max_attendance override per club.
     *
     * @param  int      $eventId
     * @param  int|null $targetClubId
     * @param  int|null $maxAttendance  Per-club override (NULL = use event-level default)
     * @param  int|null $targetDivisionId
     * @param  int|null $targetZonalId
     * @return int      Inserted target_id
     */
    public function createTarget($eventId, $targetClubId = null, $maxAttendance = null, $targetDivisionId = null, $targetZonalId = null) {
        $sql = "INSERT INTO EventTarget (event_id, target_club_id, max_attendance, target_division_id, target_zonal_id)
                VALUES (?, ?, ?, ?, ?)";

        $this->query($sql, [$eventId, $targetClubId, $maxAttendance, $targetDivisionId, $targetZonalId]);
        return (int)Database::getInstance()->getConnection()->lastInsertId();
    }

    /**
     * Save (replace) all target rows for an event atomically.
     * Deletes all existing rows first, then inserts each entry in $targets.
     *
     * $targets is an array of:
     *   ['club_id' => int, 'max_attendance' => int|null]
     * If $targets is empty (AllInScope), all rows are deleted.
     *
     * @param  int   $eventId
     * @param  array $targets
     * @return void
     */
    public function saveTargets($eventId, array $targets) {
        // Remove all existing targets for this event
        $this->query("DELETE FROM EventTarget WHERE event_id = ?", [$eventId]);

        foreach ($targets as $t) {
            $clubId        = !empty($t['club_id']) ? (int)$t['club_id'] : null;
            $maxAttendance = isset($t['max_attendance']) && $t['max_attendance'] !== '' ? (int)$t['max_attendance'] : null;
            $this->createTarget($eventId, $clubId, $maxAttendance);
        }
    }

    /**
     * Find targets for a specific event, with club names joined.
     *
     * @param  int $eventId
     * @return array
     */
    public function findByEventId($eventId) {
        $sql = "SELECT 
                    et.*,
                    c.club_name AS target_club_name,
                    c.club_code AS target_club_code,
                    d.division_name AS target_division_name,
                    z.zonal_name AS target_zonal_name
                FROM EventTarget et
                LEFT JOIN Club c ON et.target_club_id = c.club_id
                LEFT JOIN Division d ON et.target_division_id = d.division_id
                LEFT JOIN Zone z ON et.target_zonal_id = z.zonal_id
                WHERE et.event_id = ?
                ORDER BY c.club_name ASC";

        return $this->resultSet($sql, [$eventId]);
    }

    /**
     * Legacy: Update target club for a given event (single target only).
     * Kept for backward compatibility — prefer saveTargets() going forward.
     *
     * @param  int      $eventId
     * @param  int      $targetClubId
     * @param  int|null $maxAttendance
     * @return bool
     */
    public function updateTargetClub($eventId, $targetClubId, $maxAttendance = null) {
        $existing = $this->single("SELECT target_id FROM EventTarget WHERE event_id = ? LIMIT 1", [$eventId]);

        if ($existing) {
            $this->query(
                "UPDATE EventTarget SET target_club_id = ?, max_attendance = ? WHERE event_id = ?",
                [$targetClubId, $maxAttendance, $eventId]
            );
        } else {
            $this->createTarget($eventId, $targetClubId, $maxAttendance);
        }
        return true;
    }
}
