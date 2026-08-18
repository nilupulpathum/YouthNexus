<?php

class ClubApplicationPhotoModel extends Model {

    public function findByApplication($applicationId) {
        return $this->resultSet(
            "SELECT * FROM ClubApplicationPhoto WHERE application_id = ? ORDER BY uploaded_at",
            [$applicationId]
        );
    }

    // -----------------------------------------------------------------
    // RECONCILIATION with feat-club_registration — submission-side methods.
    // -----------------------------------------------------------------

    /** Create a photo record (submission wizard side). */
    public function createPhoto($applicationId, $photoPath) {
        $this->query(
            "INSERT INTO ClubApplicationPhoto (application_id, photo_path) VALUES (?, ?)",
            [$applicationId, $photoPath]
        );
        return true;
    }

    /** Alias of findByApplication() — matches feat-club_registration's naming. */
    public function getByApplication($applicationId) {
        return $this->findByApplication($applicationId);
    }
}
