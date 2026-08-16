<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="<?= ROOT ?>/assets/css/common.css">
  <style>
    .top-icon {
      width: 52px; height: 52px; background: #002d72; border-radius: 12px;
      display: flex; align-items: center; justify-content: center; margin-bottom: 18px;
      box-shadow: 0 4px 12px rgba(0,45,114,.25);
    }
    h1 { color: #1a202c; font-size: 26px; margin: 0 0 8px; }
    .subtitle { color: #555; font-size: 14px; margin-bottom: 28px; }

    .input-box .icon-right {
      width: 46px; height: 46px; display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: #888; background: none; border: none; padding: 0; flex-shrink: 0;
    }
    .input-box .icon-right:hover { color: #555; }

    .requirements { background: #f0f4f8; border-radius: 10px; padding: 18px; margin-bottom: 20px; }
    .req-title { font-size: 11px; font-weight: bold; color: #666; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 12px; }
    .req-item { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; font-size: 14px; color: #888; transition: color .2s; }
    .req-item:last-child { margin-bottom: 0; }
    .req-item.met { color: #059669; }
    .req-check { width: 18px; height: 18px; border-radius: 50%; border: 2px solid #bbb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .2s; }
    .req-item.met .req-check { background: #059669; border-color: #059669; }
    .req-check svg { width: 10px; height: 10px; display: none; }
    .req-item.met .req-check svg { display: block; }

    .back-link { display: inline-flex; align-items: center; gap: 6px; color: #002d72; font-size: 14px; text-decoration: none; margin-top: 25px; font-weight: 500; }
    .back-link:hover { text-decoration: underline; }

    .bottom-bar {
      position: fixed; bottom: 0; left: 0; width: 100%;
      background: #eef1f7; padding: 14px 40px;
      display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #666;
    }
    .bottom-bar a { color: #555; text-decoration: none; margin-left: 20px; }
    .bottom-bar a:hover { color: #002d72; }
    .bottom-brand { color: #002d72; font-weight: bold; }

    .popup-icon-wrap { width: 64px; height: 64px; background: #d1fae5; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; }
    .popup-icon-inner { width: 38px; height: 38px; background: #059669; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
  </style>
</head>
<body class="auth-card-body">

  <div class="top-icon">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="white">
      <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
      <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
    </svg>
  </div>

  <h1>Reset Password</h1>
  <p class="subtitle">Create a new password for your YouthNexus account</p>

  <div class="card">
    <?php if (!empty($errorMsg)): ?>
      <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="resetForm">
      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

      <div class="input-group">
        <label>New Password</label>
        <div class="input-box">
          <div class="icon-left">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </div>
          <input type="password" name="password" id="password" placeholder="Enter new password" required>
          <button type="button" class="icon-right" onclick="toggleEye('password',this)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>

      <div class="input-group">
        <label>Confirm New Password</label>
        <div class="input-box">
          <div class="icon-left">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </div>
          <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required>
          <button type="button" class="icon-right" onclick="toggleEye('confirm_password',this)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>

      <div class="requirements">
        <div class="req-title">Password Requirements</div>
        <div class="req-item" id="req1">
          <div class="req-check"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
          <span>At least 8 characters</span>
        </div>
        <div class="req-item" id="req2">
          <div class="req-check"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
          <span>At least 1 number</span>
        </div>
        <div class="req-item" id="req3">
          <div class="req-check"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
          <span>Passwords must match</span>
        </div>
      </div>

      <button type="submit" class="btn">
        Update Password
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
          <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
        </svg>
      </button>
    </form>
  </div>

  <a href="<?= ROOT ?>/auth/signin" class="back-link">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
    </svg>
    Back to Sign In
  </a>

  <div class="bottom-bar">
    <div class="bottom-brand">YouthNexus Pulse</div>
    <div>&copy; 2024 YouthNexus Pulse. <a href="<?= ROOT ?>/privacy">Privacy Policy</a> <a href="<?= ROOT ?>/terms">Terms of Service</a> <a href="https://mail.google.com/mail/?view=cm&fs=1&to=governance@youthnexus.gov.lk" target="_blank">Contact Support</a></div>
  </div>

  <?php if ($showSuccess): ?>
  <div class="popup-overlay">
    <div class="popup-box">
      <div class="popup-icon-wrap">
        <div class="popup-icon-inner">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </div>
      </div>
      <h2>Password Updated!</h2>
      <p>Your password has been changed successfully. Use your new password to sign in.</p>
      <a href="<?= ROOT ?>/auth/signin" class="popup-btn">
        Go to Sign In
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="margin-left:6px;">
          <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
        </svg>
      </a>
    </div>
  </div>
  <?php endif; ?>

  <script>
    const pass    = document.getElementById('password');
    const confirm = document.getElementById('confirm_password');

    function checkReqs() {
      const p = pass.value, c = confirm.value;
      document.getElementById('req1').classList.toggle('met', p.length >= 8);
      document.getElementById('req2').classList.toggle('met', /[0-9]/.test(p));
      document.getElementById('req3').classList.toggle('met', p === c && p !== '');
    }
    pass.addEventListener('input', checkReqs);
    confirm.addEventListener('input', checkReqs);

    function toggleEye(id, btn) {
      const input = document.getElementById(id);
      const isPass = input.type === 'password';
      input.type = isPass ? 'text' : 'password';
      btn.innerHTML = isPass
        ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
        : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    }

    document.getElementById('resetForm').addEventListener('submit', e => {
      const p = pass.value, c = confirm.value;
      if (p.length < 8 || !/[0-9]/.test(p) || p !== c) {
        e.preventDefault();
        alert('Please meet all password requirements before submitting.');
      }
    });
  </script>

</body>
</html>
