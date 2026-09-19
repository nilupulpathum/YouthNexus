<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="<?= ROOT ?>/assets/css/common.css">
  <style>
    body { display: flex; min-height: 100vh; }

    .form-section {
      width: 50%; background: #fff;
      display: flex; align-items: center; justify-content: center; padding: 40px;
    }
    .form-container { width: 100%; max-width: 450px; }
    .form-container h1 { font-size: 32px; color: #1a1a1a; margin-bottom: 10px; }
    .form-container > p { color: #555; margin-bottom: 30px; font-size: 15px; }

    .form-row { display: flex; gap: 15px; }
    .form-row .form-group { width: 50%; }

    .checkbox-group { display: flex; align-items: center; gap: 10px; margin-bottom: 25px; }
    .checkbox-group input { width: 18px; height: 18px; cursor: pointer; }
    .checkbox-group label { font-size: 14px; color: #444; }
    .checkbox-group a { color: #1e3a8a; text-decoration: none; }

    .divider { border: none; border-top: 1px solid #e0e0e0; margin: 28px 0; }
    .signin-text { text-align: center; font-size: 14px; color: #555; }
    .signin-text a { color: #1e3a8a; text-decoration: none; font-weight: 700; }

    /* Right brand panel */
    .info-section {
      width: 50%; background: #1e3a8a; color: #fff;
      padding: 60px; display: flex; flex-direction: column;
      justify-content: center; position: relative; overflow: hidden;
    }
    .logo { display: flex; align-items: center; gap: 12px; margin-bottom: 70px; }
    .logo-icon { width: 40px; height: 40px; background: #fff; border-radius: 8px;
      display: flex; align-items: center; justify-content: center; }
    .logo-icon span { color: #1e3a8a; font-weight: bold; font-size: 18px; }
    .logo-text { font-size: 22px; font-weight: 600; }

    .headline { font-size: 40px; line-height: 1.2; margin-bottom: 44px; font-weight: 600; }
    .stats { display: flex; gap: 60px; margin-bottom: 44px; }
    .stat-item h2 { font-size: 34px; margin-bottom: 4px; }
    .stat-item p { font-size: 13px; opacity: .8; letter-spacing: 1px; }

    .testimonial { background: rgba(255,255,255,.1); padding: 28px; border-radius: 16px; backdrop-filter: blur(10px); }
    .quote-mark { font-size: 38px; line-height: 1; margin-bottom: 10px; opacity: .6; }
    .testimonial p { font-size: 15px; line-height: 1.7; margin-bottom: 18px; opacity: .95; }
    .author { display: flex; align-items: center; gap: 12px; }
    .author-img { width: 40px; height: 40px; border-radius: 50%; background: #cbd5e1;
      display: flex; align-items: center; justify-content: center;
      color: #1e3a8a; font-weight: bold; font-size: 14px; }
    .author-name { font-weight: 600; font-size: 14px; }
    .author-role { font-size: 12px; opacity: .7; letter-spacing: .5px; }

    .help-link { position: absolute; bottom: 28px; left: 60px; color: #fff;
      text-decoration: none; font-size: 14px; opacity: .8; }
    .help-link:hover { opacity: 1; }

    .circle { position: absolute; border-radius: 50%; border: 2px solid rgba(255,255,255,.1); }
    .circle-1 { width: 300px; height: 300px; top: -80px; right: -80px; }
    .circle-2 { width: 200px; height: 200px; top: 20px; right: 20px; }

    @media (max-width: 900px) {
      body { flex-direction: column; }
      .form-section, .info-section { width: 100%; }
      .info-section { display: none; }
    }
  </style>
</head>
<body>

  <!-- Left: Form -->
  <div class="form-section">
    <div class="form-container">
      <h1>Create your account</h1>
      <p>Join the YouthNexus Pulse community.</p>

      <?php if (!empty($message)): ?>
        <div class="<?= $message['type'] === 'error' ? 'alert alert-error' : 'alert alert-success' ?>">
          <?= htmlspecialchars($message['text']) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= ROOT ?>/auth/signup">
        <div class="form-group">
          <label for="fullname">Full Name</label>
          <input type="text" id="fullname" name="fullname" placeholder="John Doe"
                 value="<?= $fullname ?>" required>
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="name@organisation.org"
                 value="<?= $email ?>" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="********" required>
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="********" required>
          </div>
        </div>

        <div class="checkbox-group">
          <input type="checkbox" id="agree" name="agree">
          <label for="agree">I agree to the <a href="<?= ROOT ?>/terms" target="_blank">Terms of Service</a> and <a href="<?= ROOT ?>/privacy" target="_blank">Privacy Policy</a>.</label>
        </div>

        <button type="submit" class="btn">Verify email &rarr;</button>
      </form>

      <hr class="divider">
      <p class="signin-text">Already have an account? <a href="<?= ROOT ?>/auth/signin">Sign In</a></p>
    </div>
  </div>

  <!-- Right: Brand -->
  <div class="info-section">
    <div class="circle circle-1"></div>
    <div class="circle circle-2"></div>
    <div class="logo">
      <div class="logo-icon"><span>YN</span></div>
      <div class="logo-text">YouthNexus Pulse</div>
    </div>
    <div class="headline">Empowering the<br>Next Generation</div>
    <div class="stats">
      <div class="stat-item"><h2>45k+</h2><p>ACTIVE MEMBERS</p></div>
      <div class="stat-item"><h2>120</h2><p>NATIONAL INITIATIVES</p></div>
    </div>
    <div class="testimonial">
      <div class="quote-mark">"</div>
      <p>YouthNexus Pulse has revolutionised how we coordinate regional leadership. The clarity and structure provided by this portal have allowed us to double our impact in just six months.</p>
      <div class="author">
        <div class="author-img">SJ</div>
        <div>
          <div class="author-name">Sarah Jenkins</div>
          <div class="author-role">REGIONAL DIRECTOR</div>
        </div>
      </div>
    </div>
    <a href="https://mail.google.com/mail/?view=cm&fs=1&to=governance@youthnexus.gov.lk" target="_blank" class="help-link">Help Center</a>
  </div>

</body>
</html>
