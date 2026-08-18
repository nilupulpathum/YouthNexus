<?php

class EventTargetModel extends Model {

    /**
     * Insert an event target record.
     *
     * @param  int      $eventId
     * @param  int|null $targetClubId
     * @param  int|null $targetDivisionId
     * @param  int|null $targetZonalId
     * @return int      Inserted target_id
     */
    public function createTarget($eventId, $targetClubId = null, $targetDivisionId = null, $targetZonalId = null) {
        $sql = "INSERT INTO EventTarget (event_id, target_club_id, target_division_id, target_zonal_id)
                VALUES (?, ?, ?, ?)";

        $this->query($sql, [$eventId, $targetClubId, $targetDivisionId, $targetZonalId]);
        return (int)Database::getInstance()->getConnection()->lastInsertId();
    }

    /**
     * Find targets for a specific event.
     *
     * @param  int $eventId
     * @return array
     */
    public function findByEventId($eventId) {
        $sql = "SELECT 
                    et.*,
                    c.club_name AS target_club_name,
                    d.division_name AS target_division_name,
                    z.zonal_name AS target_zonal_name
                FROM EventTarget et
                LEFT JOIN Club c ON et.target_club_id = c.club_id
                LEFT JOIN Division d ON et.target_division_id = d.division_id
                LEFT JOIN Zone z ON et.target_zonal_id = z.zonal_id
                WHERE et.event_id = ?";

        return $this->resultSet($sql, [$eventId]);
    }

    /**
     * Update target club for a given event.
     *
     * @param  int $eventId
     * @param  int $targetClubId
     * @return bool
     */
    public function updateTargetClub($eventId, $targetClubId) {
        // Check if target row already exists
        $existing = $this->single("SELECT target_id FROM EventTarget WHERE event_id = ? LIMIT 1", [$eventId]);

        if ($existing) {
            $this->query(
                "UPDATE EventTarget SET target_club_id = ? WHERE event_id = ?",
                [$targetClubId, $eventId]
            );
        } else {
            $this->createTarget($eventId, $targetClubId);
        }
        return true;
    }
}
