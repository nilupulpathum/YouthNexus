<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/registration.css">
    <style>
        .card { padding: 35px; margin-bottom: 30px; }
        .form-row { display: flex; gap: 30px; }
        .form-left { width: 320px; flex-shrink: 0; }
        .form-right { flex: 1; }
        .form-group { margin-bottom: 20px; }
        .form-group:last-child { margin-bottom: 0; }
        input[type="text"], select, textarea {
            width: 100%; padding: 12px 14px; border: 1px solid #d0d0d0;
            border-radius: 8px; font-size: 14px; color: #333;
            background-color: #fff; font-family: Arial, sans-serif;
        }
        textarea { resize: none; height: 90px; }
        .two-col { display: flex; gap: 20px; }
        .two-col .form-group { flex: 1; }
        .info-box {
            background-color: #e8eaf6; border-radius: 8px; padding: 16px;
            display: flex; gap: 12px; margin-top: 12px;
        }
        .info-icon { width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px; }
        .info-icon svg { width: 18px; height: 18px; fill: #1a237e; }
        .info-text { font-size: 13px; color: #555; line-height: 1.5; }
        .divider { border: none; border-top: 1px solid #e0e0e0; margin: 25px 0; }
        .button-row { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="logo-text">YouthNexus Registration</div>
            <div class="step-indicator">Step 2 of 7</div>
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
        <h1 class="page-title">Location Details</h1>
        <p class="page-subtitle">Provide the physical address where your club operates.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="card">
            <form action="<?= ROOT ?>/registration/step2" method="POST">

                <div class="form-row">
                    <div class="form-left">
                        <div class="form-group">
                            <label for="division">Division / Secretariat</label>
                            <select id="division" name="division_id" required>
                                <option value="" disabled <?= empty($division_id) ? 'selected' : '' ?>>Select Division</option>
                                <?php if (!empty($divisions)): ?>
                                    <?php foreach ($divisions as $div): ?>
                                        <option value="<?= $div->division_id ?>" <?= $division_id == $div->division_id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($div->division_name) ?> (<?= htmlspecialchars($div->zonal_name ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="locationType">Location Type</label>
                            <select id="locationType" name="location_type">
                                <option value="" disabled <?= empty($location_type) ? 'selected' : '' ?>>Select Location Type</option>
                                <option value="community_center" <?= $location_type === 'community_center' ? 'selected' : '' ?>>Community Center</option>
                                <option value="school" <?= $location_type === 'school' ? 'selected' : '' ?>>School / University</option>
                                <option value="private_property" <?= $location_type === 'private_property' ? 'selected' : '' ?>>Private Property</option>
                                <option value="public_park" <?= $location_type === 'public_park' ? 'selected' : '' ?>>Public Park</option>
                                <option value="religious" <?= $location_type === 'religious' ? 'selected' : '' ?>>Religious Institution</option>
                                <option value="commercial" <?= $location_type === 'commercial' ? 'selected' : '' ?>>Commercial Space</option>
                                <option value="other" <?= $location_type === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="info-box">
                            <div class="info-icon">
                                <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                            </div>
                            <div class="info-text">The division and location type help us allocate nearby resources and verify venue eligibility.</div>
                        </div>
                    </div>
                    <div class="form-right">
                        <div class="form-group">
                            <label for="streetAddress">Street Address</label>
                            <textarea id="streetAddress" name="street_address" placeholder="Enter your club's full street address..."><?= htmlspecialchars($street_address) ?></textarea>
                        </div>
                        <div class="two-col">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" placeholder="e.g. Colombo" value="<?= htmlspecialchars($city) ?>">
                            </div>
                            <div class="form-group">
                                <label for="state">State / Province</label>
                                <input type="text" id="state" name="state_province" placeholder="e.g. Western Province" value="<?= htmlspecialchars($state_province) ?>">
                            </div>
                        </div>
                        <div class="two-col">
                            <div class="form-group">
                                <label for="postalCode">Postal Code</label>
                                <input type="text" id="postalCode" name="postal_code" placeholder="e.g. 10100" value="<?= htmlspecialchars($postal_code) ?>">
                            </div>
                            <div class="form-group">
                                <label for="country">Country</label>
                                <select id="country" name="country">
                                    <option value="lk" <?= $country === 'lk' ? 'selected' : '' ?>>Sri Lanka</option>
                                    <option value="in" <?= $country === 'in' ? 'selected' : '' ?>>India</option>
                                    <option value="us" <?= $country === 'us' ? 'selected' : '' ?>>United States</option>
                                    <option value="uk" <?= $country === 'uk' ? 'selected' : '' ?>>United Kingdom</option>
                                    <option value="other" <?= $country === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <hr class="divider">
                <div class="button-row">
                    <a href="<?= ROOT ?>/registration/step1" class="btn btn-back">&#8592; Back: Basic Information</a>
                    <button type="submit" class="btn btn-next">Next: Leadership &#8594;</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
