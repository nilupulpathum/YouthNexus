<?php

/**
 * ManageUserModel
 * ============================================================
 * Data-access layer for the NYSC Manage User feature.
 *
 * Tables touched (read/write):
 *   User        — list, create, update, status flip
 *   Zone        — jurisdiction lookup (Zonal level)
 *   Division    — jurisdiction lookup (Divisional level)
 *   AuditLog    — written on every mutating action
 * ============================================================
 */
class ManageUserModel extends Model {

    // ----------------------------------------------------------
    // ROLES handled by this screen
    // ----------------------------------------------------------
    public static $NYSC_ROLES = [
        'ZonalCoordinator',
        'ZonalSecretary',
        'ZonalTreasurer',
        'DivisionalCoordinator',
        'DivisionalSecretary',
        'DivisionalTreasurer',
    ];

    // Map UI "Administrative Level" → role options
    public static $LEVEL_ROLES = [
        'Zonal Level'      => ['ZonalCoordinator', 'ZonalSecretary', 'ZonalTreasurer'],
        'Divisional Level' => ['DivisionalCoordinator', 'DivisionalSecretary', 'DivisionalTreasurer'],
    ];

    // Human-readable labels for roles
    public static $ROLE_LABELS = [
        'ZonalCoordinator'      => 'Zonal Coordinator',
        'ZonalSecretary'        => 'Zonal Secretary',
        'ZonalTreasurer'        => 'Zonal Treasurer',
        'DivisionalCoordinator' => 'Divisional Coordinator',
        'DivisionalSecretary'   => 'Divisional Secretary',
        'DivisionalTreasurer'   => 'Divisional Treasurer',
    ];

    // ----------------------------------------------------------
    // STAT CARDS
    // ----------------------------------------------------------

    /**
     * Returns four stat numbers used by the header cards.
     *
     * @return array{totalActive:int, zonalPersonnel:int, divisionalOfficers:int, deactivated:int}
     */
    public function countStats() {
        $zonalRoles      = implode(',', array_fill(0, count(self::$LEVEL_ROLES['Zonal Level']), '?'));
        $divisionalRoles = implode(',', array_fill(0, count(self::$LEVEL_ROLES['Divisional Level']), '?'));
        $allRoles        = implode(',', array_fill(0, count(self::$NYSC_ROLES), '?'));

        $totalActive = (int) $this->single(
            "SELECT COUNT(*) AS c FROM User WHERE role IN ({$allRoles}) AND status = 'Active'",
            self::$NYSC_ROLES
        )->c ?? 0;

        $zonal = (int) $this->single(
            "SELECT COUNT(*) AS c FROM User WHERE role IN ({$zonalRoles}) AND status = 'Active'",
            self::$LEVEL_ROLES['Zonal Level']
        )->c ?? 0;

        $divisional = (int) $this->single(
            "SELECT COUNT(*) AS c FROM User WHERE role IN ({$divisionalRoles}) AND status = 'Active'",
            self::$LEVEL_ROLES['Divisional Level']
        )->c ?? 0;

        $deactivated = (int) $this->single(
            "SELECT COUNT(*) AS c FROM User WHERE role IN ({$allRoles}) AND status IN ('Disabled','Suspended')",
            self::$NYSC_ROLES
        )->c ?? 0;

        return [
            'totalActive'        => $totalActive,
            'zonalPersonnel'     => $zonal,
            'divisionalOfficers' => $divisional,
            'deactivated'        => $deactivated,
        ];
    }

    // ----------------------------------------------------------
    // LIST / SEARCH / FILTER
    // ----------------------------------------------------------

