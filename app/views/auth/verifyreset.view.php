<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="<?= ROOT ?>/assets/css/common.css?v=20261001">
  <style>
    .brand-label { color: #002d72; font-size: 22px; font-weight: bold; margin-bottom: 30px; }
    h2 { color: #1a202c; font-size: 24px; margin: 0 0 12px; }
    .desc { color: #555; font-size: 14px; line-height: 1.6; margin-bottom: 28px; }
    .otp-input {
      width: 100%; box-sizing: border-box; padding: 14px 16px; font-size: 26px;
      letter-spacing: 10px; text-align: center; border: 1px solid #ccd3df;
      border-radius: 8px; outline: none;
    }
    .otp-input:focus { border-color: #002d72; box-shadow: 0 0 0 3px rgba(0,45,114,.1); }
    .back-link { display: inline-flex; align-items: center; gap: 6px; color: #666; font-size: 14px; text-decoration: none; margin-top: 22px; }
    .back-link:hover { color: #002d72; }
    .footer-text { margin-top: 28px; color: #888; font-size: 13px; }
    .bottom-bar {
      position: fixed; bottom: 0; left: 0; width: 100%; background: #eef1f7;
      padding: 14px 40px; display: flex; justify-content: space-between; align-items: center;
      font-size: 13px; color: #666;
    }
    .bottom-bar a { color: #555; text-decoration: none; margin-left: 20px; }
    .bottom-brand { color: #002d72; font-weight: bold; }
    .notice { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 12px 14px; border-radius: 8px; font-size: 14px; margin-bottom: 18px; }
    .dev-code { margin-top: 18px; padding: 12px 14px; background: #f0f4f8; border-radius: 8px; color: #555; font-size: 13px; }
    .resend { margin-top: 20px; font-size: 14px; color: #555; }
    .resend button { background: none; border: none; color: #002d72; font-weight: 600; cursor: pointer; padding: 0; text-decoration: underline; }
    .resend button:disabled { color: #999; cursor: not-allowed; text-decoration: none; }
  </style>
</head>
<body class="auth-card-body">

  <div class="card">
    <div class="brand-label">YouthNexus Pulse</div>

    <h2>Enter your code</h2>
    <p class="desc">We sent a 6-digit verification code to <strong><?= $email ?></strong>. It expires in 10 minutes.</p>

    <?php if (!empty($errorMsg)): ?>
      <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>
    <?php if (!empty($notice)): ?>
      <div class="notice"><?= $notice ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= ROOT ?>/auth/verifyreset?token=<?= rawurlencode($token) ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">
      <div class="form-group">
        <div class="label">Verification Code <span style="color:#e53e3e;">*</span></div>
        <input type="text" name="otp" class="otp-input" inputmode="numeric" autocomplete="one-time-code"
               pattern="[0-9]{6}" maxlength="6" placeholder="000000" required autofocus>
      </div>
      <button type="submit" class="btn">Verify Code</button>
    </form>

    <div class="resend">
      Didn't get it?
      <form method="POST" action="<?= ROOT ?>/auth/verifyreset?token=<?= rawurlencode($token) ?>" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">
        <button type="submit" name="resend" value="1" <?= empty($canResend) ? 'disabled' : '' ?>>Send a new code</button>
      </form>
    </div>

    <?php if (!empty($devCode)): ?>
      <div class="dev-code">Local test mode: your code is <strong style="color:#002d72;font-size:18px;letter-spacing:3px;"><?= htmlspecialchars($devCode, ENT_QUOTES) ?></strong></div>
    <?php endif; ?>

    <a href="<?= ROOT ?>/auth/forgetpass" class="back-link">Use a different email</a>
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

</body>
</html>
