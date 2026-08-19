<?php

class ClubModel extends Model {

    /**
     * PLACEHOLDER numbering — no real spec confirmed yet. Format:
     * CLB-{DivisionAbbrev}-{Year}-{Sequence}, e.g. CLB-COL-2026-014.
     * Replace this the moment a real NYSC club-code convention is provided.
     */
    public function generateClubCode($divisionId, $divisionName) {
        $abbrev = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $divisionName), 0, 3)) ?: 'GEN';
        $year   = date('Y');

        $count = $this->single(
            "SELECT COUNT(*) AS total FROM Club WHERE division_id = ? AND YEAR(registration_date) = ?",
            [$divisionId, $year]
        );
        $sequence = str_pad((int)($count->total ?? 0) + 1, 3, '0', STR_PAD_LEFT);

        return "CLB-{$abbrev}-{$year}-{$sequence}";
    }

    public function getDivisionName($divisionId) {
        $row = $this->single("SELECT division_name FROM Division WHERE division_id = ? LIMIT 1", [$divisionId]);
        return $row->division_name ?? 'General';
    }

    /**
     * Create the real Club row from an approved application. Only called
     * once, at the moment of approval.
     *
     * @return int  the new club_id
     */
    public function createFromApplication($application, $clubCode) {
    $this->query(
        "INSERT INTO Club (club_name, description, division_id, registration_date, status, no_of_members, club_code, source_application_id)
         VALUES (?, ?, ?, NOW(), 'Active', ?, ?, ?)",
        [$application->club_name, $application->description, $application->proposed_division_id, $application->no_of_members, $clubCode, $application->application_id]
    );
    return (int)Database::getInstance()->getConnection()->lastInsertId();
    }

    /**
     * Retrieve a club record by its ID.
     *
     * @param int $id The Club ID
     * @return object|false
     */
    public function findById($id) {
        return $this->single("SELECT * FROM Club WHERE club_id = ? LIMIT 1", [(int)$id]);
    }
}