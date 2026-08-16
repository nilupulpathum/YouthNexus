<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../app/core/phpmailer/Exception.php';
require_once '../app/core/phpmailer/PHPMailer.php';
require_once '../app/core/phpmailer/SMTP.php';

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
                    $code = strval(rand(100000, 999999));

                    // Store temporary login state in session
                    $_SESSION['verification_code'] = $code;
                    $_SESSION['temp_login'] = [
                        'user_id'   => $user->user_id,
                        'user_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                        'username'  => $user->username ?? '',
                        'email'     => $user->email,
                        'user_role' => $user->role ?? 'UnassignedUser',
                    ];

                    // Send 2FA verification email
                    if ($this->sendVerificationEmail($user->email, $_SESSION['temp_login']['user_name'], $code)) {
                        $this->redirect('auth/verify');
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
                    $code = strval(rand(100000, 999999));

                    // Store temp signup data in session
                    $_SESSION['verification_code'] = $code;
                    $_SESSION['temp_signup'] = [
                        'fullname' => $fullname,
                        'email'    => $email,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                    ];

                    // Send verification email
                    if ($this->sendVerificationEmail($email, $fullname, $code)) {
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
            $code = strval(rand(100000, 999999));
            $_SESSION['verification_code'] = $code;
            $this->sendVerificationEmail($targetEmail, $targetName, $code);
            $resent = true;
        }

        $data = [
            'title'   => 'Verify Code — YouthNexus Pulse',
            'msg'     => $resent ? 'A new verification code has been sent to ' . htmlspecialchars($targetEmail, ENT_QUOTES) . '.' : '',
            'email'   => htmlspecialchars($targetEmail, ENT_QUOTES),
            'success' => false,
            'error'   => false,
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = '';
            for ($i = 1; $i <= 6; $i++) {
                $code .= $_POST['d' . $i] ?? '';
            }

            if (strlen($code) !== 6) {
                $data['msg'] = 'Please enter all 6 digits.';
            } elseif ($code === ($_SESSION['verification_code'] ?? '')) {
                $userModel = $this->model('UserModel');

                if (isset($_SESSION['temp_login'])) {
                    // 2FA for Sign In: Log in the user
                    $s = $_SESSION['temp_login'];
                    $userModel->updateLastLogin($s['user_id']);

                    $_SESSION['user_id']   = $s['user_id'];
                    $_SESSION['user_name'] = $s['user_name'];
                    $_SESSION['username']  = $s['username'];
                    $_SESSION['user_email']= $s['email'];
                    $_SESSION['user_role'] = $s['user_role'];

                    unset($_SESSION['verification_code']);
                    unset($_SESSION['temp_login']);

                    $this->redirect('home');
                } elseif (isset($_SESSION['temp_signup'])) {
                    // 2FA for Sign Up: Create user account
                    $s = $_SESSION['temp_signup'];
                    $userModel->createUser($s['fullname'], $s['email'], $s['password']);

                    unset($_SESSION['verification_code']);
                    unset($_SESSION['temp_signup']);

                    $this->redirect('auth/signin?registered=1');
                }
            } else {
                $data['error'] = true;
            }
        }

        $this->view('auth/verifypage', $data);
    }

    // ---------------------------------------------------------------
    // FORGOT PASSWORD
    // ---------------------------------------------------------------
    public function forgetpass() {
        // CSRF token
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Rate limiting
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
            'errorMsg'   => '',
            'emailValue' => '',
            'showPopup'  => false,
            'csrf_token' => $_SESSION['csrf_token'],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $data['errorMsg'] = 'Invalid request. Please refresh the page.';
            } elseif ($_SESSION['reset_attempts'] >= 5) {
                $data['errorMsg'] = 'Too many attempts. Please try again after 15 minutes.';
            } else {
                $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
                $data['emailValue'] = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $data['errorMsg'] = 'Please enter a valid email address.';
                } else {
                    $_SESSION['reset_attempts']++;

                    $userModel = $this->model('UserModel');
                    $user      = $userModel->findByEmail($email);

                    if ($user) {
                        $rawToken  = bin2hex(random_bytes(16));
                        $tokenHash = hash('sha256', $rawToken);

                        // Record reset token in PasswordReset database table (expires in 1 hour)
                        $userModel->createPasswordReset($user->user_id, $tokenHash);

                        // Build reset link
                        $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                        $resetLink = $protocol . $_SERVER['HTTP_HOST']
                                   . rtrim(dirname($_SERVER['PHP_SELF']), '/\\')
                                   . '/auth/resetpass?token=' . $rawToken
                                   . '&email=' . urlencode($email);

                        $this->sendResetEmail($email, $resetLink);
                    }

                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $data['showPopup']  = true;
                    $data['csrf_token'] = $_SESSION['csrf_token'];
                }
            }
        }

        $this->view('auth/forgetpass', $data);
    }

    // ---------------------------------------------------------------
    // RESET PASSWORD
    // ---------------------------------------------------------------
    public function resetpass() {
        $token = trim($_GET['token'] ?? '');
        $email = trim($_GET['email'] ?? '');

        $userModel = $this->model('UserModel');
        $user      = $userModel->findByEmail($email);

        $valid       = false;
        $resetRecord = null;

        if (!empty($token) && !empty($email) && $user) {
            $tokenHash   = hash('sha256', $token);
            $resetRecord = $userModel->getValidPasswordReset($user->user_id, $tokenHash);
            if ($resetRecord) {
                $valid = true;
            }
        }

        if (!$valid) {
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
                $password = $_POST['password'] ?? '';
                $confirm  = $_POST['confirm_password'] ?? '';

                $hasLength = strlen($password) >= 8;
                $hasNumber = preg_match('/[0-9]/', $password);
                $matches   = ($password === $confirm && !empty($password));

                if ($hasLength && $hasNumber && $matches) {
                    $userModel->updatePassword($email, password_hash($password, PASSWORD_DEFAULT));

                    if ($resetRecord) {
                        $userModel->markPasswordResetUsed($resetRecord->reset_id);
                    }

                    $data['showSuccess'] = true;
                } else {
                    $data['errorMsg'] = 'Please meet all password requirements.';
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
            $mail->Subject = 'Verify Your Email — YouthNexus Pulse';
            $mail->Body = '
            <div style="font-family:Arial,sans-serif;max-width:500px;margin:0 auto;background:#f4f7fb;padding:20px;">
              <div style="background:#fff;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.1);text-align:center;">
                <h2 style="color:#002d72;margin-top:0;">YouthNexus Pulse</h2>
                <p>Hello ' . htmlspecialchars($fullname, ENT_QUOTES) . ',</p>
                <p>Your 6-digit email verification code is:</p>
                <div style="margin:25px 0;font-size:32px;font-weight:bold;letter-spacing:6px;color:#002d72;background:#f0f4f8;padding:15px;border-radius:8px;">'
                    . $code .
                '</div>
                <p style="color:#666;font-size:13px;">This code is valid for 15 minutes.</p>
              </div>
            </div>';
            $mail->AltBody = "Your verification code is: $code";
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function sendResetEmail($email, $resetLink) {
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
            $mail->Subject = 'Password Reset — YouthNexus Pulse';
            $mail->Body = '
            <div style="font-family:Arial,sans-serif;max-width:500px;margin:0 auto;background:#f4f7fb;padding:20px;">
              <div style="background:#fff;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.1);">
                <h2 style="color:#002d72;margin-top:0;">YouthNexus Pulse</h2>
                <p>You requested a password reset for your account.</p>
                <p style="text-align:center;margin:25px 0;">
                  <a href="' . $resetLink . '" style="display:inline-block;padding:14px 28px;background:#002d72;color:#fff;text-decoration:none;border-radius:8px;font-weight:bold;">Reset Password</a>
                </p>
                <p style="color:#666;font-size:13px;">This link expires in 1 hour. If you did not request this, please ignore this email.</p>
              </div>
            </div>';
            $mail->AltBody = "Reset your password: $resetLink";
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
