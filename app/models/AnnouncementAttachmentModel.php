<?php

class AnnouncementAttachmentModel extends Model {

    public function findById($id) {
        return $this->single('SELECT * FROM AnnouncementAttachment WHERE attachment_id = ?', [(int)$id]);
    }

    /** Keep deletion scoped to the announcement already locked by the controller. */
    public function deleteFromAnnouncement($attachmentId, $announcementId) {
        $this->query('DELETE FROM AnnouncementAttachment WHERE attachment_id = ? AND announcement_id = ?',
            [(int)$attachmentId, (int)$announcementId]);
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
