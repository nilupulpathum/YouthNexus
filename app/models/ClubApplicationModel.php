<?php

class ClubApplicationModel extends Model {

    /**
     * Applications awaiting the Coordinator's decision in their division,
     * newest first — powers the card grid.
     */
    public function findPendingByDivision($divisionId) {
        return $this->resultSet(
            "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS proposer_name, u.email AS proposer_email, u.NIC AS proposer_nic, u.eligibility_checked AS proposer_eligible
             FROM ClubApplication a
             JOIN User u ON u.user_id = a.proposer_user_id
             WHERE a.proposed_division_id = ? AND a.status = 'Pending'
             ORDER BY a.submitted_at DESC",
            [$divisionId]
        );
    }

    /**
     * Applications already approved in the coordinator's division,
     * most recently reviewed first — powers the Approved card grid.
     */
    public function findApprovedByDivision($divisionId) {
        return $this->resultSet(
            "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS proposer_name, u.email AS proposer_email, u.NIC AS proposer_nic,
                    a.reviewed_at, CONCAT(reviewer.first_name, ' ', reviewer.last_name) AS reviewed_by_name
             FROM ClubApplication a
             JOIN User u ON u.user_id = a.proposer_user_id
             LEFT JOIN User reviewer ON reviewer.user_id = a.reviewed_by
             WHERE a.proposed_division_id = ? AND a.status = 'Approved'
             ORDER BY a.reviewed_at DESC, a.submitted_at DESC",
            [$divisionId]
        );
    }

    /** Pending / Approved / Rejected counts for the stats bar. */
    public function countsByDivision($divisionId) {
        $row = $this->single(
            "SELECT
                SUM(status = 'Pending')  AS Pending,
                SUM(status = 'Approved') AS Approved,
                SUM(status = 'Rejected') AS Rejected
             FROM ClubApplication WHERE proposed_division_id = ?",
            [$divisionId]
        );
        return [
            'Pending'  => (int)($row->Pending  ?? 0),
            'Approved' => (int)($row->Approved ?? 0),
            'Rejected' => (int)($row->Rejected ?? 0),
        ];
    }

    /** Single application with proposer details joined in, for the review modal. */
    public function findById($applicationId) {
        return $this->single(
            "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS proposer_name, u.email AS proposer_email, u.phone_number AS proposer_phone,
                    d.division_name, z.zonal_name AS zone_name,
                    CONCAT(reviewer.first_name, ' ', reviewer.last_name) AS reviewed_by_name
             FROM ClubApplication a
             JOIN User u ON u.user_id = a.proposer_user_id
             LEFT JOIN User reviewer ON reviewer.user_id = a.reviewed_by
             LEFT JOIN Division d ON d.division_id = a.proposed_division_id
             LEFT JOIN Zone z ON z.zonal_id = d.zonal_id
             WHERE a.application_id = ? LIMIT 1",
            [$applicationId]
        );
    }

    /**
     * Which required document columns are still empty on this application.
     * Docs are fixed columns on ClubApplication, not a separate table.
     *
     * @param  object  $application  row from findById()
     * @return array   list of missing labels, empty if complete
     */
    public function missingDocuments($application) {
        $required = [
            'constitution_path'  => 'Constitution',
            'venue_proof_path'   => 'Proof of Venue',
            'nic_president_path' => 'President NIC',
            'nic_secretary_path' => 'Secretary NIC',
            'nic_treasurer_path' => 'Treasurer NIC',
        ];
        $missing = [];
        foreach ($required as $column => $label) {
            if (empty($application->$column)) {
                $missing[] = $label;
            }
        }
        return $missing;
    }

    public function markApproved($applicationId, $reviewerId) {
        $this->query(
            "UPDATE ClubApplication SET status = 'Approved', reviewed_by = ?, reviewed_at = NOW() WHERE application_id = ?",
            [$reviewerId, $applicationId]
        );
    }

    public function markRejected($applicationId, $reviewerId, $remarks) {
        $this->query(
            "UPDATE ClubApplication SET status = 'Rejected', reviewed_by = ?, reviewed_at = NOW(), rejection_remarks = ? WHERE application_id = ?",
            [$reviewerId, $remarks, $applicationId]
        );
    }

    // -----------------------------------------------------------------
    // RECONCILIATION with feat-club_registration (the submission-wizard
    // branch), which independently created a model with this same name.
    // These methods cover the submission side and standard naming they
    // used, kept here so either branch's controller works against this
    // file once merged. Flagged to the team — needs a real merge review.
    // -----------------------------------------------------------------

    /** Create a new club application (submission wizard side). */
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
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW()
            )",
            [
                $data['proposer_user_id'], $data['club_name'], $data['description'], $data['club_logo_path'], $data['category'],
                $data['date_establishment'] ?: null, $data['no_of_members'],
                $data['proposed_division_id'] ?: null, $data['location_type'], $data['street_address'], $data['city'],
                $data['state_province'], $data['postal_code'], $data['country'],
                $data['bank_name'], $data['bank_branch'], $data['account_holder'], $data['account_number'], $data['bank_confirmed'] ? 1 : 0,
                $data['constitution_path'], $data['venue_proof_path'],
                $data['nic_president_path'], $data['nic_secretary_path'], $data['nic_treasurer_path'],
                $data['info_accuracy'] ? 1 : 0, $data['terms_accepted'] ? 1 : 0, $data['digital_signature'],
            ]
        );
        $row = $this->single("SELECT LAST_INSERT_ID() AS id");
        return $row ? (int)$row->id : false;
    }

    /** All applications a given proposer submitted (submission wizard side). */
    public function getByProposer($userId) {
        return $this->resultSet(
            "SELECT * FROM ClubApplication WHERE proposer_user_id = ? ORDER BY submitted_at DESC",
            [$userId]
        );
    }

    /** Alias of findPendingByDivision() — matches feat-club_registration's naming. */
    public function getPending($divisionId = null) {
        if ($divisionId) {
            return $this->findPendingByDivision($divisionId);
        }
        return $this->resultSet(
            "SELECT a.*, u.first_name, u.last_name, u.email
             FROM ClubApplication a JOIN User u ON a.proposer_user_id = u.user_id
             WHERE a.status = 'Pending' ORDER BY a.submitted_at ASC"
        );
    }

    /** Alias of markApproved() — matches feat-club_registration's naming. */
    public function approve($applicationId, $reviewerUserId) {
        $this->markApproved($applicationId, $reviewerUserId);
        return true;
    }

    /** Alias of markRejected() — matches feat-club_registration's naming. */
    public function reject($applicationId, $reviewerUserId, $remarks = '') {
        $this->markRejected($applicationId, $reviewerUserId, $remarks);
        return true;
    }
}
