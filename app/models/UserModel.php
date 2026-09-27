<?php

class UserModel extends Model {

    /**
     * Find a user record by email address.
     *
     * @param  string      $email
     * @return object|false  PDO row object or false if not found
     */
    public function findByEmail($email) {
        return $this->single(
            "SELECT * FROM User WHERE email = ? LIMIT 1",
            [$email]
        );
    }

    /**
     * Find a user record by user_id.
     *
     * @param  int         $userId
     * @return object|false
     */
    public function findByUserId($userId) {
        return $this->single(
            "SELECT * FROM User WHERE user_id = ? LIMIT 1",
            [$userId]
        );
    }

    public function findByUserIdWithHierarchy($userId) {
        return $this->single(
            "SELECT u.*,
                    COALESCE(u.division_id, c.division_id) AS effective_division_id,
                    COALESCE(u.zonal_id, d.zonal_id) AS effective_zonal_id
             FROM User u
             LEFT JOIN Club c ON u.club_id = c.club_id
             LEFT JOIN Division d ON d.division_id = COALESCE(u.division_id, c.division_id)
             WHERE u.user_id = ?
             LIMIT 1",
            [(int) $userId]
        );
    }

    /**
     * Insert a new user into the database adhering to the User table schema.
     *
     * @param  string $fullname
     * @param  string $email
     * @param  string $hashedPassword  Already hashed with password_hash()
     * @param  string $role            Default: 'UnassignedUser'
     * @return bool
     */
    public function createUser($fullname, $email, $hashedPassword, $role = 'UnassignedUser') {
        // Split fullname into first_name and last_name
        $parts = explode(' ', trim($fullname), 2);
        $firstName = $parts[0] ?? $fullname;
        $lastName  = $parts[1] ?? $firstName;

        // Generate a unique username derived from email or name
        $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0]));
        if (empty($baseUsername)) {
            $baseUsername = 'user';
        }
        $username = $baseUsername;
        $counter = 1;
        while ($this->single("SELECT user_id FROM User WHERE username = ? LIMIT 1", [$username])) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        $this->query(
            "INSERT INTO User (username, email, password_hash, first_name, last_name, role, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 'Active', NOW())",
            [$username, $email, $hashedPassword, $firstName, $lastName, $role]
        );
        return true;
    }

    /**
     * Update a user's password by email.
     *
     * @param  string $email
     * @param  string $hashedPassword
     * @return bool
     */
    public function updatePassword($email, $hashedPassword) {
        $this->query(
            "UPDATE User SET password_hash = ? WHERE email = ?",
            [$hashedPassword, $email]
        );
        return true;
    }

    /**
     * Update last_login_at timestamp for a user.
     *
     * @param  int $userId
     * @return bool
     */
    public function updateLastLogin($userId) {
        $this->query(
            "UPDATE User SET last_login_at = NOW() WHERE user_id = ?",
            [$userId]
        );
        return true;
    }

    /**
     * Verify login credentials. Returns user object on success (if active), false on failure.
     *
     * @param  string      $email
     * @param  string      $plainPassword
     * @return object|false
     */
    public function verifyLogin($email, $plainPassword) {
        $user = $this->findByEmail($email);
        if ($user && password_verify($plainPassword, $user->password_hash)) {
            if (isset($user->status) && $user->status !== 'Active') {
                return false; // Prevent login if user is suspended or disabled
            }
            if (($user->role ?? '') === 'ClubMember' && ($user->membership_status ?? 'Active') !== 'Active') {
                return false; // Prevent login before the membership is approved
            }
            return $user;
        }
        return false;
    }

    // ---------------------------------------------------------------
    // CLUB ROSTER (D1: scoped reads/writes for club dashboards)
    // ---------------------------------------------------------------

    /**
     * Active roster for one club. Pending and disabled accounts excluded.
     */
    public function getClubRoster($clubId) {
        return $this->resultSet(
            "SELECT user_id, username, email, first_name, last_name, phone_number,
                    address, NIC, role, membership_status, membership_date, created_at
             FROM User
             WHERE club_id = ? AND status = 'Active' AND COALESCE(membership_status, 'Active') = 'Active'
             ORDER BY FIELD(role, 'ClubPresident', 'ClubSecretary', 'ClubTreasurer', 'ClubMember', 'Member'), first_name, last_name",
            [(int) $clubId]
        );
    }

    /**
     * Membership queue: active accounts awaiting president approval.
     */
    public function getClubPending($clubId) {
        return $this->resultSet(
            "SELECT user_id, username, email, first_name, last_name, phone_number,
                    address, NIC, role, membership_date, created_at
             FROM User
             WHERE club_id = ? AND status = 'Active' AND membership_status = 'Inactive'
             ORDER BY created_at ASC",
            [(int) $clubId]
        );
    }

    public function countClubRoster($clubId) {
        $row = $this->single(
            "SELECT COUNT(*) AS total,
                    SUM(role IN ('ClubPresident', 'ClubSecretary', 'ClubTreasurer')) AS executives
             FROM User
             WHERE club_id = ? AND status = 'Active' AND COALESCE(membership_status, 'Active') = 'Active'",
            [(int) $clubId]
        );
        $pending = $this->single(
            "SELECT COUNT(*) AS pending FROM User
             WHERE club_id = ? AND status = 'Active' AND membership_status = 'Inactive'",
            [(int) $clubId]
        );
        return [
            'total'      => (int) ($row->total ?? 0),
            'executives' => (int) ($row->executives ?? 0),
            'pending'    => (int) ($pending->pending ?? 0),
        ];
    }

    public function getClubNics($clubId) {
        return array_map(
            fn($r) => $r->NIC,
            $this->resultSet(
                "SELECT NIC FROM User WHERE club_id = ? AND NIC IS NOT NULL",
                [(int) $clubId]
            )
        );
    }

    /**
     * Active divisional treasurer for void-request routing (or false).
     */
    public function findDivisionTreasurer($divisionId) {
        return $this->single(
            "SELECT user_id FROM User WHERE role = 'DivisionalTreasurer'
             AND division_id = ? AND status = 'Active' LIMIT 1",
            [(int) $divisionId]
        );
    }

    /**
     * Email/NIC uniqueness check. Pass $excludeUserId when editing an existing
     * registration so a record never collides with itself.
     */
    public function emailOrNicTaken($email, $nic, $excludeUserId = 0) {
        return (bool) $this->single(
            "SELECT user_id FROM User
             WHERE (email = ? OR (NIC IS NOT NULL AND NIC = ?)) AND user_id <> ?
             LIMIT 1",
            [$email, $nic, (int) $excludeUserId]
        );
    }

    /**
     * Rejected registrations for one club, newest rejection first, with the
     * president's rejection reason and date pulled from the audit trail.
     */
    public function getClubRejected($clubId) {
        return $this->resultSet(
            "SELECT u.user_id, u.username, u.email, u.first_name, u.last_name,
                    u.phone_number, u.address, u.NIC, u.role,
                    u.membership_date, u.created_at,
                    (SELECT l.details FROM AuditLog l
                      WHERE l.target_entity = 'User' AND l.target_id = u.user_id
                        AND l.action_type = 'REJECT_MEMBER'
                      ORDER BY l.log_id DESC LIMIT 1) AS rejection_reason,
                    (SELECT l.timestamp FROM AuditLog l
                      WHERE l.target_entity = 'User' AND l.target_id = u.user_id
                        AND l.action_type = 'REJECT_MEMBER'
                      ORDER BY l.log_id DESC LIMIT 1) AS rejected_at
             FROM User u
             WHERE u.club_id = ? AND u.status = 'Disabled' AND u.membership_status = 'Inactive'
             ORDER BY rejected_at DESC, u.user_id DESC",
            [(int) $clubId]
        );
    }

    public function countClubRejected($clubId) {
        $row = $this->single(
            "SELECT COUNT(*) AS rejected FROM User
             WHERE club_id = ? AND status = 'Disabled' AND membership_status = 'Inactive'",
            [(int) $clubId]
        );
        return (int) ($row->rejected ?? 0);
    }

    /**
     * Edit a rejected registration and send it back to the president queue.
     * Returns affected rows (0 = wrong club, wrong id, or not rejected).
     */
    public function updateRejectedRegistration($clubId, $userId, $name, $email, $phone, $address, $nic) {
        $parts = preg_split('/\s+/u', trim($name), 2);
        $firstName = $parts[0] ?? $name;
        $lastName = $parts[1] ?? $firstName;
        $stmt = $this->query(
            "UPDATE User
             SET first_name = ?, last_name = ?, email = ?, phone_number = ?,
                 address = ?, NIC = ?, status = 'Active', membership_status = 'Inactive'
             WHERE user_id = ? AND club_id = ? AND status = 'Disabled' AND membership_status = 'Inactive'",
            [$firstName, $lastName, $email, $phone, $address, $nic, (int) $userId, (int) $clubId]
        );
        return $stmt->rowCount();
    }

    /**
     * Child records that would block a hard delete of this user.
     *
     * @return array list of ['table' => ..., 'column' => ..., 'count' => ...]
     */
    public function findUserDependents($userId) {
        $keys = $this->resultSet(
            "SELECT TABLE_NAME, COLUMN_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE REFERENCED_TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = 'User'"
        );
        $blockers = [];
        foreach ($keys as $key) {
            $table = $key->TABLE_NAME;
            $column = $key->COLUMN_NAME;
            $count = (int) $this->single(
                "SELECT COUNT(*) AS total FROM `{$table}` WHERE `{$column}` = ?",
                [(int) $userId]
            )->total;
            if ($count > 0) {
                $blockers[] = ['table' => $table, 'column' => $column, 'count' => $count];
            }
        }
        return $blockers;
    }

    /**
     * Delete a rejected registration outright. Refuses when another record
     * still points at the user, so the secretary is told what is in the way
     * instead of silently losing data. The audit trail is left intact.
     *
     * @return array{deleted: bool, blockers: array}
     */
    public function deleteRejectedRegistration($clubId, $userId) {
        $target = $this->single(
            "SELECT user_id FROM User
             WHERE user_id = ? AND club_id = ? AND status = 'Disabled' AND membership_status = 'Inactive'
             LIMIT 1",
            [(int) $userId, (int) $clubId]
        );
        if (!$target) {
            return ['deleted' => false, 'blockers' => []];
        }

        $blockers = $this->findUserDependents($userId);
        if ($blockers) {
            return ['deleted' => false, 'blockers' => $blockers];
        }

        $this->query('DELETE FROM User WHERE user_id = ? AND club_id = ?', [(int) $userId, (int) $clubId]);
        return ['deleted' => true, 'blockers' => []];
    }

    /**
     * Approve a pending member. Returns affected rows (0 = wrong scope/id).
     */
    public function approveClubMember($clubId, $userId) {
        $stmt = $this->query(
            "UPDATE User SET membership_status = 'Active'
             WHERE user_id = ? AND club_id = ? AND status = 'Active' AND membership_status = 'Inactive'",
            [(int) $userId, (int) $clubId]
        );
        return $stmt->rowCount();
    }

    /**
     * Reject a pending member (kept as Disabled for audit trail).
     */
    public function rejectClubMember($clubId, $userId) {
        $stmt = $this->query(
            "UPDATE User SET status = 'Disabled'
             WHERE user_id = ? AND club_id = ? AND status = 'Active' AND membership_status = 'Inactive'",
            [(int) $userId, (int) $clubId]
        );
        return $stmt->rowCount();
    }

    /**
     * Assign an executive role to an active roster member.
     */
    public function assignClubRole($clubId, $userId, $role) {
        $allowed = ['ClubSecretary', 'ClubTreasurer', 'ClubMember'];
        if (!in_array($role, $allowed, true)) {
            return 0;
        }
        $stmt = $this->query(
            "UPDATE User SET role = ?
             WHERE user_id = ? AND club_id = ? AND status = 'Active'
               AND COALESCE(membership_status, 'Active') = 'Active' AND role <> 'ClubPresident'",
            [$role, (int) $userId, (int) $clubId]
        );
        return $stmt->rowCount();
    }

    /**
     * Register a member into the approval queue (secretary flow).
     * Returns the new user_id.
     */
    public function registerClubMember($clubId, $divisionId, $name, $email, $phone, $address, $nic) {
        $parts = preg_split('/\s+/u', trim($name), 2);
        $firstName = $parts[0] ?? $name;
        $lastName = $parts[1] ?? $firstName;
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0]));
        $base = $base !== '' ? $base : 'member';
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $username = $base;
            $suffix = 1;
            while ($this->single("SELECT user_id FROM User WHERE username = ? LIMIT 1", [$username])) {
                $username = $base . $suffix;
                $suffix++;
            }
            try {
                $this->query(
                    "INSERT INTO User (username, email, password_hash, first_name, last_name,
                                       phone_number, NIC, address, role, status,
                                       membership_status, membership_date, club_id, division_id)
                     VALUES (?, ?, '', ?, ?, ?, ?, ?, 'ClubMember', 'Active', 'Inactive', CURDATE(), ?, ?)",
                    [$username, $email, $firstName, $lastName, $phone, $nic, $address, (int) $clubId, (int) $divisionId]
                );
                return (int) $this->single("SELECT LAST_INSERT_ID() AS id")->id;
            } catch (PDOException $exception) {
                if (($exception->errorInfo[0] ?? $exception->getCode()) !== '23000') {
                    throw $exception;
                }
                if ($this->emailOrNicTaken($email, $nic)) {
                    return 0;
                }
            }
        }
        throw new RuntimeException('Unable to allocate a unique username for this member.');
    }

    // ---------------------------------------------------------------
    // PASSWORD RESET DB HELPERS
    // ---------------------------------------------------------------

    /**
     * Create a password reset record in the PasswordReset table.
     *
     * @param int    $userId
     * @param string $otpCode
     * @return bool
     */
    public function createPasswordReset($userId, $otpCode) {
        $this->query(
            "INSERT INTO PasswordReset (user_id, otp_code, expires_at, is_used, created_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), FALSE, NOW())",
            [$userId, $otpCode]
        );
        return true;
    }

    /**
     * Fetch a valid (unused and non-expired) password reset record.
     *
     * @param int    $userId
     * @param string $otpCode
     * @return object|false
     */
    public function getValidPasswordReset($userId, $otpCode) {
        return $this->single(
            "SELECT * FROM PasswordReset 
             WHERE user_id = ? AND otp_code = ? AND is_used = FALSE AND expires_at > NOW() 
             ORDER BY created_at DESC LIMIT 1",
            [$userId, $otpCode]
        );
    }

    /**
     * Mark a password reset record as used.
     *
     * @param int $resetId
     * @return bool
     */
    public function markPasswordResetUsed($resetId) {
        $this->query(
            "UPDATE PasswordReset SET is_used = TRUE WHERE reset_id = ?",
            [$resetId]
        );
        return true;
    }

    public function findAnnouncementRecipients($targetRole, $managerLevel, $scopeId = null) {
        $sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.role, u.club_id,
                       c.club_name,
                       COALESCE(u.division_id, c.division_id) AS effective_division_id,
                       d.division_name,
                       COALESCE(u.zonal_id, d.zonal_id) AS effective_zonal_id,
                       z.zonal_name
                FROM User u
                LEFT JOIN Club c ON c.club_id = u.club_id
                LEFT JOIN Division d ON d.division_id = COALESCE(u.division_id, c.division_id)
                LEFT JOIN Zone z ON z.zonal_id = COALESCE(u.zonal_id, d.zonal_id)
                WHERE u.status = 'Active' AND u.role = ?";
        $params = [$targetRole];

        switch ($managerLevel) {
            case 'Club':
                $sql .= " AND u.club_id = ?";
                $params[] = (int) $scopeId;
                break;
            case 'Divisional':
                $sql .= " AND COALESCE(u.division_id, c.division_id) = ?";
                $params[] = (int) $scopeId;
                break;
            case 'Zonal':
                $sql .= " AND COALESCE(u.zonal_id, d.zonal_id) = ?";
                $params[] = (int) $scopeId;
                break;
            case 'NYSC':
                break;
            default:
                throw new InvalidArgumentException('Invalid announcement manager level.');
        }

        $sql .= " ORDER BY c.club_name, d.division_name, u.first_name, u.last_name";
        return $this->resultSet($sql, $params);
    }
}
