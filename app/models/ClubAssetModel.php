<?php

class ClubAssetModel extends Model {

    public function findByApplication($applicationId) {
        return $this->resultSet(
            "SELECT * FROM ClubAsset WHERE application_id = ? ORDER BY asset_id",
            [$applicationId]
        );
    }

    // -----------------------------------------------------------------
    // RECONCILIATION with feat-club_registration — submission-side methods.
    // -----------------------------------------------------------------

    /** Create an asset record (submission wizard side). */
    public function createAsset($data) {
        $this->query(
            "INSERT INTO ClubAsset (application_id, asset_name, quantity, `condition`) VALUES (?, ?, ?, ?)",
            [$data['application_id'], $data['asset_name'], $data['quantity'], $data['condition']]
        );
        return true;
    }

    /** Alias of findByApplication() — matches feat-club_registration's naming. */
    public function getByApplication($applicationId) {
        return $this->findByApplication($applicationId);
    }

    public function deleteAsset($assetId) {
        $this->query("DELETE FROM ClubAsset WHERE asset_id = ?", [$assetId]);
        return true;
    }
}