    /**
     * Paginated, filterable user list.
     *
     * @param  array  $filters  Keys: search, level, position, status
     * @param  int    $page     1-indexed current page
     * @param  int    $perPage  Rows per page
     * @return array{rows:array, total:int}
     */
    public function getUsers(array $filters = [], int $page = 1, int $perPage = 15): array {
        $params = [];
        $where  = ["u.role IN ('" . implode("','", self::$NYSC_ROLES) . "')"];

        // ---- search ----
        $search = trim($filters['search'] ?? '');
        if ($search !== '') {
            $where[]  = "(CONCAT(u.first_name,' ',u.last_name) LIKE ? OR u.NIC LIKE ? OR u.email LIKE ?)";
            $like     = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        // ---- level filter ----
        $level = trim($filters['level'] ?? '');
        if ($level !== '' && isset(self::$LEVEL_ROLES[$level])) {
            $ph       = implode(',', array_fill(0, count(self::$LEVEL_ROLES[$level]), '?'));
            $where[]  = "u.role IN ({$ph})";
            $params   = array_merge($params, self::$LEVEL_ROLES[$level]);
        }

        // ---- position / role filter ----
        $position = trim($filters['position'] ?? '');
        if ($position !== '' && in_array($position, self::$NYSC_ROLES)) {
            $where[]  = "u.role = ?";
            $params[] = $position;
        }

        // ---- status filter ----
        $status = trim($filters['status'] ?? '');
        if ($status === 'Active') {
            $where[]  = "u.status = 'Active'";
        } elseif ($status === 'Inactive') {
            $where[]  = "u.status IN ('Disabled','Suspended')";
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);

        // total count
        $total = (int) $this->single(
            "SELECT COUNT(*) AS c FROM User u {$whereSQL}",
            $params
        )->c ?? 0;

        // paginated rows
        $offset = ($page - 1) * $perPage;
        $rows   = $this->resultSet(
            "SELECT
                u.user_id,
                u.first_name,
                u.last_name,
                CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                u.NIC,
                u.email,
                u.phone_number,
                u.role,
                u.status,
                u.created_at,
                u.zonal_id,
                u.division_id,
                z.zonal_name,
                d.division_name
             FROM User u
             LEFT JOIN Zone z     ON z.zonal_id     = u.zonal_id
             LEFT JOIN Division d ON d.division_id  = u.division_id
             {$whereSQL}
             ORDER BY u.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        return ['rows' => $rows, 'total' => $total];
    }

    // ----------------------------------------------------------
    // SINGLE USER
    // ----------------------------------------------------------

    public function getUserById(int $id) {
        return $this->single(
            "SELECT u.*,
                    z.zonal_name,
                    d.division_name
             FROM User u
             LEFT JOIN Zone z     ON z.zonal_id    = u.zonal_id
             LEFT JOIN Division d ON d.division_id = u.division_id
             WHERE u.user_id = ?",
            [$id]
        );
    }

    // ----------------------------------------------------------
    // CREATE
    // ----------------------------------------------------------

    /**
     * Insert a new NYSC-level user with a temporary password.
     *
     * @param  array $data  Keys: first_name, last_name, NIC, email, phone_number,
     *                           role, zonal_id|division_id, password_hash
     * @return int  New user_id
     */
    public function createNyscUser(array $data): int {
        // Generate a unique username
        $base    = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $data['email'])[0]));
        $base    = $base ?: 'user';
        $uname   = $base;
        $counter = 1;
        while ($this->single("SELECT user_id FROM User WHERE username = ? LIMIT 1", [$uname])) {
            $uname = $base . $counter++;
        }

        $zonalId    = !empty($data['zonal_id'])    ? (int)$data['zonal_id']    : null;
        $divisionId = !empty($data['division_id']) ? (int)$data['division_id'] : null;

        $this->query(
            "INSERT INTO User
                (username, email, password_hash, first_name, last_name, phone_number, NIC,
                 role, status, must_change_password, zonal_id, division_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active', 1, ?, ?, NOW())",
            [
                $uname,
                $data['email'],
                $data['password_hash'],
                $data['first_name'],
                $data['last_name'],
                $data['phone_number'] ?? null,
                $data['NIC'] ?? null,
                $data['role'],
                $zonalId,
                $divisionId,
            ]
        );

        return (int) $this->single("SELECT LAST_INSERT_ID() AS id")->id;
    }

