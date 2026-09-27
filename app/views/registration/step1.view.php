<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/registration.css">
    <style>
        .card { padding: 35px; margin-bottom: 30px; }
        .form-row { display: flex; gap: 30px; margin-bottom: 25px; }
        .form-left { width: 320px; flex-shrink: 0; }
        .form-right { flex: 1; }
        .form-group { margin-bottom: 20px; }
        .form-group:last-child { margin-bottom: 0; }
        input[type="text"], input[type="date"], input[type="number"], select, textarea {
            width: 100%; padding: 12px 14px; border: 1px solid #d0d0d0;
            border-radius: 8px; font-size: 14px; color: #333;
            background-color: #fff; font-family: Arial, sans-serif;
        }
        textarea { resize: none; height: 120px; }
        .upload-box {
            border: 2px dashed #c5c5c5; border-radius: 12px; padding: 50px 20px;
            text-align: center; cursor: pointer; transition: border-color 0.2s;
        }
        .upload-box:hover { border-color: #1a237e; }
        .upload-icon {
            width: 48px; height: 48px; background-color: #e8eaf6; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;
        }
        .upload-icon svg { width: 20px; height: 20px; fill: #1a237e; }
        .upload-text { font-size: 14px; color: #1a237e; font-weight: 600; margin-bottom: 4px; }
        .upload-hint { font-size: 11px; color: #888; }
        .info-box {
            background-color: #e8eaf6; border-radius: 8px; padding: 16px;
            display: flex; gap: 12px; margin-top: 12px;
        }
        .info-icon { width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px; }
        .info-icon svg { width: 18px; height: 18px; fill: #1a237e; }
        .info-text { font-size: 13px; color: #555; line-height: 1.5; }
        .char-counter { text-align: right; font-size: 12px; color: #888; margin-top: 6px; }
        .two-col { display: flex; gap: 20px; }
        .two-col .form-group { flex: 1; }
        .divider { border: none; border-top: 1px solid #e0e0e0; margin: 25px 0; }
        .button-row {
            display: flex; justify-content: space-between; align-items: center; margin-top: 10px;
        }
        .bottom-section { display: flex; gap: 20px; }
        .bottom-card {
            flex: 1; border-radius: 12px; padding: 28px;
            position: relative; overflow: hidden;
        }
        .bottom-card.dark { background-color: #1a237e; color: #fff; }
        .bottom-card.light {
            background-color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex; align-items: flex-start; gap: 20px;
        }
        .bottom-card h3 { font-size: 18px; margin-bottom: 10px; position: relative; z-index: 1; }
        .bottom-card p {
            font-size: 14px; line-height: 1.6; color: rgba(255,255,255,0.85);
            position: relative; z-index: 1;
        }
        .bottom-card.light p { color: #666; margin-bottom: 12px; }
        .bg-icon { position: absolute; right: 20px; bottom: -10px; opacity: 0.1; width: 100px; height: 100px; }
        .bg-icon svg { width: 100%; height: 100%; fill: #fff; }
        .support-icon {
            width: 48px; height: 48px; background-color: #f5f6fa; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .support-icon svg { width: 24px; height: 24px; fill: #1a237e; }
        .contact-link { color: #1a237e; font-size: 13px; font-weight: bold; text-decoration: none; }
        .contact-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="logo-text">YouthNexus Registration</div>
            <div class="step-indicator">Step 1 of 7</div>
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
        <h1 class="page-title">Basic Information</h1>
        <p class="page-subtitle">Establish the core identity of your club within the YouthNexus ecosystem.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="card">
            <form action="<?= ROOT ?>/registration/step1" method="POST" enctype="multipart/form-data">

                <!-- First Row -->
                <div class="form-row">
                    <div class="form-left">
                        <label>Club Logo</label>
                        <div class="upload-box" onclick="document.getElementById('clubLogo').click()">
                            <div class="upload-icon">
                                <svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>
                            </div>
                            <div class="upload-text">Click to upload</div>
                            <div class="upload-hint">PNG, JPG UP TO 5MB</div>
                            <input type="file" id="clubLogo" name="club_logo" accept="image/png, image/jpeg" style="display: none;">
                        </div>
                    </div>
                    <div class="form-right">
                        <div class="form-group">
                            <label for="clubName">Club Name</label>
                            <input type="text" id="clubName" name="club_name" placeholder="e.g. Skyline Youth Association" value="<?= htmlspecialchars($club_name) ?>">
                        </div>
                        <div class="two-col">
                            <div class="form-group">
                                <label for="category">Club Category</label>
                                <select id="category" name="category">
                                    <option value="" disabled <?= empty($category) ? 'selected' : '' ?>>Select Category</option>
                                    <option value="sports" <?= $category === 'sports' ? 'selected' : '' ?>>Sports &amp; Recreation</option>
                                    <option value="arts" <?= $category === 'arts' ? 'selected' : '' ?>>Arts &amp; Culture</option>
                                    <option value="education" <?= $category === 'education' ? 'selected' : '' ?>>Education &amp; Literacy</option>
                                    <option value="environment" <?= $category === 'environment' ? 'selected' : '' ?>>Environment &amp; Sustainability</option>
                                    <option value="health" <?= $category === 'health' ? 'selected' : '' ?>>Health &amp; Wellness</option>
                                    <option value="technology" <?= $category === 'technology' ? 'selected' : '' ?>>Technology &amp; Innovation</option>
                                    <option value="community" <?= $category === 'community' ? 'selected' : '' ?>>Community Service</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="estDate">Date of Establishment</label>
                                <input type="text" id="estDate" name="date_establishment" placeholder="mm/dd/yyyy" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" value="<?= htmlspecialchars($date_establishment) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="divider">

                <!-- Second Row -->
                <div class="form-row">
                    <div class="form-left">
                        <div class="form-group">
                            <label for="noMembers">No. of Members</label>
                            <input type="number" id="noMembers" name="no_of_members" placeholder="e.g. 25" min="0" value="<?= htmlspecialchars($no_of_members) ?>">
                        </div>
                        <div class="info-box">
                            <div class="info-icon">
                                <svg viewBox="0 0 24 24"><path d="M9 21c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7zm2.85 11.1l-.85.6V16h-4v-2.3l-.85-.6A4.997 4.997 0 0 1 7 9c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.63-.8 3.16-2.15 4.1z"/></svg>
                            </div>
                            <div class="info-text">The category helps us match your club with relevant grants and community programs.</div>
                        </div>
                    </div>
                    <div class="form-right">
                        <div class="form-group">
                            <label for="description">Club Description / Mission Statement</label>
                            <textarea id="description" name="description" maxlength="500" placeholder="Briefly describe the purpose of your club..." oninput="document.getElementById('charCount').textContent = this.value.length + ' / 500 characters'"><?= htmlspecialchars($description) ?></textarea>
                            <div class="char-counter" id="charCount"><?= strlen($description) ?> / 500 characters</div>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <hr class="divider">
                <div class="button-row">
                    <a href="<?= ROOT ?>/registration/cancel" class="btn btn-cancel">&#8592; Cancel</a>
                    <button type="submit" class="btn btn-next">Next: Location Details &#8594;</button>
                </div>
            </form>
        </div>

        <!-- Bottom Cards -->
        <div class="bottom-section">
            <div class="bottom-card dark">
                <h3>Why registration matters?</h3>
                <p>Registered clubs gain access to YouthNexus equipment lockers, professional coaching workshops, and annual community funding tiers.</p>
                <div class="bg-icon">
                    <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
                </div>
            </div>
            <div class="bottom-card light">
                <div class="support-icon">
                    <svg viewBox="0 0 24 24"><path d="M11.5 2C6.81 2 3 5.81 3 10.5S6.81 19 11.5 19h.5v3c4.86-2.01 7-5.96 7-9.8 0-4.69-3.81-8.5-8.5-8.5zm0 15c-2.49 0-4.5-2.01-4.5-4.5S9.01 8 11.5 8 16 10.01 16 12.5 13.99 17 11.5 17z"/></svg>
                </div>
                <div>
                    <h3 style="color: #1a237e; margin-bottom: 8px;">Need Help?</h3>
                    <p>Our support team is available 24/7 to help you with the registration process.</p>
                    <a href="#" class="contact-link">CONTACT SUPPORT</a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
