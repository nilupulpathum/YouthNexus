<?php

class ClubModel extends Model {

    /**
     * PLACEHOLDER numbering — no real spec confirmed yet.
     *
     * Format: CLB-{DivisionAbbrev}-{Year}-{Sequence}
     * Example: CLB-COL-2026-014
     *
     * Replace this when an official NYSC club-code convention is provided.
     */
    public function generateClubCode($divisionId, $divisionName) {
        $cleanDivisionName = preg_replace(
            '/[^A-Za-z]/',
            '',
            $divisionName
        );

        $abbrev = strtoupper(
            substr($cleanDivisionName, 0, 3)
        ) ?: 'GEN';

        $year = date('Y');

        $count = $this->single(
            "SELECT COUNT(*) AS total
             FROM Club
             WHERE division_id = ?
               AND YEAR(registration_date) = ?",
            [
                (int)$divisionId,
                $year,
            ]
        );

        $sequence = str_pad(
            (int)($count->total ?? 0) + 1,
            3,
            '0',
            STR_PAD_LEFT
        );

        return "CLB-{$abbrev}-{$year}-{$sequence}";
    }

    /**
     * Retrieve the name of a division.
     *
     * @param int $divisionId
     * @return string
     */
    public function getDivisionName($divisionId) {
        $row = $this->single(
            "SELECT division_name
             FROM Division
             WHERE division_id = ?
             LIMIT 1",
            [(int)$divisionId]
        );

        return $row->division_name ?? 'General';
    }

    /**
     * Create the real Club row from an approved application.
     *
     * Before calling this method, the controller should check
     * findBySourceApplicationId() to avoid duplicate creation when
     * resuming an interrupted approval.
     *
     * @param object $application
     * @param string $clubCode
     * @return int Newly created club ID
     */
    public function createFromApplication($application, $clubCode) {
        $this->query(
            "INSERT INTO Club (
                club_name,
                description,
                division_id,
                registration_date,
                status,
                no_of_members,
                club_code,
                source_application_id
             ) VALUES (
                ?, ?, ?, NOW(), 'Active', ?, ?, ?
             )",
            [
                $application->club_name,
                $application->description,
                (int)$application->proposed_division_id,
                (int)$application->no_of_members,
                $clubCode,
                (int)$application->application_id,
            ]
        );

        return (int) Database::getInstance()
            ->getConnection()
            ->lastInsertId();
    }

    /**
     * Find a Club already created from an application.
     *
     * This makes approval resumable when an earlier request stopped
     * after creating the Club but before completing the approval.
     *
     * @param int $applicationId
     * @return object|false
     */
    public function findBySourceApplicationId($applicationId) {
        return $this->single(
            "SELECT *
             FROM Club
             WHERE source_application_id = ?
             LIMIT 1",
            [(int)$applicationId]
        );
    }

    /**
     * Retrieve a Club using its primary key.
     *
     * @param int $id
     * @return object|false
     */
    public function findById($id) {
        return $this->single(
            "SELECT *
             FROM Club
             WHERE club_id = ?
             LIMIT 1",
            [(int)$id]
        );
    }
}