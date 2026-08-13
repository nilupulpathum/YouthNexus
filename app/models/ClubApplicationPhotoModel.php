<?php

class ClubApplicationPhotoModel extends Model {

    public function findByApplication($applicationId) {
        return $this->resultSet(
            "SELECT * FROM ClubApplicationPhoto WHERE application_id = ? ORDER BY uploaded_at",
            [$applicationId]
        );
    }
}