    // ----------------------------------------------------------
    // UPDATE
    // ----------------------------------------------------------

    /**
     * Update editable fields for an existing user.
     *
     * @param  int   $id
     * @param  array $data  Keys: first_name, last_name, NIC, email, phone_number,
     *                           role, zonal_id|division_id
     * @return bool
     */
    public function updateUser(int $id, array $data): bool {
        $zonalId    = !empty($data['zonal_id'])    ? (int)$data['zonal_id']    : null;
        $divisionId = !empty($data['division_id']) ? (int)$data['division_id'] : null;

        $this->query(
            "UPDATE User SET
                first_name   = ?,
                last_name    = ?,
                NIC          = ?,
                email        = ?,
                phone_number = ?,
                role         = ?,
                zonal_id     = ?,
                division_id  = ?
             WHERE user_id = ?",
            [
                $data['first_name'],
                $data['last_name'],
                $data['NIC'] ?? null,
                $data['email'],
                $data['phone_number'] ?? null,
                $data['role'],
                $zonalId,
                $divisionId,
                $id,
            ]
        );
        return true;
    }

    // ----------------------------------------------------------
    // STATUS TOGGLE (soft-delete / reactivate)
    // ----------------------------------------------------------

    /**
     * Flip a user's status between Active and Disabled.
     *
     * @param  int    $id
     * @param  string $newStatus  'Active' | 'Disabled'
     * @return bool
     */
    public function setStatus(int $id, string $newStatus): bool {
        $allowed = ['Active', 'Disabled'];
        if (!in_array($newStatus, $allowed)) {
            return false;
        }
        $this->query(
            "UPDATE User SET status = ? WHERE user_id = ?",
            [$newStatus, $id]
        );
        return true;
    }

    // ----------------------------------------------------------
    // PERMANENT DELETE (hard delete)
    // ----------------------------------------------------------

    /**
     * Check whether at least one OTHER active NYSCAdministrator exists.
     * Used to prevent deleting/deactivating the last administrator.
     */
    public function otherActiveAdminExists(int $excludeId): bool {
        return (bool) $this->single(
            "SELECT user_id FROM User
             WHERE role = 'NYSCAdministrator' AND status = 'Active' AND user_id != ?
             LIMIT 1",
            [$excludeId]
        );
    }

    /**
     * Collect the reasons a user cannot be permanently deleted.
     *
     * A user is only deletable when they carry no financial, audit, or
     * operational footprint — deactivated accounts always keep their ledger
     * audit history, so anyone who has performed real work must stay
     * (soft-deleted) for traceability.
     *
     * @return string[]  Human-readable blocker descriptions (empty = deletable)
     */
    public function getDeletionBlockers(int $id): array {
        $blockers = [];

        $checks = [
            'ledger entries recorded by this account' =>
                "SELECT entry_id FROM LedgerEntry WHERE created_by = ? LIMIT 1",
            'fund transfers authorized by this account' =>
                "SELECT allocation_id FROM FundAllocation WHERE authorized_by = ? LIMIT 1",
            'financial audits initiated by this account' =>
                "SELECT audit_id FROM Audit WHERE initiated_by = ? LIMIT 1",
            'financial audits signed off by this account' =>
                "SELECT audit_id FROM Audit WHERE signed_off_by = ? LIMIT 1",
            'audit trail entries performed by this account' =>
                "SELECT log_id FROM AuditLog WHERE actor_user_id = ? LIMIT 1",
            'events created by this account' =>
                "SELECT event_id FROM Event WHERE created_by = ? LIMIT 1",
            'event approvals recorded by this account' =>
                "SELECT event_id FROM Event WHERE approved_by = ? LIMIT 1",
            'attendance records for this account' =>
                "SELECT attendance_id FROM Attendance WHERE user_id = ? LIMIT 1",
            'attendance records taken by this account' =>
                "SELECT attendance_id FROM Attendance WHERE recorded_by = ? LIMIT 1",
            'club applications proposed by this account' =>
                "SELECT application_id FROM ClubApplication WHERE proposer_user_id = ? LIMIT 1",
            'club application reviews by this account' =>
                "SELECT application_id FROM ClubApplication WHERE reviewed_by = ? LIMIT 1",
            'club health flags raised by this account' =>
                "SELECT health_flag_id FROM ClubHealthFlag WHERE raised_by = ? LIMIT 1",
            'club health flags resolved by this account' =>
                "SELECT health_flag_id FROM ClubHealthFlag WHERE resolved_by = ? LIMIT 1",
            'budget allocations set by this account' =>
                "SELECT budget_id FROM FundBudget WHERE set_by = ? LIMIT 1",
            'reports generated by this account' =>
                "SELECT report_id FROM Report WHERE generated_by = ? LIMIT 1",
            'volunteer history linked to this account' =>
                "SELECT log_id FROM VolunteerHistory WHERE member_id = ? LIMIT 1",
        ];

        foreach ($checks as $label => $sql) {
            if ($this->single($sql, [$id])) {
                $blockers[] = $label;
            }
        }

        return $blockers;
    }

