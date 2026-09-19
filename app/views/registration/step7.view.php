<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/registration.css">
    <style>
        .page-title { margin-bottom: 10px; }
        .page-subtitle { line-height: 1.6; max-width: 600px; }
        .card { padding: 30px; }
        .card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
        .icon-circle {
            width: 44px; height: 44px; background-color: #eef2ff; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .icon-circle svg { width: 22px; height: 22px; fill: #1a237e; }
        .card-title { font-size: 18px; font-weight: bold; color: #1a1a1a; }
        .card-desc { color: #666; font-size: 13px; line-height: 1.5; margin-bottom: 25px; }
        .divider { border: none; border-top: 1px solid #e8e8e8; margin-bottom: 25px; }
        .checkbox-item {
            display: flex; align-items: flex-start; gap: 14px; margin-bottom: 20px;
            padding: 18px; background-color: #f9fafb; border-radius: 10px;
        }
        .checkbox-item:last-child { margin-bottom: 0; }
        .checkbox-item input[type="checkbox"] {
            width: 20px; height: 20px; margin-top: 2px; cursor: pointer;
            accent-color: #1a237e; flex-shrink: 0;
        }
        .checkbox-item label { cursor: pointer; }
        .checkbox-title { font-size: 15px; font-weight: bold; color: #1a1a1a; display: block; margin-bottom: 4px; }
        .checkbox-text { font-size: 13px; color: #666; line-height: 1.5; }
        .form-group { margin-bottom: 22px; }
        .form-group:last-child { margin-bottom: 0; }
        .form-label {
            display: block; font-size: 11px; font-weight: bold; color: #888;
            text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 10px;
        }
        .input-wrap { position: relative; }
        .input-wrap input {
            width: 100%; padding: 14px 45px 14px 16px; border: 1px solid #d0d0d0;
            border-radius: 10px; font-size: 15px; color: #333;
            background-color: #fff; font-family: Arial, sans-serif;
        }
        .input-wrap input::placeholder { color: #aaa; }
        .input-icon { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; fill: #aaa; }
        .hint-text { font-size: 13px; color: #888; font-style: italic; margin-top: 8px; line-height: 1.5; }
        .info-box { background-color: #e8eaf6; border-radius: 12px; padding: 22px; margin-top: 20px; }
        .info-box-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .info-box-header svg { width: 20px; height: 20px; fill: #1a237e; }
        .info-box-title { font-size: 16px; font-weight: bold; color: #1a237e; }
        .info-box p { font-size: 13px; color: #555; line-height: 1.7; }
        @media (max-width: 768px) { .main-row { flex-direction: column; } }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="logo-text">YouthNexus Registration</div>
            <div class="step-indicator">Step 7 of 7</div>
        </div>
        <div class="header-right">
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Guest') ?></div>
                <div class="user-role"><?= htmlspecialchars($_SESSION['user_role'] ?? 'User') ?></div>
            </div>
            <div class="user-avatar"><?= strtoupper(mb_substr($_SESSION['user_name'] ?? 'G', 0, 1)) ?></div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <h1 class="page-title">Final Declaration</h1>
        <p class="page-subtitle">Please review your information one last time before submitting. By completing this step, you formally request registration with the National Youth Service Council (NYSC).</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= ROOT ?>/registration/step7">
            <div class="main-row">

                <!-- Left: Legal Acknowledgement -->
                <div class="main-left">
                    <div class="card">
                        <div class="card-header">
                            <div class="icon-circle">
                                <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                            </div>
                            <div class="card-title">Legal Acknowledgement</div>
                        </div>
                        <div class="card-desc">Submission of fraudulent data is a punishable offense under the youth development act.</div>

                        <hr class="divider">

                        <div class="checkbox-item">
                            <input type="checkbox" id="accuracy" name="info_accuracy" value="1" required>
                            <label for="accuracy">
                                <span class="checkbox-title">Information Accuracy</span>
                                <span class="checkbox-text">I confirm all information provided is accurate and true to the best of my knowledge and belief.</span>
                            </label>
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="terms" name="terms_conditions" value="1" required>
                            <label for="terms">
                                <span class="checkbox-title">Terms &amp; Conditions</span>
                                <span class="checkbox-text">I agree to the NYSC terms and conditions regarding club governance, membership standards, and reporting requirements.</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Right: Official Endorsement -->
                <div class="main-right">
                    <div class="card">
                        <div class="card-header">
                            <div class="icon-circle">
                                <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                            </div>
                            <div class="card-title">Official Endorsement</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Digital Signature of President</label>
                            <div class="input-wrap">
                                <input type="text" name="digital_signature" placeholder="Type Full Name to Sign" value="<?= htmlspecialchars($sigValue) ?>" required>
                                <svg class="input-icon" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                            </div>
                            <div class="hint-text">Typing your name acts as a legally binding electronic signature.</div>
                        </div>
                    </div>

                    <!-- Ready to Submit -->
                    <div class="info-box">
                        <div class="info-box-header">
                            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                            <div class="info-box-title">Ready to submit?</div>
                        </div>
                        <p>Once submitted, your application will be assigned a tracking ID and forwarded to the Divisional Secretariat for verification. You can track status in your dashboard.</p>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="button-bar">
                <a href="<?= ROOT ?>/registration/step6" class="btn btn-back">&#8592; Back</a>
                <button type="submit" name="submit_app" class="btn btn-next">Submit Application &#8594;</button>
            </div>
        </form>
    </div>

</body>
</html>
