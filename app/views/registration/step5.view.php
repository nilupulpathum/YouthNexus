<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/registration.css">
    <style>
        .page-title { margin-bottom: 10px; }
        .page-subtitle { line-height: 1.6; max-width: 700px; }
        .card { padding: 30px; }
        .card-title {
            font-size: 18px; font-weight: bold; color: #1a1a1a;
            margin-bottom: 25px; display: flex; align-items: center; gap: 10px;
        }
        .card-title svg { width: 22px; height: 22px; fill: #1a237e; }
        .form-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .form-group { flex: 1; margin-bottom: 20px; }
        .form-group.half { flex: 1; }
        input[type="text"], select {
            width: 100%; padding: 14px 16px; border: 1px solid #d0d0d0;
            border-radius: 10px; font-size: 15px; color: #333;
            background-color: #fff; font-family: Arial, sans-serif;
        }
        select { background-position: right 16px center; }
        .hint-text { font-size: 12px; color: #888; margin-top: 6px; font-style: italic; }
        .input-icon-wrap { position: relative; }
        .input-icon-wrap svg {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            width: 18px; height: 18px; fill: #888;
        }
        .input-icon-wrap input { padding-left: 42px; }
        .checkbox-group {
            display: flex; align-items: flex-start; gap: 12px;
            margin-top: 25px; padding-top: 20px; border-top: 1px solid #f0f0f0;
        }
        .checkbox-group input[type="checkbox"] {
            width: 18px; height: 18px; margin-top: 2px; cursor: pointer; accent-color: #1a237e;
        }
        .checkbox-group label { font-size: 14px; color: #555; font-weight: normal; line-height: 1.5; margin: 0; cursor: pointer; }
        .info-card {
            background-color: #eef2ff; border-radius: 12px; padding: 30px;
            text-align: center; margin-bottom: 20px;
        }
        .info-icon-circle {
            width: 50px; height: 50px; background-color: #fff; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px;
        }
        .info-icon-circle svg { width: 24px; height: 24px; fill: #1a237e; }
        .info-card h3 { font-size: 18px; color: #1a237e; margin-bottom: 10px; }
        .info-card p { font-size: 13px; color: #555; line-height: 1.6; }
        .docs-card { background-color: #eef2ff; border-radius: 12px; padding: 25px; }
        .docs-card h4 { font-size: 12px; color: #1a237e; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 18px; }
        .doc-item { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; font-size: 14px; color: #333; }
        .doc-item svg { width: 18px; height: 18px; fill: #a0522d; flex-shrink: 0; }
        .chat-btn {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            background-color: #fff; border: 1px solid #d0d0d0; border-radius: 8px;
            padding: 12px; width: 100%; margin-top: 15px; font-size: 14px;
            color: #333; cursor: pointer; text-decoration: none;
        }
        .chat-btn svg { width: 18px; height: 18px; fill: #1a237e; }
        @media (max-width: 768px) { .9: { flex-direction: column; } .form-row { flex-direction: column; gap: 0; } }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="logo-text">YouthNexus Registration</div>
            <div class="step-indicator">Step 5 of 7</div>
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
        <h1 class="page-title">Verification & Disbursements</h1>
        <p class="page-subtitle">Enter the bank account details for the club. This account will be used for all official grant disbursements and institutional fee settlements.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="main-row">
            <!-- Left Side -->
            <div class="main-left">
                <div class="card">
                    <div class="card-title">
                        <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                        Official Bank Information
                    </div>
                    <form id="bankForm" method="POST" action="<?= ROOT ?>/registration/step5">
                        <div class="form-row">
                            <div class="form-group half">
                                <label for="bankName">Bank Name</label>
                                <select id="bankName" name="bank_name" required>
                                    <option value="" disabled <?= empty($bank_name) ? 'selected' : '' ?>>Select your bank</option>
                                    <option value="boc" <?= $bank_name === 'boc' ? 'selected' : '' ?>>Bank of Ceylon</option>
                                    <option value="peoples" <?= $bank_name === 'peoples' ? 'selected' : '' ?>>Peoples Bank</option>
                                    <option value="commercial" <?= $bank_name === 'commercial' ? 'selected' : '' ?>>Commercial Bank</option>
                                    <option value="hnb" <?= $bank_name === 'hnb' ? 'selected' : '' ?>>Hatton National Bank</option>
                                    <option value="sampath" <?= $bank_name === 'sampath' ? 'selected' : '' ?>>Sampath Bank</option>
                                    <option value="seylan" <?= $bank_name === 'seylan' ? 'selected' : '' ?>>Seylan Bank</option>
                                </select>
                            </div>
                            <div class="form-group half">
                                <label for="branch">Branch</label>
                                <input type="text" id="branch" name="bank_branch" placeholder="e.g. Downtown Metro" value="<?= htmlspecialchars($bank_branch) ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="accountHolder">Account Holder Name</label>
                            <input type="text" id="accountHolder" name="account_holder" placeholder="As per bank records" value="<?= htmlspecialchars($account_holder) ?>" required>
                            <div class="hint-text">Must match the registered Club Name or the Treasurer's authorized account.</div>
                        </div>
                        <div class="form-group">
                            <label for="accountNumber">Account Number</label>
                            <div class="input-icon-wrap">
                                <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                                <input type="text" id="accountNumber" name="account_number" placeholder=".... .... .... ...." value="<?= htmlspecialchars($account_number) ?>" required>
                            </div>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="bankConfirm" name="bank_confirmed" value="1" <?= $bank_confirmed ? 'checked' : '' ?> required>
                            <label for="bankConfirm">I confirm that this bank account is active and authorized for receiving institutional funds under the YouthNexus guidelines.</label>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Side -->
            <div class="main-right">
                <div class="info-card">
                    <div class="info-icon-circle">
                        <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                    </div>
                    <h3>Secure Processing</h3>
                    <p>Your financial data is encrypted using AES-256 standard and is only used for disbursement purposes.</p>
                </div>
                <div class="docs-card">
                    <h4>Required Documents</h4>
                    <div class="doc-item">
                        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        Cancelled Cheque
                    </div>
                    <div class="doc-item">
                        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        Bank Statement (Last 3 mo)
                    </div>
                    <div class="doc-item">
                        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        Auth Signatory List
                    </div>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="button-bar">
            <a href="<?= ROOT ?>/registration/step4" class="btn btn-back">&#8592; Back</a>
            <button type="submit" form="bankForm" name="save_bank" class="btn btn-next">Next: Support Documents &#8594;</button>
        </div>
    </div>

</body>
</html>
