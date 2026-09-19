<?php

class DivisionModel extends Model {

    /**
     * Get all divisions (for dropdowns).
     * @return array
     */
    public function getAll() {
        return $this->resultSet(
            "SELECT d.*, z.zonal_name
             FROM Division d
             JOIN Zone z ON d.zonal_id = z.zonal_id
             ORDER BY z.zonal_name, d.division_name"
        );
    }

    /**
     * Find a division by ID.
     * @param int $divisionId
     * @return object|false
     */
    public function findById($divisionId) {
        return $this->single(
            "SELECT * FROM Division WHERE division_id = ? LIMIT 1",
            [$divisionId]
        );
    }

    /**
     * Get divisions by zone.
     * @param int $zonalId
     * @return array
     */
    public function getByZone($zonalId) {
        return $this->resultSet(
            "SELECT * FROM Division WHERE zonal_id = ? ORDER BY division_name",
            [$zonalId]
        );
    }
}
