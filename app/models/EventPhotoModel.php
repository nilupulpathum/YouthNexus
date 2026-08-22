<?php

class EventPhotoModel extends Model {

    /**
     * Retrieve all photos for a given event.
     *
     * @param  int   $eventId
     * @return array
     */
    public function getByEventId($eventId) {
        $sql = "SELECT
                    p.photo_id,
                    p.photo_url,
                    p.uploaded_at,
                    CONCAT(u.first_name, ' ', u.last_name) AS uploaded_by_name
                FROM EventPhoto p
                JOIN User u ON p.uploaded_by = u.user_id
                WHERE p.event_id = ?
                ORDER BY p.uploaded_at DESC";
        return $this->resultSet($sql, [(int)$eventId]);
    }

    /**
     * Add a photo record.
     *
     * @param  int    $eventId
     * @param  string $photoUrl
     * @param  int    $uploadedBy
     * @return int    Inserted photo_id
     */
    public function add($eventId, $photoUrl, $uploadedBy) {
        $sql = "INSERT INTO EventPhoto (event_id, photo_url, uploaded_by)
                VALUES (?, ?, ?)";
        $this->query($sql, [(int)$eventId, $photoUrl, (int)$uploadedBy]);
        return (int)Database::getInstance()->getConnection()->lastInsertId();
    }
}
