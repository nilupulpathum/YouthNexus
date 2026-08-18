<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/registration.css">
    <style>
        .page-title { margin-bottom: 10px; }
        .card { padding: 25px; margin-bottom: 20px; }
        .card-header-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .card-header-left { display: flex; align-items: center; gap: 12px; }
        .icon-circle {
            width: 44px; height: 44px; background-color: #eef2ff; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .icon-circle svg { width: 22px; height: 22px; fill: #1a237e; }
        .card-title { font-size: 18px; font-weight: bold; color: #1a1a1a; }
        .card-desc { color: #666; font-size: 13px; line-height: 1.5; margin-bottom: 20px; max-width: 90%; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-required { background-color: #fff3e0; color: #e65100; }
        .badge-optional { background-color: #f3f4f6; color: #666; }
        .upload-box {
            border: 2px dashed #c5c5c5; border-radius: 10px; padding: 35px 20px;
            text-align: center; cursor: pointer; background-color: #fafafa; transition: border-color 0.2s;
        }
        .upload-box:hover { border-color: #1a237e; }
        .upload-box svg { width: 24px; height: 24px; fill: #888; margin-bottom: 8px; }
        .upload-text { font-size: 13px; color: #1a237e; font-weight: 600; margin-bottom: 4px; }
        .upload-hint { font-size: 12px; color: #aaa; }
        .row-two { display: flex; gap: 20px; margin-bottom: 20px; }
        .row-two .card { flex: 1; margin-bottom: 0; }
        .nic-grid { display: flex; gap: 15px; margin-top: 15px; }
        .nic-box { flex: 1; border: 1px solid #e8e8e8; border-radius: 10px; padding: 15px; text-align: center; }
        .nic-label { font-size: 13px; font-weight: 600; color: #555; margin-bottom: 12px; }
        .nic-upload { border: 2px dashed #c5c5c5; border-radius: 8px; padding: 30px 10px; cursor: pointer; background-color: #fafafa; }
        .nic-upload:hover { border-color: #1a237e; }
        .nic-upload svg { width: 20px; height: 20px; fill: #aaa; margin-bottom: 6px; }
        .nic-upload-text { font-size: 11px; color: #888; letter-spacing: 0.5px; }
        .activity-row { display: flex; gap: 30px; align-items: center; }
        .activity-left { flex: 1; }
        .activity-right { flex: 1.2; }
        .activity-right .upload-box { padding: 45px 20px; }
        .info-banner { background-color: #e8eaf6; border-radius: 10px; padding: 18px 22px; display: flex; align-items: flex-start; gap: 12px; margin-top: 10px; }
        .info-banner svg { width: 20px; height: 20px; fill: #1a237e; flex-shrink: 0; margin-top: 2px; }
        .info-banner p { font-size: 13px; color: #555; line-height: 1.6; }
        .file-name { font-size: 12px; color: #1a237e; margin-top: 8px; word-break: break-all; }
        .upload-form { display: none; }
        @media (max-width: 768px) { .row-two, .nic-grid, .activity-row { flex-direction: column; } }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="logo-text">YouthNexus Registration</div>
            <div class="step-indicator">Step 6 of 7</div>
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
        <h1 class="page-title">Supporting Documents</h1>
        <p class="page-subtitle">Upload the necessary legal and administrative documents to verify your club's registration. Files must be under 10MB each.</p>

        <!-- Constitution + Venue -->
        <div class="row-two">
            <!-- Constitution -->
            <div class="card">
                <div class="card-header-row">
                    <div class="card-header-left">
                        <div class="icon-circle"><svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg></div>
                    </div>
                    <span class="badge badge-required">Required (PDF)</span>
                </div>
                <div class="card-title" style="margin-bottom:6px;">Constitution / Club Charter</div>
                <div class="card-desc">A scanned copy of the official governing document signed by the executive committee.</div>
                <div class="upload-box" onclick="document.getElementById('constitutionInput').click()">
                    <svg viewBox="0 0 24 24"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
                    <div class="upload-text">Click to upload or drag &amp; drop</div>
                    <div class="upload-hint">PDF only, max 10MB</div>
                    <?php if (!empty($docs['constitution_path'])): ?>
                        <div class="file-name"><?= htmlspecialchars(basename($docs['constitution_path'])) ?></div>
                    <?php endif; ?>
                </div>
                <form method="POST" action="<?= ROOT ?>/registration/step6" enctype="multipart/form-data" class="upload-form">
                    <input type="hidden" name="field_name" value="constitution_path">
                    <input type="file" id="constitutionInput" name="doc_file" accept=".pdf" onchange="this.form.submit()">
                    <input type="hidden" name="upload_doc" value="1">
                </form>
            </div>

            <!-- Proof of Meeting Venue -->
            <div class="card">
                <div class="card-header-row">
                    <div class="card-header-left">
                        <div class="icon-circle"><svg viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/></svg></div>
                    </div>
                    <span class="badge badge-required">Required</span>
                </div>
                <div class="card-title" style="margin-bottom:6px;">Proof of Meeting Venue</div>
                <div class="card-desc">Utility bill, rental agreement, or authorization letter from the premises owner.</div>
                <div class="upload-box" onclick="document.getElementById('venueInput').click()">
                    <svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                    <div class="upload-text">Upload JPG, PNG, or PDF</div>
                    <div class="upload-hint">Max 5MB per file</div>
                    <?php if (!empty($docs['venue_proof_path'])): ?>
                        <div class="file-name"><?= htmlspecialchars(basename($docs['venue_proof_path'])) ?></div>
                    <?php endif; ?>
                </div>
                <form method="POST" action="<?= ROOT ?>/registration/step6" enctype="multipart/form-data" class="upload-form">
                    <input type="hidden" name="field_name" value="venue_proof_path">
                    <input type="file" id="venueInput" name="doc_file" accept="image/jpeg,image/png,.pdf" onchange="this.form.submit()">
                    <input type="hidden" name="upload_doc" value="1">
                </form>
            </div>
        </div>

        <!-- NIC Copies -->
        <div class="card">
            <div class="card-header-row">
                <div class="card-header-left">
                    <div class="icon-circle"><svg viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg></div>
                    <div class="card-title" style="margin:0;">NIC Copies of Key Officials</div>
                </div>
                <span class="badge badge-required">Mandatory Verification</span>
            </div>
            <div class="card-desc">Front and back scans for the President, Secretary, and Treasurer.</div>
            <div class="nic-grid">
                <?php
                $nicFields = [
                    ['key' => 'nic_president_path', 'label' => 'President', 'id' => 'nicPresInput'],
                    ['key' => 'nic_secretary_path', 'label' => 'Secretary', 'id' => 'nicSecInput'],
                    ['key' => 'nic_treasurer_path', 'label' => 'Treasurer', 'id' => 'nicTreasInput'],
                ];
                foreach ($nicFields as $nf):
                ?>
                <div class="nic-box">
                    <div class="nic-label"><?= $nf['label'] ?></div>
                    <div class="nic-upload" onclick="document.getElementById('<?= $nf['id'] ?>').click()">
                        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                        <div class="nic-upload-text">FRONT &amp; BACK</div>
                        <?php if (!empty($docs[$nf['key']])): ?>
                            <div class="file-name"><?= htmlspecialchars(basename($docs[$nf['key']])) ?></div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="<?= ROOT ?>/registration/step6" enctype="multipart/form-data" class="upload-form">
                        <input type="hidden" name="field_name" value="<?= $nf['key'] ?>">
                        <input type="file" id="<?= $nf['id'] ?>" name="doc_file" accept="image/jpeg,image/png,.pdf" onchange="this.form.submit()">
                        <input type="hidden" name="upload_doc" value="1">
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Club Activity Photos -->
        <div class="card">
            <div class="activity-row">
                <div class="activity-left">
                    <div class="card-header-left" style="margin-bottom:12px;">
                        <div class="icon-circle"><svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg></div>
                    </div>
                    <div class="card-title" style="margin-bottom:6px;">Club Activity Photos</div>
                    <div class="card-desc">Upload up to 5 photos showing recent club meetings, community projects, or events.</div>
                    <span class="badge badge-optional">Optional</span>
                </div>
                <div class="activity-right">
                    <div class="upload-box" onclick="document.getElementById('activityInput').click()">
                        <svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                        <div class="upload-text">Upload Club Images</div>
                        <div class="upload-hint">Maximum 5 images, JPG/PNG preferred</div>
                        <?php if (!empty($docs['activity_photos'])): ?>
                            <div class="file-name"><?= count($docs['activity_photos']) ?> photo(s) uploaded</div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="<?= ROOT ?>/registration/step6" enctype="multipart/form-data" class="upload-form">
                        <input type="hidden" name="field_name" value="activity_photos">
                        <input type="file" id="activityInput" name="doc_file" accept="image/jpeg,image/png" onchange="this.form.submit()">
                        <input type="hidden" name="upload_doc" value="1">
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Banner -->
        <div class="info-banner">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
            <p>Please ensure all text in scanned documents is legible to avoid delays in the verification process.</p>
        </div>

        <!-- Buttons -->
        <div class="button-bar">
            <a href="<?= ROOT ?>/registration/step5" class="btn btn-back">&#8592; Back</a>
            <a href="<?= ROOT ?>/registration/step7" class="btn btn-next">Next: Review &#8594;</a>
        </div>
    </div>

<script>
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            sessionStorage.setItem('scrollPos', window.scrollY);
        });
    });
    window.addEventListener('load', function() {
        var pos = sessionStorage.getItem('scrollPos');
        if (pos) { window.scrollTo(0, parseInt(pos)); sessionStorage.removeItem('scrollPos'); }
    });
</script>
</body>
</html>
