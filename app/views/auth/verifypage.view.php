<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="<?= ROOT ?>/assets/css/common.css">
  <style>
    .logo {
      display: flex; align-items: center; gap: 10px;
      margin-bottom: 35px; font-size: 20px; font-weight: bold; color: #002d72;
    }
    .logo-box {
      width: 30px; height: 30px; background: #002d72; border-radius: 5px;
      display: flex; align-items: center; justify-content: center;
    }
    .icon-circle {
      width: 55px; height: 55px; background: #eef3ff; border-radius: 50%;
      margin: 0 auto 18px; display: flex; align-items: center; justify-content: center;
    }
    h2 { margin: 0 0 10px; color: #111; font-size: 20px; }
    .subtext { color: #666; font-size: 13px; line-height: 1.5; margin-bottom: 25px; }

    .inputs { display: flex; justify-content: center; gap: 8px; margin-bottom: 18px; }
    .inputs input {
      width: 45px; height: 45px; border: 1px solid #ccc; border-radius: 6px;
      text-align: center; font-size: 18px; font-weight: bold; color: #222;
      outline: none;
    }
    .inputs input:focus { border-color: #002d72; box-shadow: 0 0 0 2px rgba(0,45,114,.12); }

    .timer { font-size: 12px; color: #888; margin-bottom: 6px; }
    .resend { font-size: 12px; color: #bbb; margin-bottom: 22px; }
    .resend a { color: #bbb; text-decoration: none; cursor: not-allowed; pointer-events: none; }
    .resend a.active { color: #002d72; cursor: pointer; pointer-events: auto; font-weight: bold; }

    .help { font-size: 12px; color: #666; border-top: 1px solid #eee; padding-top: 18px; }
    .help a { color: #002d72; text-decoration: none; }
  </style>
</head>
<body class="auth-card-body">

  <div class="logo">
    <div class="logo-box">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="white">
        <path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z"/>
      </svg>
    </div>
    YouthNexus Pulse
  </div>

  <div class="card">
    <div class="icon-circle">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#002d72" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
        <polyline points="22,6 12,13 2,6"/>
      </svg>
    </div>

    <h2>Verify your email</h2>
    <p class="subtext">
      We've sent a 6-digit code to
      <?php if (!empty($email)): ?>
        <strong><?= $email ?></strong>
      <?php else: ?>
        your email address
      <?php endif; ?>.
      Please enter it below.
    </p>

    <?php if (!empty($msg)): ?>
      <div class="alert alert-success" style="margin-bottom:16px;"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= ROOT ?>/auth/verify">
      <div class="inputs">
        <?php for ($i = 1; $i <= 6; $i++): ?>
          <input type="text" name="d<?= $i ?>" maxlength="1" required <?= $i === 1 ? 'autofocus' : '' ?>>
        <?php endfor; ?>
      </div>

      <div class="timer">Resend code in <span id="time">00:59</span></div>
      <div class="resend">
        <a href="#" id="resendBtn">Resend Code</a>
      </div>

      <button type="submit" class="btn">Verify &amp; Continue &rarr;</button>
    </form>

    <div class="help">Having trouble? <a href="#">Contact Support</a></div>
  </div>

  <script>
    let sec = 59;
    const timeEl   = document.getElementById('time');
    const resendEl = document.getElementById('resendBtn');

    const countdown = setInterval(() => {
      sec--;
      const m = Math.floor(sec / 60), s = sec % 60;
      timeEl.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
      if (sec <= 0) {
        clearInterval(countdown);
        timeEl.parentElement.style.display = 'none';
        resendEl.href = '<?= ROOT ?>/auth/verify?resend=1';
        resendEl.classList.add('active');
      }
    }, 1000);

    const boxes = document.querySelectorAll('.inputs input');
    boxes.forEach((box, i) => {
      box.addEventListener('input', () => {
        if (box.value.length === 1 && i < boxes.length - 1) boxes[i + 1].focus();
      });
      box.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && box.value === '' && i > 0) boxes[i - 1].focus();
      });
    });
  </script>

  <?php if ($success) { include '../app/views/auth/verifysuccess.view.php'; } ?>
  <?php if ($error)   { include '../app/views/auth/verifyerror.view.php';   } ?>

</body>
</html>
