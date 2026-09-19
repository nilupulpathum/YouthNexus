<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/registration.css">
    <style>
        .card { margin-bottom: 25px; overflow: hidden; }
        .card-header {
            background-color: #eef1f8; padding: 18px 25px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-header-left { display: flex; align-items: center; gap: 12px; }
        .card-icon {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
        .card-icon svg { width: 20px; height: 20px; fill: #fff; }
        .icon-blue { background-color: #1a237e; }
        .icon-orange { background-color: #e65100; }
        .icon-brown { background-color: #8d6e63; }
        .card-title { font-size: 18px; font-weight: bold; color: #1a1a1a; }
        .badge-role {
            background-color: #e8eaf6; color: #1a237e; font-size: 11px;
            font-weight: bold; padding: 5px 14px; border-radius: 20px; letter-spacing: 0.5px;
        }
        .card-body { padding: 25px; }
        .form-row { display: flex; gap: 25px; }
        .form-left { width: 280px; flex-shrink: 0; }
        .form-right { flex: 1; }
        .form-group { margin-bottom: 18px; }
        .form-group:last-child { margin-bottom: 0; }
        input[type="text"], input[type="email"], input[type="tel"], input[type="date"], select {
            width: 100%; padding: 12px 14px; border: 1px solid #d0d0d0;
            border-radius: 8px; font-size: 14px; color: #333;
            background-color: #fff; font-family: Arial, sans-serif;
        }
        .two-col { display: flex; gap: 20px; }
        .two-col .form-group { flex: 1; }
        .upload-box {
            border: 2px dashed #c5c5c5; border-radius: 12px; padding: 40px 20px;
            text-align: center; cursor: pointer; transition: border-color 0.2s;
        }
        .upload-box:hover { border-color: #1a237e; }
        .upload-icon-big {
            width: 70px; height: 70px; background-color: #e8eaf6; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;
        }
        .upload-icon-big svg { width: 32px; height: 32px; fill: #9fa8da; }
        .upload-text { font-size: 14px; color: #555; margin-bottom: 4px; }
        .upload-hint { font-size: 12px; color: #888; }
        .cards-row { display: flex; gap: 25px; }
        .cards-row .card { flex: 1; margin-bottom: 0; }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="logo-text">YouthNexus Registration</div>
            <div class="step-indicator">Step 3 of 7</div>
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
        <h1 class="page-title">Executive Committee Details</h1>
        <p class="page-subtitle">Please provide the contact and identity details for the primary leadership roles of your club.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="<?= ROOT ?>/registration/step3" method="POST" enctype="multipart/form-data">

            <!-- President Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-left">
                        <div class="card-icon icon-blue">
                            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        </div>
                        <div class="card-title">President</div>
                    </div>
                    <div class="badge-role">PRIMARY OFFICER</div>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-left">
                            <div class="upload-box" onclick="document.getElementById('presidentPhoto').click()">
                                <div class="upload-icon-big">
                                    <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                </div>
                                <div class="upload-text">Profile Photo</div>
                                <div class="upload-hint">JPG or PNG, max 2MB</div>
                                <input type="file" id="presidentPhoto" name="president_photo" accept="image/png, image/jpeg" style="display: none;">
                            </div>
                        </div>
                        <div class="form-right">
                            <div class="two-col">
                                <div class="form-group">
                                    <label for="presidentName">Full Name (As per NIC)</label>
                                    <input type="text" id="presidentName" name="president_name" placeholder="Enter President's Full Name" value="<?= htmlspecialchars($president_name) ?>">
                                </div>
                                <div class="form-group">
                                    <label for="presidentNic">NIC Number</label>
                                    <input type="text" id="presidentNic" name="president_nic" placeholder="e.g. 199512345678" value="<?= htmlspecialchars($president_nic) ?>">
                                </div>
                            </div>
                            <div class="two-col">
                                <div class="form-group">
                                    <label for="presidentDob">Date of Birth</label>
                                    <input type="text" id="presidentDob" name="president_dob" placeholder="mm/dd/yyyy" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" value="<?= htmlspecialchars($president_dob) ?>">
                                </div>
                                <div class="form-group">
                                    <label for="presidentPhone">Phone Number</label>
                                    <input type="tel" id="presidentPhone" name="president_phone" placeholder="+94 77 123 4567" value="<?= htmlspecialchars($president_phone) ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="presidentEmail">Email Address</label>
                                <input type="email" id="presidentEmail" name="president_email" placeholder="president@youthnexus.org" value="<?= htmlspecialchars($president_email) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secretary & Treasurer Row -->
            <div class="cards-row">
                <!-- Secretary Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-icon icon-orange">
                                <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                            </div>
                            <div class="card-title">Secretary</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="secretaryName">Full Name</label>
                            <input type="text" id="secretaryName" name="secretary_name" placeholder="Enter Secretary's Full Name" value="<?= htmlspecialchars($secretary_name) ?>">
                        </div>
                        <div class="two-col">
                            <div class="form-group">
                                <label for="secretaryNic">NIC Number</label>
                                <input type="text" id="secretaryNic" name="secretary_nic" placeholder="NIC Number" value="<?= htmlspecialchars($secretary_nic) ?>">
                            </div>
                            <div class="form-group">
                                <label for="secretaryPhone">Phone Number</label>
                                <input type="tel" id="secretaryPhone" name="secretary_phone" placeholder="Phone" value="<?= htmlspecialchars($secretary_phone) ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="secretaryDob">Date of Birth</label>
                            <input type="text" id="secretaryDob" name="secretary_dob" placeholder="mm/dd/yyyy" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" value="<?= htmlspecialchars($secretary_dob) ?>">
                        </div>
                        <div class="form-group">
                            <label for="secretaryEmail">Email Address</label>
                            <input type="email" id="secretaryEmail" name="secretary_email" placeholder="secretary@youthnexus.org" value="<?= htmlspecialchars($secretary_email) ?>">
                        </div>
                    </div>
                </div>

                <!-- Treasurer Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-icon icon-brown">
                                <svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                            </div>
                            <div class="card-title">Treasurer</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="treasurerName">Full Name</label>
                            <input type="text" id="treasurerName" name="treasurer_name" placeholder="Enter Treasurer's Full Name" value="<?= htmlspecialchars($treasurer_name) ?>">
                        </div>
                        <div class="two-col">
                            <div class="form-group">
                                <label for="treasurerNic">NIC Number</label>
                                <input type="text" id="treasurerNic" name="treasurer_nic" placeholder="NIC Number" value="<?= htmlspecialchars($treasurer_nic) ?>">
                            </div>
                            <div class="form-group">
                                <label for="treasurerPhone">Phone Number</label>
                                <input type="tel" id="treasurerPhone" name="treasurer_phone" placeholder="Phone" value="<?= htmlspecialchars($treasurer_phone) ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="treasurerDob">Date of Birth</label>
                            <input type="text" id="treasurerDob" name="treasurer_dob" placeholder="mm/dd/yyyy" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" value="<?= htmlspecialchars($treasurer_dob) ?>">
                        </div>
                        <div class="form-group">
                            <label for="treasurerEmail">Email Address</label>
                            <input type="email" id="treasurerEmail" name="treasurer_email" placeholder="treasurer@youthnexus.org" value="<?= htmlspecialchars($treasurer_email) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="button-bar">
                <a href="<?= ROOT ?>/registration/step2" class="btn btn-back">&#8592; Back</a>
                <button type="submit" class="btn btn-next">Next: Assets Details &#8594;</button>
            </div>
        </form>
    </div>

</body>
</html>
