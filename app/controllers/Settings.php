<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../app/core/phpmailer/Exception.php';
require_once '../app/core/phpmailer/PHPMailer.php';
require_once '../app/core/phpmailer/SMTP.php';

class Settings extends Controller {

    // Password-change code policy (owner-approved 2026-09-26).
    const PW_CODE_TTL = 900;        // 15 minutes
    const PW_MAX_ATTEMPTS = 5;      // wrong-code tries before state is wiped
    const PW_MAX_SENDS = 3;         // code emails per window
    const PW_SEND_WINDOW = 900;     // 15 minutes
    const PW_RESEND_COOLDOWN = 60;  // seconds between sends
    const PW_MIN_LENGTH = 8;

    public function index() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }

        $headerNotif = ZoneOverview::headerNotifications($this);

        $data = [
            'title' => 'Profile Settings — YouthNexus Pulse',
            'pageTitle' => 'Profile Settings',
            'pageDescription' => 'Manage your profile, security, and notification preferences.',
            'currentRoute' => 'settings',
            'userRole' => $_SESSION['user_role'] ?? 'ClubMember',
            'userName' => trim((string) ($_SESSION['user_name'] ?? '')) !== '' ? trim((string) $_SESSION['user_name']) : 'YouthNexus User',
            'userEmail' => $_SESSION['user_email'] ?? '',
            'userInitials' => $_SESSION['user_initials'] ?? 'YN',
            'unreadNotificationCount' => $headerNotif['count'],
            'headerNotifications' => $headerNotif['items'],
            'user' => $this->profileFormValues(),
            'csrf_token' => $this->ensureCsrfToken(),
            'maskedEmail' => $this->maskedEmail((string) ($_SESSION['user_email'] ?? '')),
        ];

        $this->view('settings/index', $data);
    }

    private function ensureCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private function verifyCsrf() {
        $token = (string) ($_POST['csrf_token'] ?? '');
        return $token !== '' && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
    }

    private function jsonResponse($status, array $payload) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit();
    }

    private function maskedEmail($email) {
        $parts = explode('@', (string) $email);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return '';
        }
        return mb_substr($parts[0], 0, 1) . '***@' . $parts[1];
    }

    private function isLocalRequest() {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        return (bool) preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/', $host)
            || php_sapi_name() === 'cli-server';
    }

    /**
     * Step 1: validate current + new passwords, email a 6-digit code, stash
     * the pending change server-side. The code modal opens on {ok:true}.
     * POST only, JSON in/out.
     */
    public function updatePassword() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->jsonResponse(405, ['ok' => false, 'error' => 'This action requires POST.']);
        }
        if (!$this->verifyCsrf()) {
            $this->jsonResponse(403, ['ok' => false, 'error' => 'Invalid request. Please refresh the page.']);
        }

        $current = isset($_POST['current']) && is_string($_POST['current']) ? $_POST['current'] : '';
        $new = isset($_POST['new']) && is_string($_POST['new']) ? $_POST['new'] : '';
        $confirm = isset($_POST['confirm']) && is_string($_POST['confirm']) ? $_POST['confirm'] : '';

        $userId = (int) $_SESSION['user_id'];
        $row = $this->model('UserModel')->findByUserId($userId);
        if (!$row) {
            $this->jsonResponse(403, ['ok' => false, 'error' => 'Your user account could not be loaded.']);
        }
        if ($current === '' || !password_verify($current, (string) ($row->password_hash ?? ''))) {
            $this->jsonResponse(422, ['ok' => false, 'error' => 'Current password is incorrect.']);
        }
        if (mb_strlen($new) < self::PW_MIN_LENGTH) {
            $this->jsonResponse(422, ['ok' => false, 'error' => 'New password must be at least ' . self::PW_MIN_LENGTH . ' characters.']);
        }
        if ($new !== $confirm) {
            $this->jsonResponse(422, ['ok' => false, 'error' => 'New passwords do not match.']);
        }
        if (password_verify($new, (string) ($row->password_hash ?? ''))) {
            $this->jsonResponse(422, ['ok' => false, 'error' => 'New password must be different from the current one.']);
        }

        // Send rate limit (separate from the pending-change state).
        $now = time();
        $sends = $_SESSION['pw_sends'] ?? null;
        if (!is_array($sends) || ($now - (int) ($sends['window_start'] ?? 0)) >= self::PW_SEND_WINDOW) {
            $sends = ['window_start' => $now, 'count' => 0];
        }
        if ((int) $sends['count'] >= self::PW_MAX_SENDS) {
            $this->jsonResponse(429, ['ok' => false, 'error' => 'Too many codes sent. Please try again later.']);
        }

        $code = (string) random_int(100000, 999999);
        $_SESSION['pw_change'] = [
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'new_hash' => password_hash($new, PASSWORD_DEFAULT),
            'expires' => $now + self::PW_CODE_TTL,
            'attempts' => 0,
            'sent_at' => $now,
        ];
        $sends['count']++;
        $_SESSION['pw_sends'] = $sends;

        $email = (string) ($row->email ?? '');
        $name = trim(trim((string) ($row->first_name ?? '')) . ' ' . trim((string) ($row->last_name ?? '')));
        if (!$this->sendPasswordCodeEmail($email, $name !== '' ? $name : 'there', $code)) {
            // Production: fail closed and keep no pending state. Localhost:
            // the bundled SMTP has no credentials, so log the code for QA
            // and let the flow continue (same localhost pragmatism as
            // _local_login.php skipping the 2FA email). The unset MUST stay
            // inside the production branch — unsetting before the localhost
            // fallthrough wipes the pending change we just stored.
            if (!$this->isLocalRequest()) {
                unset($_SESSION['pw_change']);
                $this->jsonResponse(500, ['ok' => false, 'error' => 'Could not send the verification email. Please try again.']);
            }
            error_log('[YouthNexus] LOCAL-ONLY password-change code for ' . $email . ': ' . $code);
        }

        $this->jsonResponse(200, ['ok' => true]);
    }

    /**
     * Step 1b: re-send a fresh code for the pending change (cool-down
     * enforced, wrong-code attempts are NOT reset).
     * POST only, JSON in/out.
     */
    public function resendCode() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->jsonResponse(405, ['ok' => false, 'error' => 'This action requires POST.']);
        }
        if (!$this->verifyCsrf()) {
            $this->jsonResponse(403, ['ok' => false, 'error' => 'Invalid request. Please refresh the page.']);
        }
        $state = $_SESSION['pw_change'] ?? null;
        $now = time();
        if (!is_array($state) || $now >= (int) ($state['expires'] ?? 0)) {
            unset($_SESSION['pw_change']);
            $this->jsonResponse(410, ['ok' => false, 'error' => 'Code expired. Start again.']);
        }
        if ($now - (int) ($state['sent_at'] ?? 0) < self::PW_RESEND_COOLDOWN) {
            $wait = self::PW_RESEND_COOLDOWN - ($now - (int) $state['sent_at']);
            $this->jsonResponse(429, ['ok' => false, 'error' => 'Please wait ' . $wait . 's before resending.']);
        }
        $sends = $_SESSION['pw_sends'] ?? null;
        if (!is_array($sends) || ($now - (int) ($sends['window_start'] ?? 0)) >= self::PW_SEND_WINDOW) {
            $sends = ['window_start' => $now, 'count' => 0];
        }
        if ((int) $sends['count'] >= self::PW_MAX_SENDS) {
            $this->jsonResponse(429, ['ok' => false, 'error' => 'Too many codes sent. Please try again later.']);
        }

        $row = $this->model('UserModel')->findByUserId((int) $_SESSION['user_id']);
        if (!$row) {
            unset($_SESSION['pw_change']);
            $this->jsonResponse(403, ['ok' => false, 'error' => 'Your user account could not be loaded.']);
        }
        $code = (string) random_int(100000, 999999);
        $_SESSION['pw_change']['code_hash'] = password_hash($code, PASSWORD_DEFAULT);
        $_SESSION['pw_change']['sent_at'] = $now;
        $sends['count']++;
        $_SESSION['pw_sends'] = $sends;

        $email = (string) ($row->email ?? '');
        $name = trim(trim((string) ($row->first_name ?? '')) . ' ' . trim((string) ($row->last_name ?? '')));
        if (!$this->sendPasswordCodeEmail($email, $name !== '' ? $name : 'there', $code)) {
            if (!$this->isLocalRequest()) {
                $this->jsonResponse(500, ['ok' => false, 'error' => 'Could not send the verification email. Please try again.']);
            }
            error_log('[YouthNexus] LOCAL-ONLY password-change code for ' . $email . ': ' . $code);
        }

        $this->jsonResponse(200, ['ok' => true]);
    }

    /**
     * Step 2: confirm the emailed code and apply the stashed new password.
     * POST only, JSON in/out.
     */
    public function confirmPassword() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->jsonResponse(405, ['ok' => false, 'error' => 'This action requires POST.']);
        }
        if (!$this->verifyCsrf()) {
            $this->jsonResponse(403, ['ok' => false, 'error' => 'Invalid request. Please refresh the page.']);
        }
        $state = $_SESSION['pw_change'] ?? null;
        $now = time();
        if (!is_array($state)) {
            $this->jsonResponse(410, ['ok' => false, 'error' => 'No pending change. Start again.']);
        }
        if ($now >= (int) ($state['expires'] ?? 0)) {
            unset($_SESSION['pw_change']);
            $this->jsonResponse(410, ['ok' => false, 'error' => 'Code expired. Start again.']);
        }
        if ((int) ($state['attempts'] ?? 0) >= self::PW_MAX_ATTEMPTS) {
            unset($_SESSION['pw_change']);
            $this->jsonResponse(429, ['ok' => false, 'error' => 'Too many wrong codes. Start again.']);
        }
        $code = isset($_POST['code']) && is_string($_POST['code']) ? trim($_POST['code']) : '';
        if (!preg_match('/^\d{6}$/', $code) || !password_verify($code, (string) ($state['code_hash'] ?? ''))) {
            $_SESSION['pw_change']['attempts'] = (int) ($state['attempts'] ?? 0) + 1;
            $left = self::PW_MAX_ATTEMPTS - (int) $_SESSION['pw_change']['attempts'];
            $this->jsonResponse(422, ['ok' => false, 'error' => 'Incorrect code.' . ($left > 0 ? ' ' . $left . ' attempt' . ($left === 1 ? '' : 's') . ' left.' : '')]);
        }

        $userId = (int) $_SESSION['user_id'];
        $row = $this->model('UserModel')->findByUserId($userId);
        if (!$row) {
            unset($_SESSION['pw_change']);
            $this->jsonResponse(403, ['ok' => false, 'error' => 'Your user account could not be loaded.']);
        }
        $this->model('UserModel')->updatePassword((string) $row->email, (string) ($state['new_hash'] ?? ''));
        $this->model('AuditLogModel')->log($userId, 'PASSWORD_CHANGE', 'User', $userId, 'Password changed via verified code');
        unset($_SESSION['pw_change']);

        $this->jsonResponse(200, ['ok' => true]);
    }

    /**
     * Edit Profile pane: validate and persist the profile form to the User
     * row, then refresh the session identity (header/popup/masked email).
     * POST only, JSON in/out.
     */
    public function updateProfile() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->jsonResponse(405, ['ok' => false, 'error' => 'This action requires POST.']);
        }
        if (!$this->verifyCsrf()) {
            $this->jsonResponse(403, ['ok' => false, 'error' => 'Invalid request. Please refresh the page.']);
        }

        $userId = (int) $_SESSION['user_id'];
        $row = $this->model('UserModel')->findByUserId($userId);
        if (!$row) {
            $this->jsonResponse(403, ['ok' => false, 'error' => 'Your user account could not be loaded.']);
        }

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));

        if ($firstName === '' || $lastName === '') {
            $this->jsonResponse(422, ['ok' => false, 'error' => 'First name and last name are required.']);
        }
        if (mb_strlen($firstName) > 50 || mb_strlen($lastName) > 50) {
            $this->jsonResponse(422, ['ok' => false, 'error' => 'Names must be 50 characters or fewer.']);
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
            $this->jsonResponse(422, ['ok' => false, 'error' => 'Enter a valid email address.']);
        }
        if (strcasecmp($email, (string) $row->email) !== 0
            && $this->model('UserModel')->emailOrNicTaken($email, '', $userId)) {
            $this->jsonResponse(422, ['ok' => false, 'error' => 'That email address is already in use by another account.']);
        }
        if ($phone !== '' && !preg_match('/^[0-9+()\-\s.]{6,20}$/', $phone)) {
            $this->jsonResponse(422, ['ok' => false, 'error' => 'Enter a valid phone number (up to 20 characters).']);
        }
        if (mb_strlen($address) > 255) {
            $this->jsonResponse(422, ['ok' => false, 'error' => 'Address must be 255 characters or fewer.']);
        }

        $this->model('UserModel')->updateProfile($userId, $firstName, $lastName, $email, $phone, $address);
        $this->model('AuditLogModel')->log($userId, 'PROFILE_UPDATE', 'User', $userId, 'Updated profile details');

        // Keep the session identity in step with the saved row (topbar popup,
        // masked email in the password modal, next page loads).
        $_SESSION['user_name'] = trim($firstName . ' ' . $lastName);
        $_SESSION['user_email'] = $email;
        $_SESSION['user_initials'] = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

        $this->jsonResponse(200, [
            'ok' => true,
            'user' => [
                'name' => $_SESSION['user_name'],
                'email' => $email,
                'initials' => $_SESSION['user_initials'],
            ],
        ]);
    }

    /**
     * Edit Profile pane: upload a new profile picture. Multipart POST, JSON out.
     */
    public function uploadPicture() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->jsonResponse(405, ['ok' => false, 'error' => 'This action requires POST.']);
        }
        if (!$this->verifyCsrf()) {
            $this->jsonResponse(403, ['ok' => false, 'error' => 'Invalid request. Please refresh the page.']);
        }

        $userId = (int) $_SESSION['user_id'];
        $row = $this->model('UserModel')->findByUserId($userId);
        if (!$row) {
            $this->jsonResponse(403, ['ok' => false, 'error' => 'Your user account could not be loaded.']);
        }

        [$path, $error] = $this->handleProfilePictureUpload($_FILES['picture'] ?? null);
        if ($error !== null) {
            $this->jsonResponse(422, ['ok' => false, 'error' => $error]);
        }

        // Replacing: drop the previous file so uploads/profile_pictures stays tidy.
        $this->removeProfilePictureFile((string) ($row->profile_picture_url ?? ''));
        $this->model('UserModel')->updateProfilePicture($userId, $path);
        $this->model('AuditLogModel')->log($userId, 'PROFILE_PICTURE_UPDATE', 'User', $userId, 'Uploaded a new profile picture');

        $this->jsonResponse(200, [
            'ok' => true,
            'url' => (defined('ROOT') ? ROOT : '') . '/../' . $path,
        ]);
    }

    /**
     * Edit Profile pane: remove the profile picture (reverts to initials).
     * POST only, JSON in/out.
     */
    public function deletePicture() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->jsonResponse(405, ['ok' => false, 'error' => 'This action requires POST.']);
        }
        if (!$this->verifyCsrf()) {
            $this->jsonResponse(403, ['ok' => false, 'error' => 'Invalid request. Please refresh the page.']);
        }

        $userId = (int) $_SESSION['user_id'];
        $row = $this->model('UserModel')->findByUserId($userId);
        if (!$row) {
            $this->jsonResponse(403, ['ok' => false, 'error' => 'Your user account could not be loaded.']);
        }

        $this->removeProfilePictureFile((string) ($row->profile_picture_url ?? ''));
        $this->model('UserModel')->updateProfilePicture($userId, null);
        $this->model('AuditLogModel')->log($userId, 'PROFILE_PICTURE_DELETE', 'User', $userId, 'Removed the profile picture');

        $this->jsonResponse(200, ['ok' => true]);
    }

    /**
     * Notifications pane: persist both toggle preferences. POST only, JSON in/out.
     */
    public function updateNotificationPrefs() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->jsonResponse(405, ['ok' => false, 'error' => 'This action requires POST.']);
        }
        if (!$this->verifyCsrf()) {
            $this->jsonResponse(403, ['ok' => false, 'error' => 'Invalid request. Please refresh the page.']);
        }

        $userId = (int) $_SESSION['user_id'];
        $emailEnabled = ($_POST['email_enabled'] ?? '') === '1';
        $announcementsEmail = ($_POST['announcements_email'] ?? '') === '1';

        $this->model('UserModel')->updateNotificationPrefs($userId, $emailEnabled, $announcementsEmail);
        $this->model('AuditLogModel')->log(
            $userId,
            'NOTIFICATION_PREFS_UPDATE',
            'User',
            $userId,
            'Email notifications ' . ($emailEnabled ? 'on' : 'off') . ', announcement emails ' . ($announcementsEmail ? 'on' : 'off')
        );

        $this->jsonResponse(200, ['ok' => true]);
    }

    /**
     * Validate and store an uploaded picture under
     * uploads/profile_pictures/. Returns [repoRelativePath, null] on
     * success or [null, errorMessage].
     */
    private function handleProfilePictureUpload($file) {
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [null, 'Upload failed. Please choose a picture and try again.'];
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            return [null, 'The picture must be 2 MB or smaller.'];
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) {
            return [null, 'Use a JPG, PNG or WEBP picture.'];
        }
        $dir = dirname(__DIR__, 2) . '/uploads/profile_pictures';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = uniqid('yn_', true) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            return [null, 'Could not save the picture. Please try again.'];
        }
        return ['uploads/profile_pictures/' . $name, null];
    }

    /**
     * Delete a previously stored profile picture. Only paths inside
     * uploads/profile_pictures/ are ever touched.
     */
    private function removeProfilePictureFile($relativePath) {
        if (!is_string($relativePath) || $relativePath === '') {
            return;
        }
        if (!preg_match('#^uploads/profile_pictures/[^/\\\\]+$#', $relativePath)) {
            return;
        }
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function sendPasswordCodeEmail($email, $name, $code) {
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

            $mail->isHTML(true);
            $mail->Subject = 'Confirm your password change — YouthNexus Pulse';
            $mail->Body = '
            <div style="font-family:Arial,sans-serif;max-width:500px;margin:0 auto;background:#f4f7fb;padding:20px;">
              <div style="background:#fff;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.1);text-align:center;">
                <h2 style="color:#002d72;margin-top:0;">YouthNexus Pulse</h2>
                <p>Hello ' . htmlspecialchars($name, ENT_QUOTES) . ',</p>
                <p>Enter this 6-digit code to confirm your password change:</p>
                <div style="margin:25px 0;font-size:32px;font-weight:bold;letter-spacing:6px;color:#002d72;background:#f0f4f8;padding:15px;border-radius:8px;">'
                    . $code .
                '</div>
                <p style="color:#666;font-size:13px;">This code is valid for 15 minutes. If you did not request this, your password is safe — just ignore this email.</p>
              </div>
            </div>';
            $mail->AltBody = "Your password-change confirmation code is: $code (valid 15 minutes).";
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('[YouthNexus] sendPasswordCodeEmail SMTP error: ' . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Edit Profile form values from the viewer's own live User row, never
     * another person's — including the stored picture and the real
     * notification preferences (persisted by updateProfile /
     * updateNotificationPrefs, see settings.js).
     */
    private function profileFormValues(): array {
        $row = $this->model('UserModel')->findByUserId((int) ($_SESSION['user_id'] ?? 0));
        $sessionName = trim((string) ($_SESSION['user_name'] ?? ''));
        $nameParts = preg_split('/\s+/u', $sessionName !== '' ? $sessionName : 'YouthNexus User', 2);
        return [
            'first_name' => $row->first_name ?? ($nameParts[0] ?? ''),
            'last_name' => $row->last_name ?? ($nameParts[1] ?? ''),
            'email' => $row->email ?? ($_SESSION['user_email'] ?? ''),
            'phone' => $row->phone_number ?? '',
            'address' => $row->address ?? '',
            'profile_picture_url' => $row->profile_picture_url ?? '',
            'notifications' => [
                'email_enabled' => (bool) ($row->email_notifications_enabled ?? 1),
                'announcements_email' => (bool) ($row->announcement_email_enabled ?? 1),
            ],
        ];
    }
}
