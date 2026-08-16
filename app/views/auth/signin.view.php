<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="<?= ROOT ?>/assets/css/common.css" />
  <link rel="stylesheet" href="<?= ROOT ?>/assets/css/signin.css" />
</head>
<body>
  <div class="page">

    <!-- LEFT PANEL -->
    <aside class="left">
      <header class="brand">
        <span class="brand-icon">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="#1e40af" aria-hidden="true">
            <path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z"/>
          </svg>
        </span>
        <div class="brand-text">
          <span class="brand-name">YouthNexus</span>
        </div>
      </header>

      <span class="badge">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <circle cx="12" cy="12" r="9"/>
          <path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>
        </svg>
        OFFICIAL NYSC PORTAL
      </span>

      <h1 class="headline">
        Empowering Sri Lanka's Youth,
        <span class="accent">Connecting Communities.</span>
      </h1>

      <p class="lead">
        A revolutionary digital ecosystem designed to streamline youth organisation
        management, foster collaboration, and track nationwide impact in real-time.
      </p>

      <ul class="stats">
        <li><span class="stat-num">2,500+</span><span class="stat-label">ACTIVE CLUBS</span></li>
        <li><span class="stat-num">150,000+</span><span class="stat-label">MEMBERS</span></li>
        <li><span class="stat-num">10,000+</span><span class="stat-label">EVENTS</span></li>
      </ul>

      <figure class="event-card">
        <img src="<?= ROOT ?>/assets/images/conference.png" alt="Youth conference" />
        <figcaption>National Youth Conference 2025 · Colombo</figcaption>
      </figure>
    </aside>

    <!-- RIGHT PANEL -->
    <main class="right">
      <div class="form-wrap">
        <h2 class="title">Welcome back</h2>
        <p class="subtitle">Sign in to your YouthNexus Pulse account to continue.</p>

        <?php if (!empty($error)): ?>
          <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
        <?php elseif (!empty($success)): ?>
          <p class="alert alert-success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form method="POST" action="<?= ROOT ?>/auth/signin">
          <label class="field-label" for="email">Email</label>
          <div class="input">
            <svg class="input-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <circle cx="12" cy="8" r="4"/>
              <path d="M4 20c0-4 4-6 8-6s8 2 8 6"/>
            </svg>
            <input id="email" name="email" type="email" placeholder="Enter your email" value="<?= $email ?>"/>
          </div>

          <div class="label-row">
            <label class="field-label" for="password">Password</label>
            <a href="<?= ROOT ?>/auth/forgetpass" class="forgot">Forgot password?</a>
          </div>
          <div class="input">
            <svg class="input-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <rect x="4" y="10" width="16" height="10" rx="2"/>
              <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
            </svg>
            <input id="password" name="password" type="password" placeholder="Enter your password"/>
            <button type="button" class="toggle-pass" id="togglePass" aria-label="Show password">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/>
                <circle cx="12" cy="12" r="3"/>
                <line x1="3" y1="3" x2="21" y2="21"/>
              </svg>
            </button>
          </div>

          <br>
          <button type="submit" class="signin-btn">
            Sign In
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="5" y1="12" x2="19" y2="12"/>
              <polyline points="12 5 19 12 12 19"/>
            </svg>
          </button>

          <div class="notice">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2 4 5v6c0 5 3.5 8 8 11 4.5-3 8-6 8-11V5l-8-3z"/>
            </svg>
            <span>You may be asked to authenticate via email or security code for added protection.</span>
          </div>

          <div class="divider"><span>or</span></div>
          <p class="signup">New to YouthNexus Pulse? <a href="<?= ROOT ?>/auth/signup">Sign Up</a></p>

          <nav class="footer-links">
            <a href="<?= ROOT ?>/privacy">PRIVACY POLICY</a>
            <a href="<?= ROOT ?>/terms">TERMS OF SERVICE</a>
            <a href="https://mail.google.com/mail/?view=cm&fs=1&to=governance@youthnexus.gov.lk" target="_blank">HELP</a>
          </nav>
        </form>
      </div>
    </main>
  </div>

  <script>
    const toggle = document.getElementById('togglePass');
    const pass   = document.getElementById('password');
    toggle.addEventListener('click', () => {
      const isText = pass.type === 'text';
      pass.type = isText ? 'password' : 'text';
      toggle.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
    });
  </script>
</body>
</html>
