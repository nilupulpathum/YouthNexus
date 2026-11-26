<?php

require_once __DIR__ . '/../core/Mailer.php';

class Auth extends Controller {

    // ---------------------------------------------------------------
    // SIGN IN
    // ---------------------------------------------------------------
    // ---------------------------------------------------------------
    // SIGN IN
    // ---------------------------------------------------------------
    public function signin() {
        $data = [
            'title'   => 'Sign In — YouthNexus Pulse',
            'error'   => '',
            'success' => (isset($_GET['registered']) && $_GET['registered'] == 1) ? 'Account created successfully! Please sign in with your credentials.' : '',
            'email'   => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $data['error'] = 'Please enter both your email and password.';
                $data['email'] = htmlspecialchars($email, ENT_QUOTES);
            } else {
                $userModel = $this->model('UserModel');
                $user      = $userModel->verifyLogin($email, $password);

                if ($user) {
                    // Generate 6-digit 2FA verification code
                    $code = strval(random_int(100000, 999999));

                    // Store temporary login state in session
                    $_SESSION['verification_code'] = $code;
                    $_SESSION['verification_code_created_at'] = time();
                    $_SESSION['verification_attempts'] = 0;
                    $_SESSION['temp_login'] = [
                        'user_id'      => $user->user_id,
                        'user_name'    => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                        'username'     => $user->username ?? '',
                        'email'        => $user->email,
                        'user_role'    => $user->role ?? 'UnassignedUser',
                        'division_id'  => $user->division_id ?? null,
                        'zonal_id'     => $user->zonal_id ?? null,
                        'club_id'      => $user->club_id ?? null,
                        'user_initials'=> strtoupper(
                            substr($user->first_name ?? 'U', 0, 1) .
                            substr($user->last_name  ?? 'U', 0, 1)
                        ),
                    ];

                    // Send 2FA verification email
                    if ($this->sendVerificationEmail($user->email, $_SESSION['temp_login']['user_name'], $code)) {
                        if (Mailer::mode() === 'outbox') {
                            $_SESSION['local_2fa_code'] = $code;
                            $this->redirect('auth/verify?code=' . $code);
                        }
                        $this->redirect('auth/verify');
                    } elseif ($this->isLocalRequest()) {
                        $_SESSION['local_2fa_code'] = $code;
                        $this->redirect('auth/verify?code=' . $code);
                    } else {
                        $data['error'] = 'Failed to send 2FA verification email. Please try again.';
                        $data['email'] = htmlspecialchars($email, ENT_QUOTES);
                    }
                } else {
                    $data['error'] = 'Invalid email or password, or account is suspended/disabled.';
                    $data['email'] = htmlspecialchars($email, ENT_QUOTES);
                }
            }
        }

