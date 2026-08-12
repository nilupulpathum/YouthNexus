<?php

class ClubApplicationPhotoModel extends Model {

    /**
     * Create an application photo record.
     * @param int    $applicationId
     * @param string $photoPath
     * @return bool
     */
    public function createPhoto($applicationId, $photoPath) {
        $this->query(
            "INSERT INTO ClubApplicationPhoto (application_id, photo_path)
             VALUES (?, ?)",
            [$applicationId, $photoPath]
        );
        return true;
    }

    /**
     * Get all photos for a given application.
     * @param int $applicationId
     * @return array
     */
    public function getByApplication($applicationId) {
        return $this->resultSet(
            "SELECT * FROM ClubApplicationPhoto WHERE application_id = ? ORDER BY uploaded_at",
            [$applicationId]
        );
    }
}
