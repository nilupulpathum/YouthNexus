<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once APP_ROOT . '/core/phpmailer/Exception.php';
require_once APP_ROOT . '/core/phpmailer/PHPMailer.php';
require_once APP_ROOT . '/core/phpmailer/SMTP.php';

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
 *   POST manageuser/delete/{id}    → delete($id)    — permanent delete (guarded)
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

        // NIC: optional, format + plausibility validated, unique if provided
        if (!empty($data['NIC'])) {
            if (!preg_match('/^(\d{12}|\d{9}[VvXx])$/', $data['NIC'])) {
                $errors['NIC'] = 'NIC must be 12 digits or 9 digits followed by V/X.';
            } else {
                $nicIssue = $this->validateSriLankanNic($data['NIC']);

                if ($nicIssue !== null) {
                    $errors['NIC'] = $nicIssue;
                } elseif ($model->nicExists($data['NIC'], $excludeId)) {
                    $errors['NIC'] = 'This NIC is already registered.';
                }
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

    /**
     * Plausibility validation for Sri Lankan NIC numbers.
     *
     * New format (12 digits):  YYYY + day-of-year (3) + serial (4) + check.
     * Old format (9 digits + V/X): YY + day-of-year (3) + serial (3) + letter.
     *
     * The birth year must be a real, living-person year, and the day-of-year
     * window follows the official rule: 001-366 (male) / 501-866 (female);
     * 367-500 and 867-999 are never issued.
     *
     * @return string|null  Error message, or null when plausible.
     */
    private function validateSriLankanNic(string $nic): ?string {
        $currentYear = (int) date('Y');

        if (preg_match('/^\d{12}$/', $nic)) {
            $birthYear = (int) substr($nic, 0, 4);
            $dayOfYear = (int) substr($nic, 4, 3);
        } else {
            $yy        = (int) substr($nic, 0, 2);
            $birthYear = $yy <= (int) date('y') ? 2000 + $yy : 1900 + $yy;
            $dayOfYear = (int) substr($nic, 2, 3);
        }

        if ($birthYear < 1900 || $birthYear > $currentYear) {
            return "The birth year in this NIC ({$birthYear}) is outside the valid "
                . 'Sri Lankan NIC range (1900-' . $currentYear . ').';
        }

        if (!($dayOfYear >= 1 && $dayOfYear <= 366)
            && !($dayOfYear >= 501 && $dayOfYear <= 866)
        ) {
            return 'The day-of-year digits in this NIC are outside the valid '
                . 'Sri Lankan NIC range (001-366 or 501-866).';
        }

        return null;
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
            'title'          => 'Manage User — YouthNexus',
            'currentRoute'   => 'manageuser',
            'userRole'       => $_SESSION['user_role'] ?? '',
            'userName'       => ($_SESSION['user_first_name'] ?? '') . ' ' . ($_SESSION['user_last_name'] ?? ''),
            'pageTitle'      => 'National Personnel & User Governance',
            'pageDescription'=> 'Manage NYSC administrative personnel across all zones and divisions.',

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

        // Email the login credentials to the new user. On localhost the
        // bundled SMTP has no credentials, so log them for QA instead
        // (same pragmatism as Settings::sendPasswordCodeEmail). The account
        // is already created at this point, so a send failure never fails
        // the request — the admin still sees the temp password in the modal.
        $fullName = $data['first_name'] . ' ' . $data['last_name'];
        $emailSent = $this->sendAccountCreatedEmail($data['email'], $fullName, $data['role'], $tempPassword);
        if (!$emailSent && $this->isLocalRequest()) {
            error_log('[YouthNexus] LOCAL-ONLY new-account credentials for ' . $data['email'] . ': ' . $tempPassword);
        }

        $this->json([
            'success'      => true,
            'message'      => "User {$data['first_name']} {$data['last_name']} created successfully.",
            'tempPassword' => $tempPassword,
            'userId'       => $newUserId,
            'emailSent'    => $emailSent,
        ]);
    }

    // ================================================================
    // OUTBOUND EMAIL — account-created credentials
    // ================================================================

    private function isLocalRequest(): bool {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        return (bool) preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/', $host)
            || php_sapi_name() === 'cli-server';
    }

    private function sendAccountCreatedEmail(string $email, string $name, string $role, string $tempPassword): bool {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER;
            $mail->Password   = MAIL_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($email);

            $safeName  = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $safeRole  = htmlspecialchars($role, ENT_QUOTES, 'UTF-8');
            $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

            $mail->isHTML(true);
            $mail->Subject = 'Your YouthNexus account has been created';
            $mail->Body = '
            <div style="font-family:Arial,sans-serif;max-width:500px;margin:0 auto;background:#f4f7fb;padding:20px;">
              <div style="background:#fff;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.1);text-align:center;">
                <h2 style="color:#002d72;margin-top:0;">YouthNexus Pulse</h2>
                <p>Hello ' . $safeName . ',</p>
                <p>Your YouthNexus account has been created with the position <strong>' . $safeRole . '</strong>.
                   Use the credentials below to sign in:</p>
                <div style="margin:25px 0;text-align:left;background:#f0f4f8;padding:15px;border-radius:8px;font-size:14px;">
                  <p style="margin:0 0 8px;"><strong>Sign in at:</strong> <a href="' . ROOT . '/auth/signin">' . ROOT . '/auth/signin</a></p>
                  <p style="margin:0 0 8px;"><strong>Email:</strong> ' . $safeEmail . '</p>
                  <p style="margin:0;"><strong>Temporary password:</strong> <span style="font-size:18px;font-weight:bold;letter-spacing:2px;color:#002d72;">' . htmlspecialchars($tempPassword, ENT_QUOTES, 'UTF-8') . '</span></p>
                </div>
                <p style="color:#666;font-size:13px;">For security, you will be asked to change this temporary password the first time you sign in. If you were not expecting this account, please ignore this email.</p>
              </div>
            </div>';
            $mail->AltBody = "Hello $name,\n\nYour YouthNexus account ($role) has been created.\n"
                . "Sign in at: " . ROOT . "/auth/signin\n"
                . "Email: $email\n"
                . "Temporary password: $tempPassword\n\n"
                . "You will be asked to change this password the first time you sign in.";
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('[YouthNexus] sendAccountCreatedEmail SMTP error for ' . $email . ': ' . $mail->ErrorInfo);
            return false;
        }
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
    // DELETE — permanent removal (hard delete)
    // ================================================================

    public function delete(string $id = ''): void {
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

        $model = $this->model('ManageUserModel');
        $user  = $model->getUserById($userId);

        if (!$user) {
            $this->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        // Prevent self-deletion
        if ($userId === (int) $_SESSION['user_id']) {
            $this->json(['success' => false, 'message' => 'You cannot permanently delete your own account.'], 400);
        }

        // Protect administrators from an accidental lock-out
        if ($user->role === 'NYSCAdministrator' && !$model->otherActiveAdminExists($userId)) {
            $this->json(['success' => false, 'message' => 'Cannot delete the last NYSC Administrator account.'], 400);
        }

        // Preserve the soft-delete policy: accounts with any financial,
        // audit, or operational history keep their records. Such accounts
        // are marked Suspended instead — they vanish from the management
        // list (only Active/Disabled are listed) while history stays intact.
        $blockers = $model->getDeletionBlockers($userId);
        if (!empty($blockers)) {
            $model->setStatus($userId, 'Suspended');

            $auditModel = $this->model('AuditLogModel');
            $auditModel->log(
                $_SESSION['user_id'],
                'USER_SUSPENDED',
                'User',
                $userId,
                "NYSC Admin suspended user ID {$userId}: {$user->first_name} {$user->last_name} (permanent delete blocked: {$blockers[0]})"
            );

            $this->json([
                'success'   => false,
                'suspended' => true,
                'message'   => "This account cannot be permanently deleted because it has {$blockers[0]}. "
                    . 'It has been marked as Suspended instead and will no longer appear in the user list.',
            ]);
        }

        try {
            $model->deleteUserPermanently($userId);
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'This account is still referenced by other records and cannot be permanently deleted. Deactivate it instead.',
            ], 409);
        }

        // Audit log (records the acting admin; the target id is kept for reference)
        $auditModel = $this->model('AuditLogModel');
        $auditModel->log(
            $_SESSION['user_id'],
            'USER_PERMANENTLY_DELETED',
            'User',
            $userId,
            "NYSC Admin permanently deleted user ID {$userId}: {$user->first_name} {$user->last_name} ({$user->email}), Role: {$user->role}"
        );

        $this->json([
            'success' => true,
            'message' => "User {$user->first_name} {$user->last_name} permanently deleted.",
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
