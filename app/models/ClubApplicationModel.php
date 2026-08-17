<?php

class ClubApplicationModel extends Model {

    /**
     * Create a new club application record.
     * @param array $data  Associative array of application fields
     * @return int|false   The inserted application_id, or false on failure
     */
    public function createApplication($data) {
        $this->query(
            "INSERT INTO ClubApplication (
                proposer_user_id, club_name, description, club_logo_path, category,
                date_establishment, no_of_members,
                proposed_division_id, location_type, street_address, city,
                state_province, postal_code, country,
                bank_name, bank_branch, account_holder, account_number, bank_confirmed,
                constitution_path, venue_proof_path,
                nic_president_path, nic_secretary_path, nic_treasurer_path,
                info_accuracy, terms_accepted, digital_signature,
                status, submitted_at
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                'Pending', NOW()
            )",
            [
                $data['proposer_user_id'],
                $data['club_name'],
                $data['description'],
                $data['club_logo_path'],
                $data['category'],
                $data['date_establishment'] ?: null,
                $data['no_of_members'],
                $data['proposed_division_id'] ?: null,
                $data['location_type'],
                $data['street_address'],
                $data['city'],
                $data['state_province'],
                $data['postal_code'],
                $data['country'],
                $data['bank_name'],
                $data['bank_branch'],
                $data['account_holder'],
                $data['account_number'],
                $data['bank_confirmed'] ? 1 : 0,
                $data['constitution_path'],
                $data['venue_proof_path'],
                $data['nic_president_path'],
                $data['nic_secretary_path'],
                $data['nic_treasurer_path'],
                $data['info_accuracy'] ? 1 : 0,
                $data['terms_accepted'] ? 1 : 0,
                $data['digital_signature'],
            ]
        );

        $row = $this->single("SELECT LAST_INSERT_ID() AS id");
        return $row ? (int)$row->id : false;
    }

    /**
     * Find a club application by its ID.
     * @param int $applicationId
     * @return object|false
     */
    public function findById($applicationId) {
        return $this->single(
            "SELECT * FROM ClubApplication WHERE application_id = ? LIMIT 1",
            [$applicationId]
        );
    }

    /**
     * Get all applications submitted by a specific user.
     * @param int $userId
     * @return array
     */
    public function getByProposer($userId) {
        return $this->resultSet(
            "SELECT * FROM ClubApplication WHERE proposer_user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
    }

    /**
     * Get all pending applications (for coordinator review queue).
     * @param int|null $divisionId  Optional: scope to a specific division
     * @return array
     */
    public function getPending($divisionId = null) {
        if ($divisionId) {
            return $this->resultSet(
                "SELECT a.*, u.first_name, u.last_name, u.email
                 FROM ClubApplication a
                 JOIN User u ON a.proposer_user_id = u.user_id
                 WHERE a.status = 'Pending' AND a.proposed_division_id = ?
                 ORDER BY a.submitted_at ASC",
                [$divisionId]
            );
        }
        return $this->resultSet(
            "SELECT a.*, u.first_name, u.last_name, u.email
             FROM ClubApplication a
             JOIN User u ON a.proposer_user_id = u.user_id
             WHERE a.status = 'Pending'
             ORDER BY a.submitted_at ASC"
        );
    }

    /**
     * Approve a club application.
     * @param int $applicationId
     * @param int $reviewerUserId  The coordinator who approves
     * @return bool
     */
    public function approve($applicationId, $reviewerUserId) {
        $this->query(
            "UPDATE ClubApplication
             SET status = 'Approved', reviewed_by = ?, reviewed_at = NOW()
             WHERE application_id = ?",
            [$reviewerUserId, $applicationId]
        );
        return true;
    }

    /**
     * Reject a club application.
     * @param int    $applicationId
     * @param int    $reviewerUserId
     * @param string $remarks
     * @return bool
     */
    public function reject($applicationId, $reviewerUserId, $remarks = '') {
        $this->query(
            "UPDATE ClubApplication
             SET status = 'Rejected', reviewed_by = ?, reviewed_at = NOW(), rejection_remarks = ?
             WHERE application_id = ?",
            [$reviewerUserId, $remarks, $applicationId]
        );
        return true;
    }
}
