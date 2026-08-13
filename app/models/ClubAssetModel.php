<?php

class ClubAssetModel extends Model {

    public function findByApplication($applicationId) {
        return $this->resultSet(
            "SELECT * FROM ClubAsset WHERE application_id = ? ORDER BY asset_id",
            [$applicationId]
        );
    }
}
