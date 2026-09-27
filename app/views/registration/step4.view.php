<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/registration.css">
    <style>
        .card { padding: 25px; margin-bottom: 20px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-title { font-size: 18px; font-weight: bold; color: #1a1a1a; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: #888; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 0; border-bottom: 1px solid #e0e0e0; }
        td { padding: 18px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #333; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .trash-btn { background: none; border: none; cursor: pointer; color: #888; padding: 4px; }
        .trash-btn:hover { color: #ef4444; }
        .trash-btn svg { width: 18px; height: 18px; fill: currentColor; }
        .form-group { margin-bottom: 18px; }
        input[type="text"], input[type="number"], select {
            width: 100%; padding: 12px 14px; border: 1px solid #d0d0d0;
            border-radius: 8px; font-size: 14px; color: #333; background-color: #fff; font-family: Arial, sans-serif;
        }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        .btn-brown {
            background-color: #a0522d; color: #fff; border: none; border-radius: 8px;
            padding: 14px; width: 100%; font-size: 15px; font-weight: 600; cursor: pointer; font-family: Arial, sans-serif;
        }
        .btn-brown:hover { background-color: #8b4513; }
        .upload-area { display: flex; gap: 15px; align-items: flex-start; flex-wrap: wrap; }
        .upload-box {
            width: 140px; height: 110px; border: 2px dashed #c5c5c5; border-radius: 10px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: #888; font-size: 12px; cursor: pointer; background-color: #fafafa;
        }
        .upload-box:hover { border-color: #1a237e; }
        .upload-box svg { width: 28px; height: 28px; fill: #9fa8da; margin-bottom: 6px; }
        .photo-thumb { width: 140px; height: 110px; border-radius: 10px; object-fit: cover; }
        .info-box { background-color: #e8eaf6; border-radius: 12px; padding: 22px; }
        .info-title { font-size: 12px; font-weight: bold; color: #1a237e; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .info-title svg { width: 18px; height: 18px; fill: #1a237e; }
        .info-text { font-size: 13px; color: #555; line-height: 1.7; }
        .upload-form { display: none; }
        @media (max-width: 768px) { .main-row { flex-direction: column; } }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="logo-text">YouthNexus Registration</div>
            <div class="step-indicator">Step 4 of 7</div>
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
        <h1 class="page-title">Initial Club Assets</h1>
        <p class="page-subtitle">Please declare all physical assets currently owned by the club.</p>

        <div class="main-row">
            <!-- Left Side -->
            <div class="main-left">
                <!-- Asset Inventory -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Asset Inventory</div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Asset Name</th>
                                <th>Quantity</th>
                                <th>Condition</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assets as $index => $asset):
                                $cond = strtolower($asset['condition']);
                                if ($cond == 'good') { $bg = '#dbeafe'; $clr = '#1e40af'; }
                                elseif ($cond == 'excellent') { $bg = '#ede9fe'; $clr = '#5b21b6'; }
                                elseif ($cond == 'fair') { $bg = '#fee2e2'; $clr = '#991b1b'; }
                                else { $bg = '#f3f4f6'; $clr = '#374151'; }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($asset['name']) ?></td>
                                <td><?= str_pad($asset['quantity'], 2, '0', STR_PAD_LEFT) ?></td>
                                <td><span class="badge" style="background:<?= $bg ?>;color:<?= $clr ?>"><?= htmlspecialchars($asset['condition']) ?></span></td>
                                <td>
                                    <a href="<?= ROOT ?>/registration/step4?delete_asset=<?= $index ?>" class="trash-btn" onclick="return confirm('Delete this asset?')">
                                        <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($assets)): ?>
                            <tr><td colspan="4" style="text-align:center;color:#888;padding:20px;">No assets added yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Asset Photos -->
                <div class="card">
                    <div class="card-title" style="margin-bottom:6px;">Asset Photos</div>
                    <p style="font-size:13px;color:#666;margin-bottom:15px;">Upload clear photos of major assets for verification (JPG, PNG - Max 5MB each)</p>
                    <div class="upload-area">
                        <div class="upload-box" onclick="document.getElementById('photoInput').click()">
                            <svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>
                            Upload File
                        </div>
                        <form method="POST" action="<?= ROOT ?>/registration/step4" enctype="multipart/form-data" class="upload-form">
                            <input type="file" id="photoInput" name="asset_photo" accept="image/jpeg, image/png" onchange="this.form.submit()">
                        </form>
                        <?php foreach ($asset_photos as $photo): ?>
                            <img src="<?= ROOT ?>/../<?= htmlspecialchars($photo) ?>" class="photo-thumb" alt="Asset Photo">
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right Side -->
            <div class="main-right">
                <div class="card">
                    <div class="card-title" style="margin-bottom:20px;">Add New Asset</div>
                    <form method="POST" action="<?= ROOT ?>/registration/step4">
                        <div class="form-group">
                            <label>Asset Name</label>
                            <input type="text" name="asset_name" placeholder="e.g. Office Desks" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="number" name="quantity" value="1" min="1" required>
                            </div>
                            <div class="form-group">
                                <label>Condition</label>
                                <select name="condition">
                                    <option>Excellent</option>
                                    <option>Good</option>
                                    <option>Fair</option>
                                    <option>Poor</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="add_asset" class="btn-brown">Add Asset</button>
                    </form>
                </div>
                <div class="info-box">
                    <div class="info-title">
                        <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                        WHY DECLARE ASSETS?
                    </div>
                    <div class="info-text">Asset declaration ensures transparency and legal recognition of the club's financial health. It assists in insurance claims and annual auditing processes required by the Divisional Council.</div>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="button-bar">
            <a href="<?= ROOT ?>/registration/step3" class="btn btn-back">&#8592; Back</a>
            <a href="<?= ROOT ?>/registration/step5" class="btn btn-next">Next: Bank Details &#8594;</a>
        </div>
    </div>

</body>
</html>
