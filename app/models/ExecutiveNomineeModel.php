<?php

class ExecutiveNomineeModel extends Model {

    public function findByApplication($applicationId) {
        return $this->resultSet(
            "SELECT * FROM ExecutiveNominee WHERE application_id = ? ORDER BY
             FIELD(role_type, 'President', 'Secretary', 'Treasurer')",
            [$applicationId]
        );
    }

    /**
     * PLACEHOLDER numbering — no real spec confirmed yet. Format:
     * NYSC-{Year}-{6-digit sequence}. Replace once a real convention exists.
     */
    public function generateIndexNumber() {
        $year  = date('Y');
        $count = $this->single("SELECT COUNT(*) AS total FROM ExecutiveNominee WHERE index_number IS NOT NULL");
        $seq   = str_pad((int)($count->total ?? 0) + 1, 6, '0', STR_PAD_LEFT);
        return "NYSC-{$year}-{$seq}";
    }

    public function setIndexNumber($nomineeId, $indexNumber) {
        $this->query("UPDATE ExecutiveNominee SET index_number = ? WHERE nominee_id = ?", [$indexNumber, $nomineeId]);
    }

    /** Direct lookup on User by NIC — kept here rather than in UserModel.php. */
    public function findUserByNIC($nic) {
        if (empty($nic)) return null;
        return $this->single("SELECT * FROM User WHERE NIC = ? LIMIT 1", [$nic]);
    }

    private function generateUsername($firstName, $lastName) {
        $base     = strtolower(preg_replace('/[^a-z]/i', '', $firstName . $lastName));
        $username = $base;
        $suffix   = 1;
        while ($this->single("SELECT user_id FROM User WHERE username = ? LIMIT 1", [$username])) {
            $username = $base . $suffix;
            $suffix++;
        }
        return $username;
    }

    /**
     * Promote a nominee to a real User account on approval.
     *
     * - If the nominee's NIC already belongs to an existing User: link that
     *   account (update role/club_id/division_id), do NOT create a new one,
     *   do NOT send a temp-password credentials email.
     * - Otherwise: insert a brand-new User row with a generated username +
     *   temp password, written directly (bypasses UserModel::createUser(),
     *   since that method doesn't accept NIC/phone_number).
     *
     * @return array ['linked' => bool, 'user_id' => int, 'username' => string, 'password' => string|null]
     */
    public function promoteToUser($nominee, $clubId, $divisionId, $role) {
        $existing = $this->findUserByNIC($nominee->NIC);

        if ($existing) {
            $this->query(
                "UPDATE User SET role = ?, club_id = ?, division_id = ? WHERE user_id = ?",
                [$role, $clubId, $divisionId, $existing->user_id]
            );
            return [
                'linked'   => true,
                'user_id'  => $existing->user_id,
                'username' => $existing->username,
                'password' => null,
            ];
        }

        $nameParts = preg_split('/\s+/', trim($nominee->name), 2);
        $firstName = $nameParts[0] ?? $nominee->name;
        $lastName  = $nameParts[1] ?? '';

        $username     = $this->generateUsername($firstName, $lastName);
        $tempPassword = bin2hex(random_bytes(6));
        $hash         = password_hash($tempPassword, PASSWORD_DEFAULT);

        $this->query(
            "INSERT INTO User (username, email, password_hash, first_name, last_name, phone_number, NIC, role, status, club_id, division_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?)",
            [$username, $nominee->email, $hash, $firstName, $lastName, $nominee->phone_number, $nominee->NIC, $role, $clubId, $divisionId]
        );
        $newUserId = (int)Database::getInstance()->getConnection()->lastInsertId();

        return [
            'linked'   => false,
            'user_id'  => $newUserId,
            'username' => $username,
            'password' => $tempPassword,
        ];
    }

    // -----------------------------------------------------------------
    // RECONCILIATION with feat-club_registration — submission-side methods
    // and their naming, kept alongside the approval-side methods above.
    // -----------------------------------------------------------------

    /** Create a nominee record (submission wizard side). */
    public function createNominee($data) {
        $this->query(
            "INSERT INTO ExecutiveNominee (application_id, role_type, name, email, NIC, phone_number, date_of_birth, photo_path)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['application_id'], $data['role_type'], $data['name'], $data['email'],
                $data['NIC'], $data['phone_number'], $data['date_of_birth'] ?: null, $data['photo_path'],
            ]
        );
        return true;
    }

    /** Alias of findByApplication() — matches feat-club_registration's naming. */
    public function getByApplication($applicationId) {
        return $this->findByApplication($applicationId);
    }

    public function getByRole($applicationId, $roleType) {
        return $this->single(
            "SELECT * FROM ExecutiveNominee WHERE application_id = ? AND role_type = ? LIMIT 1",
            [$applicationId, $roleType]
        );
    }

    /** Alias of setIndexNumber() — matches feat-club_registration's naming. */
    public function updateIndexNumber($nomineeId, $indexNumber) {
        $this->setIndexNumber($nomineeId, $indexNumber);
        return true;
    }

    public function deleteByApplication($applicationId) {
        $this->query("DELETE FROM ExecutiveNominee WHERE application_id = ?", [$applicationId]);
        return true;
    }
}
