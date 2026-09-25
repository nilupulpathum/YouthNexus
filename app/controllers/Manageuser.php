<?php

/**
 * Manageuser Controller
 * ============================================================
 * Handles the NYSC Administration → Manage User feature.
 *
 * Routes (mapped by App.php URL dispatcher):
 *   GET  manageuser            → index()        — main listing page
 *   POST manageuser/create     → create()       — AJAX create user
 *   POST manageuser/update/{id}→ update($id)    — AJAX update user
 *   POST manageuser/setstatus/{id} → setstatus($id) — activate / deactivate
 *   GET  manageuser/getuser/{id}   → getuser($id)   — JSON for edit pre-fill
 *   GET  manageuser/getdivisions   → getdivisions() — JSON divisions by zone
 * ============================================================
 */
class Manageuser extends Controller {

    private const PER_PAGE    = 15;
    private const ALLOWED_ROLE = 'NYSCAdministrator';

    // ----------------------------------------------------------------
    // AUTH GUARD
    // ----------------------------------------------------------------

    private function requireAuth(): void {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        if (($_SESSION['user_role'] ?? '') !== self::ALLOWED_ROLE) {
            $this->redirect('home');
        }
    }

    // ----------------------------------------------------------------
    // CSRF helpers
    // ----------------------------------------------------------------

