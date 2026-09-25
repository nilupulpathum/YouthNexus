<?php

class AnnouncementReadModel extends Model {

    /**
     * Mark an announcement as read by a user.
     * Uses INSERT IGNORE to handle the UNIQUE (announcement_id, user_id) constraint gracefully.
     *
     * @param  int $announcementId
     * @param  int $userId
     * @return bool
     */
    public function markRead($announcementId, $userId) {
        $sql = "INSERT IGNORE INTO AnnouncementRead (announcement_id, user_id, read_at)
                VALUES (?, ?, NOW())";

        $this->query($sql, [(int)$announcementId, (int)$userId]);
        return true;
    }

    /**
     * Check if a user has read an announcement.
     *
     * @param  int $announcementId
     * @param  int $userId
     * @return bool
     */
    public function hasRead($announcementId, $userId) {
        $sql = "SELECT read_id FROM AnnouncementRead
                WHERE announcement_id = ? AND user_id = ?
                LIMIT 1";

        $row = $this->single($sql, [(int)$announcementId, (int)$userId]);
        return !empty($row);
    }
}
