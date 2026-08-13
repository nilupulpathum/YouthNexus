<?php

class ClubApplicationModel extends Model {

    /**
     * Applications awaiting the Coordinator's decision in their division,
     * newest first — powers the card grid.
     */
    public function findPendingByDivision($divisionId) {
        return $this->resultSet(
            "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS proposer_name, u.email AS proposer_email
             FROM ClubApplication a
             JOIN User u ON u.user_id = a.proposer_user_id
             WHERE a.proposed_division_id = ? AND a.status = 'Pending'
             ORDER BY a.submitted_at DESC",
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
            "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS proposer_name, u.email AS proposer_email, u.phone_number AS proposer_phone
             FROM ClubApplication a
             JOIN User u ON u.user_id = a.proposer_user_id
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
}
