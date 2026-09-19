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
            return $user;
        }
        return false;
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
}