    private function ensureCsrf(): void {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    private function verifyCsrf(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // ----------------------------------------------------------------
    // JSON response helper
    // ----------------------------------------------------------------

    private function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    private function isAjax(): bool {
        return (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
            || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    }

    // ----------------------------------------------------------------
    // INPUT HELPERS
    // ----------------------------------------------------------------

    private function post(string $key, string $default = ''): string {
        return trim((string) ($_POST[$key] ?? $default));
    }

    private function get(string $key, string $default = ''): string {
        return trim((string) ($_GET[$key] ?? $default));
    }

    // ----------------------------------------------------------------
    // VALIDATE a user form payload (shared by create + update)
    //
    // Returns [] on success, ['field' => 'message', ...] on failure.
    // ----------------------------------------------------------------

    private function validateUserPayload(
        array $data,
        ManageUserModel $model,
        ?int $excludeId = null
    ): array {
        $errors = [];

        if (empty($data['first_name'])) {
            $errors['first_name'] = 'First name is required.';
        }
        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Last name is required.';
        }

        // NIC: optional but unique if provided
        if (!empty($data['NIC'])) {
            if (!preg_match('/^(\d{12}|\d{9}[VvXx])$/', $data['NIC'])) {
                $errors['NIC'] = 'NIC must be 12 digits or 9 digits followed by V/X.';
            } elseif ($model->nicExists($data['NIC'], $excludeId)) {
                $errors['NIC'] = 'This NIC is already registered.';
            }
        }

        // Email
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif ($model->emailExists($data['email'], $excludeId)) {
            $errors['email'] = 'This email is already registered.';
        }

        // Role
        if (empty($data['role']) || !in_array($data['role'], ManageUserModel::$NYSC_ROLES)) {
            $errors['role'] = 'Select a valid position.';
        }

        // Jurisdiction
        $level = ManageUserModel::levelForRole($data['role'] ?? '');
        if ($level === 'Zonal Level' && empty($data['zonal_id'])) {
            $errors['zonal_id'] = 'Select a zone for this role.';
        }
        if ($level === 'Divisional Level' && empty($data['division_id'])) {
            $errors['division_id'] = 'Select a division for this role.';
        }

        return $errors;
    }

    // ================================================================
    // INDEX — main listing page
    // ================================================================

    public function index(): void {
        $this->requireAuth();
        $this->ensureCsrf();

        $model = $this->model('ManageUserModel');

        $filters = [
            'search'   => $this->get('search'),
            'level'    => $this->get('level'),
            'position' => $this->get('position'),
            'status'   => $this->get('status'),
        ];

        $page   = max(1, (int) $this->get('page', '1'));
        $result = $model->getUsers($filters, $page, self::PER_PAGE);
        $stats  = $model->countStats();
        $zones  = $model->getZones();

        // Total pages
        $totalPages = (int) ceil($result['total'] / self::PER_PAGE);

        $this->view('manageuser/list', [
            'title'          => 'User Management — YouthNexus',
            'currentRoute'   => 'manageuser',
            'userRole'       => $_SESSION['user_role'] ?? '',
            'userName'       => ($_SESSION['user_first_name'] ?? '') . ' ' . ($_SESSION['user_last_name'] ?? ''),
            'pageTitle'      => 'User Management',
            'pageDescription'=> 'Create, edit and manage NYSC administrative personnel across all zones and divisions.',

            'users'          => $result['rows'],
            'total'          => $result['total'],
            'page'           => $page,
            'perPage'        => self::PER_PAGE,
            'totalPages'     => $totalPages,

            'stats'          => $stats,
            'zones'          => $zones,
            'filters'        => $filters,

            'roleLabels'     => ManageUserModel::$ROLE_LABELS,
            'levelRoles'     => ManageUserModel::$LEVEL_ROLES,
            'allRoles'       => ManageUserModel::$NYSC_ROLES,

            'csrfToken'      => $_SESSION['csrf_token'],
        ]);
    }

    // ================================================================
    // CREATE — AJAX POST
    // ================================================================

    public function create(): void {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
        }

        // CSRF
        if (!$this->verifyCsrf($this->post('csrf_token'))) {
            $this->json(['success' => false, 'message' => 'Invalid security token. Refresh and try again.'], 403);
        }

        $data = [
            'first_name'  => $this->post('first_name'),
            'last_name'   => $this->post('last_name'),
            'NIC'         => $this->post('nic'),
            'email'       => $this->post('email'),
            'phone_number'=> $this->post('phone'),
            'role'        => $this->post('position'),
            'zonal_id'    => $this->post('zonal_id')    ?: null,
            'division_id' => $this->post('division_id') ?: null,
        ];

        $model  = $this->model('ManageUserModel');
        $errors = $this->validateUserPayload($data, $model);

        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors], 422);
        }

        // Generate secure temp password
        $tempPassword = substr(bin2hex(random_bytes(8)), 0, 12);
        $data['password_hash'] = password_hash($tempPassword, PASSWORD_BCRYPT);

        try {
            $newUserId = $model->createNyscUser($data);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        }

        // Audit log
        $auditModel = $this->model('AuditLogModel');
        $auditModel->log(
            $_SESSION['user_id'],
            'USER_CREATED',
            'User',
            $newUserId,
            "NYSC Admin created user: {$data['first_name']} {$data['last_name']} ({$data['email']}), Role: {$data['role']}"
        );

        $this->json([
            'success'      => true,
            'message'      => "User {$data['first_name']} {$data['last_name']} created successfully.",
            'tempPassword' => $tempPassword,
            'userId'       => $newUserId,
        ]);
    }

    // ================================================================
    // GET USER — JSON for edit modal pre-fill
    // ================================================================

    public function getuser(string $id = ''): void {
        $this->requireAuth();
        $userId = (int) $id;
        if (!$userId) {
            $this->json(['success' => false, 'message' => 'Invalid user ID.'], 400);
        }

        $model = $this->model('ManageUserModel');
        $user  = $model->getUserById($userId);

        if (!$user) {
            $this->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $this->json([
            'success' => true,
            'user'    => [
                'user_id'      => $user->user_id,
                'first_name'   => $user->first_name,
                'last_name'    => $user->last_name,
                'NIC'          => $user->NIC,
                'email'        => $user->email,
                'phone_number' => $user->phone_number,
                'role'         => $user->role,
                'zonal_id'     => $user->zonal_id,
                'division_id'  => $user->division_id,
                'zonal_name'   => $user->zonal_name,
                'division_name'=> $user->division_name,
                'status'       => $user->status,
                'level'        => ManageUserModel::levelForRole($user->role),
            ],
        ]);
    }

    // ================================================================
    // UPDATE — AJAX POST
    // ================================================================

    public function update(string $id = ''): void {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
        }

        if (!$this->verifyCsrf($this->post('csrf_token'))) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 403);
        }

        $userId = (int) $id;
        if (!$userId) {
            $this->json(['success' => false, 'message' => 'Invalid user ID.'], 400);
        }

        $data = [
            'first_name'   => $this->post('first_name'),
            'last_name'    => $this->post('last_name'),
            'NIC'          => $this->post('nic'),
            'email'        => $this->post('email'),
            'phone_number' => $this->post('phone'),
            'role'         => $this->post('position'),
            'zonal_id'     => $this->post('zonal_id')    ?: null,
            'division_id'  => $this->post('division_id') ?: null,
        ];

        $model  = $this->model('ManageUserModel');
        $errors = $this->validateUserPayload($data, $model, $userId);

        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors], 422);
        }

        $model->updateUser($userId, $data);

        // Audit log
        $auditModel = $this->model('AuditLogModel');
        $auditModel->log(
            $_SESSION['user_id'],
            'USER_UPDATED',
            'User',
            $userId,
            "NYSC Admin updated user ID {$userId}: {$data['first_name']} {$data['last_name']}, Role: {$data['role']}"
        );

        $this->json([
            'success' => true,
            'message' => "User {$data['first_name']} {$data['last_name']} updated successfully.",
        ]);
    }

    // ================================================================
    // SET STATUS — activate / deactivate
    // ================================================================

    public function setstatus(string $id = ''): void {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
        }

        if (!$this->verifyCsrf($this->post('csrf_token'))) {
            $this->json(['success' => false, 'message' => 'Invalid security token.'], 403);
        }

        $userId    = (int) $id;
        $newStatus = $this->post('new_status'); // 'Active' or 'Disabled'

        if (!$userId || !in_array($newStatus, ['Active', 'Disabled'])) {
            $this->json(['success' => false, 'message' => 'Invalid parameters.'], 400);
        }

        $model = $this->model('ManageUserModel');
        $user  = $model->getUserById($userId);

        if (!$user) {
            $this->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        // Prevent self-deactivation
        if ($userId === (int) $_SESSION['user_id']) {
            $this->json(['success' => false, 'message' => 'You cannot deactivate your own account.'], 400);
        }

        $model->setStatus($userId, $newStatus);

        // Audit log
        $auditModel = $this->model('AuditLogModel');
        $actionType = ($newStatus === 'Active') ? 'USER_REACTIVATED' : 'USER_DEACTIVATED';
        $auditModel->log(
            $_SESSION['user_id'],
            $actionType,
            'User',
            $userId,
            "NYSC Admin {$actionType} user ID {$userId}: {$user->first_name} {$user->last_name}"
        );

        $label = ($newStatus === 'Active') ? 'reactivated' : 'deactivated';
        $this->json([
            'success'   => true,
            'message'   => "User {$user->first_name} {$user->last_name} {$label} successfully.",
            'newStatus' => $newStatus,
        ]);
    }

    // ================================================================
    // GET DIVISIONS — JSON for dynamic jurisdiction dropdown
    // ================================================================

    public function getdivisions(): void {
        $this->requireAuth();
        $zonalId = (int) $this->get('zone_id');
        $model   = $this->model('ManageUserModel');
        $divs    = $model->getDivisions($zonalId ?: null);
        $this->json(['success' => true, 'divisions' => $divs]);
    }
}