    /**
     * Permanently remove a user account. The caller must have run
     * getDeletionBlockers() first — this performs only the physical delete.
     *
     * Password reset tokens are cleared first (security artifacts, no audit
     * value). Notifications and asset transfers cascade via FK.
     */
    public function deleteUserPermanently(int $id): bool {
        $this->query("DELETE FROM PasswordReset WHERE user_id = ?", [$id]);
        $this->query("DELETE FROM User WHERE user_id = ?", [$id]);
        return true;
    }

    // ----------------------------------------------------------
    // JURISDICTION DROPDOWNS
    // ----------------------------------------------------------

    public function getZones(): array {
        return $this->resultSet("SELECT zonal_id, zonal_name FROM Zone ORDER BY zonal_name");
    }

    /**
     * Get all divisions, optionally scoped to a zone.
     */
    public function getDivisions(?int $zonalId = null): array {
        if ($zonalId) {
            return $this->resultSet(
                "SELECT division_id, division_name, zonal_id FROM Division WHERE zonal_id = ? ORDER BY division_name",
                [$zonalId]
            );
        }
        return $this->resultSet(
            "SELECT division_id, division_name, zonal_id FROM Division ORDER BY division_name"
        );
    }

    // ----------------------------------------------------------
    // UNIQUENESS CHECKS
    // ----------------------------------------------------------

    public function nicExists(string $nic, ?int $excludeId = null): bool {
        if ($excludeId) {
            $row = $this->single("SELECT user_id FROM User WHERE NIC = ? AND user_id != ? LIMIT 1", [$nic, $excludeId]);
        } else {
            $row = $this->single("SELECT user_id FROM User WHERE NIC = ? LIMIT 1", [$nic]);
        }
        return (bool) $row;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool {
        if ($excludeId) {
            $row = $this->single("SELECT user_id FROM User WHERE email = ? AND user_id != ? LIMIT 1", [$email, $excludeId]);
        } else {
            $row = $this->single("SELECT user_id FROM User WHERE email = ? LIMIT 1", [$email]);
        }
        return (bool) $row;
    }

    // ----------------------------------------------------------
    // ROLES HELPER
    // ----------------------------------------------------------

    /** Resolve level string → role array */
    public static function rolesForLevel(string $level): array {
        return self::$LEVEL_ROLES[$level] ?? [];
    }

    /** Determine UI level string from a role */
    public static function levelForRole(string $role): string {
        if (in_array($role, self::$LEVEL_ROLES['Zonal Level'])) {
            return 'Zonal Level';
        }
        if (in_array($role, self::$LEVEL_ROLES['Divisional Level'])) {
            return 'Divisional Level';
        }
        return '';
    }
}
