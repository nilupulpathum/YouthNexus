<?php

class ExecutiveNomineeModel extends Model {

    /**
     * Create an executive nominee record.
     * @param array $data  Associative array of nominee fields
     * @return bool
     */
    public function createNominee($data) {
        $this->query(
            "INSERT INTO ExecutiveNominee (
                application_id, role_type, name, email, NIC, phone_number,
                date_of_birth, photo_path
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['application_id'],
                $data['role_type'],
                $data['name'],
                $data['email'],
                $data['NIC'],
                $data['phone_number'],
                $data['date_of_birth'] ?: null,
                $data['photo_path'],
            ]
        );
        return true;
    }

    /**
     * Get all nominees for a given application.
     * @param int $applicationId
     * @return array
     */
    public function getByApplication($applicationId) {
        return $this->resultSet(
            "SELECT * FROM ExecutiveNominee WHERE application_id = ? ORDER BY role_type",
            [$applicationId]
        );
    }

    /**
     * Get a specific nominee by role type within an application.
     * @param int    $applicationId
     * @param string $roleType  'President', 'Secretary', or 'Treasurer'
     * @return object|false
     */
    public function getByRole($applicationId, $roleType) {
        return $this->single(
            "SELECT * FROM ExecutiveNominee
             WHERE application_id = ? AND role_type = ?
             LIMIT 1",
            [$applicationId, $roleType]
        );
    }

    /**
     * Update a nominee's index_number (system-generated after approval).
     * @param int    $nomineeId
     * @param string $indexNumber
     * @return bool
     */
    public function updateIndexNumber($nomineeId, $indexNumber) {
        $this->query(
            "UPDATE ExecutiveNominee SET index_number = ? WHERE nominee_id = ?",
            [$indexNumber, $nomineeId]
        );
        return true;
    }

    /**
     * Delete all nominees for an application (cleanup on cancellation).
     * @param int $applicationId
     * @return bool
     */
    public function deleteByApplication($applicationId) {
        $this->query(
            "DELETE FROM ExecutiveNominee WHERE application_id = ?",
            [$applicationId]
        );
        return true;
    }
}