        $this->view('auth/signin', $data);
    }

    // ---------------------------------------------------------------
    // SIGN UP
    // ---------------------------------------------------------------
    public function signup() {
        $data = [
            'title'    => 'Sign Up — YouthNexus Pulse',
            'message'  => '',
            'fullname' => '',
            'email'    => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fullname         = htmlspecialchars(trim($_POST['fullname'] ?? ''), ENT_QUOTES);
            $email            = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password         = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $agree            = isset($_POST['agree']);

            $data['fullname'] = $fullname;
            $data['email']    = htmlspecialchars($email, ENT_QUOTES);

            if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
                $data['message'] = ['type' => 'error', 'text' => 'Please fill in all fields.'];
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $data['message'] = ['type' => 'error', 'text' => 'Please enter a valid email address.'];
            } elseif ($password !== $confirm_password) {
                $data['message'] = ['type' => 'error', 'text' => 'Passwords do not match.'];
            } elseif (!$agree) {
                $data['message'] = ['type' => 'error', 'text' => 'You must agree to the Terms of Service and Privacy Policy.'];
            } else {
                // Check if email already registered
                $userModel = $this->model('UserModel');
                if ($userModel->findByEmail($email)) {
                    $data['message'] = ['type' => 'error', 'text' => 'This email is already registered. Please sign in.'];
                } else {
                    // Generate 6-digit verification code
                    $code = strval(random_int(100000, 999999));

                    // Store temp signup data in session
                    $_SESSION['verification_code'] = $code;
                    $_SESSION['verification_code_created_at'] = time();
                    $_SESSION['verification_attempts'] = 0;
                    $_SESSION['temp_signup'] = [
                        'fullname' => $fullname,
                        'email'    => $email,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                    ];

                    // Send verification email
                    if ($this->sendVerificationEmail($email, $fullname, $code)) {
                        if (Mailer::mode() === 'outbox') {
                            $_SESSION['local_2fa_code'] = $code;
                            $this->redirect('auth/verify?code=' . $code);
                        }
                        $this->redirect('auth/verify');
                    } else {
                        $data['message'] = ['type' => 'error', 'text' => 'Failed to send verification email. Please try again.'];
                    }
                }
            }
        }

        $this->view('auth/signup', $data);
    }

    // ---------------------------------------------------------------
    // VERIFY EMAIL / 2FA CODE
    // ---------------------------------------------------------------
    public function verify() {
        $targetEmail = $_SESSION['temp_login']['email'] 
                    ?? $_SESSION['temp_signup']['email'] 
                    ?? '';
        $targetName  = $_SESSION['temp_login']['user_name'] 
                    ?? $_SESSION['temp_signup']['fullname'] 
                    ?? 'User';

        if (empty($targetEmail)) {
            $this->redirect('auth/signin');
        }

        // Handle resend request
        $resent = false;
        if (isset($_GET['resend']) && $_GET['resend'] == 1) {
            $code = strval(random_int(100000, 999999));
            $_SESSION['verification_code'] = $code;
            $_SESSION['verification_code_created_at'] = time();
            $_SESSION['verification_attempts'] = 0;
            if ($this->sendVerificationEmail($targetEmail, $targetName, $code) && Mailer::mode() === 'outbox') {
                $_SESSION['local_2fa_code'] = $code;
            }
            $resent = true;
        }

        $localCode = $_GET['code'] ?? ($_SESSION['local_2fa_code'] ?? '');
        $data = [
            'title'   => 'Verify Code — YouthNexus Pulse',
            'msg'     => $localCode ? 'Local dev mode: Verification code is ' . htmlspecialchars($localCode) : ($resent ? 'A new verification code has been sent to ' . htmlspecialchars($targetEmail, ENT_QUOTES) . '.' : ''),
            'email'   => htmlspecialchars($targetEmail, ENT_QUOTES),
            'success' => false,
            'error'   => false,
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = '';
            for ($i = 1; $i <= 6; $i++) {
                $code .= $_POST['d' . $i] ?? '';
            }

            $storedCode = (string) ($_SESSION['verification_code'] ?? '');
            $createdAt = (int) ($_SESSION['verification_code_created_at'] ?? 0);
            $attempts = (int) ($_SESSION['verification_attempts'] ?? 0);
            $expired = $createdAt < 1 || (time() - $createdAt) > 600;

            if (strlen($code) !== 6 || !ctype_digit($code)) {
                $data['msg'] = 'Please enter all 6 digits.';
            } elseif ($attempts >= 5) {
                $data['msg'] = 'Too many verification attempts. Request a new code.';
                $data['error'] = true;
            } elseif ($expired) {
                $data['msg'] = 'The verification code has expired. Request a new code.';
                $data['error'] = true;
            } elseif ($storedCode !== '' && hash_equals($storedCode, $code)) {
                $userModel = $this->model('UserModel');

                if (isset($_SESSION['temp_login'])) {
                    // 2FA for Sign In: Log in the user
                    $s = $_SESSION['temp_login'];
                    $userModel->updateLastLogin($s['user_id']);
                    session_regenerate_id(true);

                    $_SESSION['user_id']      = $s['user_id'];
                    $_SESSION['user_name']     = $s['user_name'];
                    $_SESSION['username']      = $s['username'];
                    $_SESSION['user_email']    = $s['email'];
                    $_SESSION['user_role']     = $s['user_role'];
                    $_SESSION['division_id']   = $s['division_id'];
                    $_SESSION['zonal_id']      = $s['zonal_id'];
                    $_SESSION['club_id']       = $s['club_id'] ?? null;
                    $_SESSION['user_initials'] = $s['user_initials'];

                    unset($_SESSION['verification_code']);
                    unset($_SESSION['verification_code_created_at']);
                    unset($_SESSION['verification_attempts']);
                    unset($_SESSION['temp_login']);

                    // Role-based redirect after successful login
                    switch ($s['user_role']) {
                        case 'DivisionalCoordinator':
                            $this->redirect('divisionalcoordinator');
                            break;
                        case 'DivisionalSecretary':
                            $this->redirect('divisionalsecretary');
                            break;
                        case 'DivisionalTreasurer':
                            $this->redirect('divisionaltreasurer');
                            break;
                        case 'ZonalCoordinator':
                            $this->redirect('zonalcoordinator');
                            break;
                        case 'ZonalSecretary':
                            $this->redirect('zonalsecretary');
                            break;
                        case 'ZonalTreasurer':
                            $this->redirect('zonaltreasurer');
                            break;
                        case 'ClubPresident':
                            $this->redirect('president');
                            break;
                        case 'ClubSecretary':
                            $this->redirect('secretary');
                            break;
                        case 'ClubTreasurer':
                            $this->redirect('treasurer');
                            break;
                        case 'ClubMember':
                        case 'Member':
                            $this->redirect('member');
                            break;
                        case 'NYSCAdministrator':
                            $this->redirect('nationaldashboard');
                            break;
                        default:
                            $this->redirect('home');
                    }
                } elseif (isset($_SESSION['temp_signup'])) {
                    // 2FA for Sign Up: Create user account
                    $s = $_SESSION['temp_signup'];
                    $userModel->createUser($s['fullname'], $s['email'], $s['password']);

                    unset($_SESSION['verification_code']);
                    unset($_SESSION['verification_code_created_at']);
                    unset($_SESSION['verification_attempts']);
                    unset($_SESSION['temp_signup']);

                    $this->redirect('auth/signin?registered=1');
                }
            } else {
                $_SESSION['verification_attempts'] = $attempts + 1;
                $data['error'] = true;
            }
        }

        $this->view('auth/verifypage', $data);
    }

    // ---------------------------------------------------------------
    // FORGOT PASSWORD — step 1: email -> OTP + emailed reset link
    // ---------------------------------------------------------------
    public function forgetpass() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Rate limiting: 5 requests per 15 minutes per session.
        if (!isset($_SESSION['reset_attempts'])) {
            $_SESSION['reset_attempts']  = 0;
            $_SESSION['reset_last_time'] = time();
        }
        if (time() - $_SESSION['reset_last_time'] > 900) {
            $_SESSION['reset_attempts']  = 0;
            $_SESSION['reset_last_time'] = time();
        }

        $data = [
            'title'      => 'Forgot Password — YouthNexus Pulse',
            'errorMsg'   => ($_GET['error'] ?? '') === 'invalid'
                            ? 'That reset link is invalid or has expired. Please request a new code.'
                            : '',
            'emailValue' => '',
            'showPopup'  => false,
            'emailSent'  => false,
            'verifyLink' => '',
            'devCode'    => '',
            'csrf_token' => $_SESSION['csrf_token'],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $data['errorMsg'] = 'Invalid request. Please refresh the page.';
            } elseif ($_SESSION['reset_attempts'] >= 5) {
                $data['errorMsg'] = 'Too many attempts. Please try again after 15 minutes.';
            } else {
                $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
                $data['emailValue'] = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $data['errorMsg'] = 'Please enter a valid email address.';
                } else {
                    $_SESSION['reset_attempts']++;
                    unset($_SESSION['password_reset'], $_SESSION['password_reset_pending']);

                    $userModel = $this->model('UserModel');
                    $user      = $userModel->findByEmail($email);

                    if (!$user) {
                        // Same response for unknown addresses: never disclose who has an account.
                        $data['showPopup'] = true;
                    } elseif ($userModel->hasRecentPasswordReset($user->user_id, 60)) {
                        $data['errorMsg'] = 'A code was sent recently. Please wait a minute before requesting another.';
                    } else {
                        $otp       = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                        $rawToken  = bin2hex(random_bytes(32));
                        $resetId   = $userModel->createPasswordReset(
                            $user->user_id,
                            hash('sha256', $otp),
                            hash('sha256', $rawToken)
                        );
                        $resetLink = $this->resetLink($rawToken);
                        $fullName  = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

                        if ($this->sendResetEmail($user->email, $fullName, $otp, $resetLink)) {
                            $data['showPopup']  = true;
                            $data['emailSent']  = true;
                            $data['verifyLink'] = $resetLink;
                            if (Mailer::mode() === 'outbox') {
                                $data['devCode'] = $otp;
                            }
                        } else {
                            // Never leave a live code behind when delivery failed.
                            $userModel->markPasswordResetUsed($resetId);
                            $data['errorMsg'] = 'We could not send the code. Please try again later.'
                                . (Mailer::lastError() !== '' && $this->isLocalRequest()
                                    ? ' (' . htmlspecialchars(Mailer::lastError(), ENT_QUOTES) . ')'
                                    : '');
                        }
                    }

                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $data['csrf_token'] = $_SESSION['csrf_token'];
                }
            }
        }

        $this->view('auth/forgetpass', $data);
    }

    // ---------------------------------------------------------------
    // VERIFY OTP — step 2: the 6-digit code from the email
    // ---------------------------------------------------------------
    public function verifyreset($token = null) {
        $rawToken = is_string($token) ? trim($token) : trim($_GET['token'] ?? '');

        if (!preg_match('/^[a-f0-9]{64}$/i', $rawToken)) {
            $this->redirect('auth/forgetpass?error=invalid');
        }

        $userModel = $this->model('UserModel');
        $tokenHash = hash('sha256', strtolower($rawToken));
        $record    = $userModel->getPasswordResetByToken($tokenHash);

        if (!$record) {
            $this->redirect('auth/forgetpass?error=invalid');
        }

        // A verified session can go straight to the password form.
        if (isset($_SESSION['password_reset'])
            && hash_equals((string) ($_SESSION['password_reset']['token_hash'] ?? ''), $tokenHash)) {
            $this->redirect('auth/resetpass?token=' . $rawToken);
        }

        $_SESSION['password_reset_pending'] = [
            'reset_id'  => (int) $record->reset_id,
            'user_id'   => (int) $record->user_id,
            'token_hash'=> $tokenHash,
        ];
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $user  = $userModel->findByUserId($record->user_id);
        $email = $user->email ?? '';
        $data  = [
            'title'      => 'Verify Code — YouthNexus Pulse',
            'csrf_token' => $_SESSION['csrf_token'],
            'token'      => $rawToken,
            'email'      => htmlspecialchars($this->maskEmail($email), ENT_QUOTES, 'UTF-8'),
            'errorMsg'   => '',
            'notice'     => '',
            'devCode'    => '',
            'canResend'  => !$userModel->hasRecentPasswordReset($record->user_id, 60),
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $data['errorMsg'] = 'Invalid request. Please refresh the page.';
            } elseif (isset($_POST['resend'])) {
                if ($userModel->hasRecentPasswordReset($record->user_id, 60)) {
                    $data['errorMsg'] = 'Please wait a minute before requesting another code.';
                } else {
                    $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $userModel->refreshPasswordResetOtp($record->reset_id, hash('sha256', $otp));
                    $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

                    if ($this->sendResetEmail($email, $fullName, $otp, $this->resetLink($rawToken))) {
                        $data['notice'] = 'A new code has been sent to ' . $data['email'] . '.';
                        $data['canResend'] = false;
                        if (Mailer::mode() === 'outbox') {
                            $data['devCode'] = $otp;
                        }
                    } else {
                        $data['errorMsg'] = 'We could not send a new code. Please try again later.';
                    }
                }
            } else {
                $otp = trim((string) ($_POST['otp'] ?? ''));

                if (!preg_match('/^\d{6}$/', $otp)) {
                    $data['errorMsg'] = 'Please enter all 6 digits of the code.';
                } elseif ((int) $record->attempts >= 5) {
                    $userModel->markPasswordResetUsed($record->reset_id);
                    $data['errorMsg'] = 'Too many incorrect attempts. Please request a new code.';
                    $data['canResend'] = true;
                } elseif (hash_equals((string) $record->otp_code, hash('sha256', $otp))) {
                    unset($_SESSION['password_reset_pending']);
                    $_SESSION['password_reset'] = [
                        'reset_id'   => (int) $record->reset_id,
                        'user_id'    => (int) $record->user_id,
                        'token_hash' => $tokenHash,
                        'verified_at'=> time(),
                    ];
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    session_regenerate_id(true);
                    $this->redirect('auth/resetpass?token=' . $rawToken);
                } else {
                    $userModel->incrementPasswordResetAttempt($record->reset_id);
                    $remaining = 5 - ((int) $record->attempts + 1);
                    $data['errorMsg'] = $remaining > 0
                        ? 'Incorrect code. ' . $remaining . ' attempt(s) remaining.'
                        : 'Too many incorrect attempts. Please request a new code.';
                }
            }
        }

        $this->view('auth/verifyreset', $data);
    }

    // ---------------------------------------------------------------
    // RESET PASSWORD — step 3: only reachable after OTP verification
    // ---------------------------------------------------------------
    public function resetpass() {
        $rawToken = trim($_GET['token'] ?? '');
        $grant    = $_SESSION['password_reset'] ?? null;

        if (!preg_match('/^[a-f0-9]{64}$/i', $rawToken)
            || !$grant
            || !hash_equals((string) ($grant['token_hash'] ?? ''), hash('sha256', strtolower($rawToken)))) {
            $this->redirect('auth/forgetpass?error=invalid');
        }

        $userModel = $this->model('UserModel');
        $record    = $userModel->getPasswordResetById($grant['reset_id'] ?? 0);

        if (!$record || (int) $record->user_id !== (int) ($grant['user_id'] ?? 0)) {
            unset($_SESSION['password_reset']);
            $this->redirect('auth/forgetpass?error=invalid');
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $data = [
            'title'       => 'Reset Password — YouthNexus Pulse',
            'errorMsg'    => '',
            'showSuccess' => false,
            'csrf_token'  => $_SESSION['csrf_token'],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $data['errorMsg'] = 'Invalid request. Please refresh the page.';
            } else {
                $password = (string) ($_POST['password'] ?? '');
                $confirm  = (string) ($_POST['confirm_password'] ?? '');

                if (strlen($password) < 8 || !preg_match('/[0-9]/', $password) || $password !== $confirm) {
                    $data['errorMsg'] = 'Please meet all password requirements.';
                } else {
                    $userModel->updatePasswordById(
                        $grant['user_id'],
                        password_hash($password, PASSWORD_DEFAULT)
                    );
                    $userModel->invalidatePasswordResets($grant['user_id']);
                    unset($_SESSION['password_reset'], $_SESSION['password_reset_pending']);
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    session_regenerate_id(true);
                    $data['showSuccess'] = true;
                }
            }
        }

        $this->view('auth/resetpass', $data);
    }

    // ---------------------------------------------------------------
    // LOGOUT
    // ---------------------------------------------------------------
    public function logout() {
        session_destroy();
        $this->redirect('auth/signin');
    }

    // ---------------------------------------------------------------
    // PRIVATE HELPERS
    // ---------------------------------------------------------------
    private function sendVerificationEmail($email, $fullname, $code) {
        $html = '
        <div style="font-family:Arial,sans-serif;max-width:500px;margin:0 auto;background:#f4f7fb;padding:20px;">
          <div style="background:#fff;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.1);text-align:center;">
            <h2 style="color:#002d72;margin-top:0;">YouthNexus Pulse</h2>
            <p>Hello ' . htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>Your 6-digit email verification code is:</p>
            <div style="margin:25px 0;font-size:32px;font-weight:bold;letter-spacing:6px;color:#002d72;background:#f0f4f8;padding:15px;border-radius:8px;">'
                . $code .
            '</div>
            <p style="color:#666;font-size:13px;">This code is valid for 15 minutes. Never share it with anyone.</p>
          </div>
        </div>';

        $sent = Mailer::send(
            $email,
            $fullname,
            'Verify Your Email — YouthNexus Pulse',
            $html,
            "Your verification code is: $code. It is valid for 15 minutes.",
            $code
        );
        if (!$sent) {
            error_log('[YouthNexus] sendVerificationEmail failed: ' . Mailer::lastError());
        }
        return $sent;
    }

    /** Send the reset OTP and link. Returns false when delivery actually failed. */
    private function sendResetEmail($email, $fullname, $otp, $resetLink) {
        $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');
        $html = '
        <div style="font-family:Arial,sans-serif;max-width:500px;margin:0 auto;background:#f4f7fb;padding:20px;">
          <div style="background:#fff;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.1);">
            <h2 style="color:#002d72;margin-top:0;">YouthNexus Pulse</h2>
            <p>Hello ' . htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>We received a request to reset your password. Use this 6-digit code to continue:</p>
            <div style="margin:25px 0;text-align:center;font-size:32px;font-weight:bold;letter-spacing:6px;color:#002d72;background:#f0f4f8;padding:15px;border-radius:8px;">'
                . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') .
            '</div>
            <p style="text-align:center;margin:25px 0;">
              <a href="' . $safeLink . '" style="display:inline-block;padding:14px 28px;background:#002d72;color:#fff;text-decoration:none;border-radius:8px;font-weight:bold;">Enter Code</a>
            </p>
            <p style="color:#666;font-size:13px;">The code and link expire in 10 minutes. If you did not request this, please ignore this email and no one can change your password.</p>
          </div>
        </div>';

        $sent = Mailer::send(
            $email,
            $fullname,
            'Password Reset Code — YouthNexus Pulse',
            $html,
            "Your password reset code is $otp. Enter it at $resetLink. The code expires in 10 minutes.",
            $otp
        );
        if (!$sent) {
            error_log('[YouthNexus] sendResetEmail failed: ' . Mailer::lastError());
        }
        return $sent;
    }

    /** Absolute link to the OTP entry page (token is never the OTP itself). */
    private function resetLink(string $rawToken): string {
        return rtrim(ROOT, '/') . '/auth/verifyreset?token=' . rawurlencode($rawToken);
    }

    private function maskEmail(string $email): string {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return $email;
        }
        $name = $parts[0];
        $masked = strlen($name) <= 2
            ? str_repeat('*', strlen($name))
            : substr($name, 0, 2) . str_repeat('*', max(1, strlen($name) - 2));
        return $masked . '@' . $parts[1];
    }

    private function isLocalRequest(): bool {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        return (bool) preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/', $host)
            || php_sapi_name() === 'cli-server';
    }
}
