<?php

class AnnouncementAttachmentModel extends Model {

    public function findById($id) {
        return $this->single('SELECT * FROM AnnouncementAttachment WHERE attachment_id = ?', [(int)$id]);
    }

    /** Keep deletion scoped to the announcement already locked by the controller. */
    public function archiveFromAnnouncement($attachmentId, $announcementId, $userId) {
        $attachment = $this->findById((int) $attachmentId);
        if (!$attachment || (int) $attachment->announcement_id !== (int) $announcementId) return false;
        $this->query(
            'INSERT INTO AnnouncementAttachmentHistory
                (announcement_id, original_attachment_id, file_name, file_path, file_size, removed_by)
             VALUES (?, ?, ?, ?, ?, ?)',
            [(int) $announcementId, (int) $attachmentId, $attachment->file_name, $attachment->file_path, (int) $attachment->file_size, (int) $userId]
        );
        $stmt = $this->query('DELETE FROM AnnouncementAttachment WHERE attachment_id = ? AND announcement_id = ?',
            [(int)$attachmentId, (int)$announcementId]);
        return $stmt->rowCount() === 1;
    }

    /**
     * Create an attachment record for an announcement.
     *
     * @param  int    $announcementId
     * @param  string $fileName
     * @param  string $filePath
     * @param  int    $fileSize
     * @return int    Inserted attachment_id
     */
    public function create($announcementId, $fileName, $filePath, $fileSize) {
        $sql = "INSERT INTO AnnouncementAttachment (announcement_id, file_name, file_path, file_size, uploaded_at)
                VALUES (?, ?, ?, ?, NOW())";

        $this->query($sql, [(int)$announcementId, $fileName, $filePath, (int)$fileSize]);
        return (int)Database::getInstance()->getConnection()->lastInsertId();
    }

    /**
     * Find all attachments for a specific announcement.
     *
     * @param  int $announcementId
     * @return array
     */
    public function findByAnnouncementId($announcementId) {
        $sql = "SELECT * FROM AnnouncementAttachment
                WHERE announcement_id = ?
                ORDER BY attachment_id ASC";

        return $this->resultSet($sql, [(int)$announcementId]);
    }
}
