<?php

class ClubAssetModel extends Model {

    /**
     * Create a club asset record linked to an application.
     * @param array $data  Contains application_id, asset_name, quantity, condition
     * @return bool
     */
    public function createAsset($data) {
        $this->query(
            "INSERT INTO ClubAsset (application_id, asset_name, quantity, `condition`)
             VALUES (?, ?, ?, ?)",
            [
                $data['application_id'],
                $data['asset_name'],
                $data['quantity'],
                $data['condition'],
            ]
        );
        return true;
    }

    /**
     * Get all assets for a given application.
     * @param int $applicationId
     * @return array
     */
    public function getByApplication($applicationId) {
        return $this->resultSet(
            "SELECT * FROM ClubAsset WHERE application_id = ? ORDER BY asset_id",
            [$applicationId]
        );
    }

    /**
     * Delete an asset by ID.
     * @param int $assetId
     * @return bool
     */
    public function deleteAsset($assetId) {
        $this->query(
            "DELETE FROM ClubAsset WHERE asset_id = ?",
            [$assetId]
        );
        return true;
    }
}
