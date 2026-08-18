<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/common.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 50%, #d6dce8 100%);
            font-family: 'Inter', system-ui, sans-serif;
            padding: 40px 20px;
        }
        .success-card {
            background: #fff;
            border-radius: 20px;
            padding: 50px 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        }
        .success-icon {
            width: 80px; height: 80px; background: #059669; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px;
        }
        .success-icon svg { width: 36px; height: 36px; }
        .success-card h1 { color: #1a202c; font-size: 26px; margin-bottom: 12px; }
        .success-card p { color: #666; font-size: 15px; line-height: 1.7; margin-bottom: 30px; }
        .success-card .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            background: #1a237e; color: #fff; border: none; border-radius: 12px;
            padding: 16px 32px; font-size: 16px; font-weight: 700; cursor: pointer;
            text-decoration: none; margin-bottom: 12px;
        }
        .success-card .btn:hover { background: #0d1642; }
        .next-steps {
            margin-top: 30px; text-align: left; background: #f0f4f8;
            border-radius: 12px; padding: 20px;
        }
        .next-steps h3 { font-size: 14px; color: #1a237e; margin-bottom: 12px; }
        .next-steps ul { list-style: none; padding: 0; }
        .next-steps li {
            font-size: 13px; color: #555; padding: 8px 0;
            border-bottom: 1px solid #e0e4e8; display: flex; align-items: center; gap: 8px;
        }
        .next-steps li:last-child { border-bottom: none; }
        .next-steps li svg { width: 16px; height: 16px; fill: #059669; flex-shrink: 0; }
    </style>
</head>
<body>

    <div class="success-card">
        <div class="success-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>

        <h1>Application Submitted!</h1>
        <p>Your club registration application has been successfully submitted and is now pending review by the Divisional Secretariat. You will receive a confirmation email with your tracking ID shortly.</p>

        <a href="<?= ROOT ?>/home" class="btn">
            Go to Dashboard
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="5" y1="12" x2="19" y2="12"/>
                <polyline points="12 5 19 12 12 19"/>
            </svg>
        </a>

        <div class="next-steps">
            <h3>What happens next?</h3>
            <ul>
                <li>
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    Application is assigned a tracking ID
                </li>
                <li>
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    Divisional Coordinator reviews your application
                </li>
                <li>
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    Executive nominees' identities are verified
                </li>
                <li>
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    Upon approval, club and user accounts are created
                </li>
            </ul>
        </div>
    </div>

</body>
</html>
