<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="<?= ROOT ?>/assets/css/common.css">
  <style>
    .brand-label { color: #002d72; font-size: 22px; font-weight: bold; margin-bottom: 30px; }
    h2 { color: #1a202c; font-size: 24px; margin: 0 0 12px; }
    .desc { color: #555; font-size: 14px; line-height: 1.6; margin-bottom: 28px; }
    .back-link {
      display: inline-flex; align-items: center; gap: 6px;
      color: #666; font-size: 14px; text-decoration: none; margin-top: 22px;
    }
    .back-link:hover { color: #002d72; }
    .footer-text { margin-top: 28px; color: #888; font-size: 13px; }
    .bottom-bar {
      position: fixed; bottom: 0; left: 0; width: 100%;
      background: #eef1f7; padding: 14px 40px;
      display: flex; justify-content: space-between; align-items: center;
      font-size: 13px; color: #666;
    }
    .bottom-bar a { color: #555; text-decoration: none; margin-left: 20px; }
    .bottom-bar a:hover { color: #002d72; }
    .bottom-brand { color: #002d72; font-weight: bold; }
    .popup-footer { font-size: 13px; color: #666; }
    .popup-footer a { color: #002d72; text-decoration: none; font-weight: 500; }
  </style>
</head>
<body class="auth-card-body">

  <div class="card">
    <div class="brand-label">YouthNexus Pulse</div>

    <h2>Forgot password?</h2>
    <p class="desc">Enter the email used for your account and we'll send you a link to reset your password.</p>

    <?php if (!empty($errorMsg)): ?>
      <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= ROOT ?>/auth/forgetpass">
      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

      <div class="form-group">
        <div class="label">Email <span style="color:#e53e3e;">*</span></div>
        <input type="email" name="email" placeholder="Enter your email"
               value="<?= $emailValue ?>" required>
      </div>

      <button type="submit" class="btn">
        Send Reset Link
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
          <line x1="5" y1="12" x2="19" y2="12"/>
          <polyline points="12 5 19 12 12 19"/>
        </svg>
      </button>
    </form>

    <a href="<?= ROOT ?>/auth/signin" class="back-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="19" y1="12" x2="5" y2="12"/>
        <polyline points="12 19 5 12 12 5"/>
      </svg>
      Back to sign in
    </a>
  </div>

  <div class="footer-text">&copy; 2024 YouthNexus Pulse. Empowering the next generation.</div>

  <div class="bottom-bar">
    <div class="bottom-brand">YouthNexus Pulse</div>
    <div>
      &copy; 2024 YouthNexus Pulse.
      <a href="<?= ROOT ?>/privacy">Privacy Policy</a>
      <a href="<?= ROOT ?>/terms">Terms of Service</a>
      <a href="https://mail.google.com/mail/?view=cm&fs=1&to=governance@youthnexus.gov.lk" target="_blank">Contact Support</a>
    </div>
  </div>

  <?php if ($showPopup): ?>
  <div class="popup-overlay">
    <div class="popup-box">
      <div class="popup-icon" style="background:#002d72;">
        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
          <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
      </div>
      <h2>Email Sent!</h2>
      <p>A password reset link has been sent to your inbox. Please check your email and follow the instructions.</p>
      <a href="<?= ROOT ?>/auth/signin" class="popup-btn">Back to Sign In</a>
      <div class="popup-footer">
        Didn't receive the email? <a href="<?= ROOT ?>/auth/forgetpass">Resend Email</a>
      </div>
    </div>
  </div>
  <?php endif; ?>

</body>
</html>
