<?php

class Registration extends Controller {

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
    }

    public function index() {
        $this->step1();
    }

    // ---------------------------------------------------------------
    // STEP 1: Basic Information
    // ---------------------------------------------------------------
    public function step1() {
        $data = [
            'title'       => 'Step 1 — Club Registration',
            'step'        => 1,
            'error'       => '',
            'club_name'   => $_SESSION['reg_step1']['club_name']   ?? '',
            'category'    => $_SESSION['reg_step1']['category']    ?? '',
            'description' => $_SESSION['reg_step1']['description'] ?? '',
            'date_establishment' => $_SESSION['reg_step1']['date_establishment'] ?? '',
            'no_of_members'      => $_SESSION['reg_step1']['no_of_members'] ?? '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $clubName   = htmlspecialchars(trim($_POST['club_name'] ?? ''), ENT_QUOTES);
            $category   = htmlspecialchars(trim($_POST['category'] ?? ''), ENT_QUOTES);
            $desc       = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES);
            $dateEst    = trim($_POST['date_establishment'] ?? '');
            $noMembers  = intval($_POST['no_of_members'] ?? 0);

            if (empty($clubName)) {
                $data['error'] = 'Club Name is required.';
            } else {
                // Handle club logo upload
                $logoPath = $_SESSION['reg_step1']['club_logo_path'] ?? null;
                if (isset($_FILES['club_logo']) && $_FILES['club_logo']['error'] === UPLOAD_ERR_OK) {
                    $logoPath = $this->handleUpload($_FILES['club_logo'], 'club_logos', ['image/png', 'image/jpeg'], 5 * 1024 * 1024);
                }

                $_SESSION['reg_step1'] = [
                    'club_name'          => $clubName,
                    'category'           => $category,
                    'description'        => $desc,
                    'date_establishment' => $dateEst,
                    'no_of_members'      => $noMembers,
                    'club_logo_path'     => $logoPath,
                ];

                $this->redirect('registration/step2');
            }
        }

        $this->view('registration/step1', $data);
    }

    // ---------------------------------------------------------------
    // STEP 2: Location Details
    // ---------------------------------------------------------------
    public function step2() {
        $data = [
            'title'         => 'Step 2 — Club Registration',
            'step'          => 2,
            'error'         => '',
            'location_type' => $_SESSION['reg_step2']['location_type'] ?? '',
            'street_address'=> $_SESSION['reg_step2']['street_address'] ?? '',
            'city'          => $_SESSION['reg_step2']['city'] ?? '',
            'state_province'=> $_SESSION['reg_step2']['state_province'] ?? '',
            'postal_code'   => $_SESSION['reg_step2']['postal_code'] ?? '',
            'country'       => $_SESSION['reg_step2']['country'] ?? 'lk',
            'division_id'   => $_SESSION['reg_step2']['division_id'] ?? '',
        ];

        // Load divisions for dropdown
        $divisionModel = $this->model('DivisionModel');
        $data['divisions'] = $divisionModel->getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $locationType = htmlspecialchars(trim($_POST['location_type'] ?? ''), ENT_QUOTES);
            $streetAddr   = htmlspecialchars(trim($_POST['street_address'] ?? ''), ENT_QUOTES);
            $city         = htmlspecialchars(trim($_POST['city'] ?? ''), ENT_QUOTES);
            $state        = htmlspecialchars(trim($_POST['state_province'] ?? ''), ENT_QUOTES);
            $postal       = htmlspecialchars(trim($_POST['postal_code'] ?? ''), ENT_QUOTES);
            $country      = htmlspecialchars(trim($_POST['country'] ?? 'lk'), ENT_QUOTES);
            $divisionId   = intval($_POST['division_id'] ?? 0);

            $_SESSION['reg_step2'] = [
                'location_type'  => $locationType,
                'street_address' => $streetAddr,
                'city'           => $city,
                'state_province' => $state,
                'postal_code'    => $postal,
                'country'        => $country,
                'division_id'    => $divisionId,
            ];

            $this->redirect('registration/step3');
        }

        $this->view('registration/step2', $data);
    }

    // ---------------------------------------------------------------
    // STEP 3: Executive Committee Details
    // ---------------------------------------------------------------
    public function step3() {
        $data = [
            'title' => 'Step 3 — Club Registration',
            'step'  => 3,
            'error' => '',
        ];

        // Pre-fill from session
        $roles = ['president', 'secretary', 'treasurer'];
        foreach ($roles as $role) {
            $prefix = $role;
            $data[$prefix . '_name']  = $_SESSION['reg_step3'][$prefix . '_name']  ?? '';
            $data[$prefix . '_nic']   = $_SESSION['reg_step3'][$prefix . '_nic']   ?? '';
            $data[$prefix . '_dob']   = $_SESSION['reg_step3'][$prefix . '_dob']   ?? '';
            $data[$prefix . '_phone'] = $_SESSION['reg_step3'][$prefix . '_phone'] ?? '';
            $data[$prefix . '_email'] = $_SESSION['reg_step3'][$prefix . '_email'] ?? '';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $step3 = [];
            $valid = true;

            foreach ($roles as $role) {
                $step3[$role . '_name']  = htmlspecialchars(trim($_POST[$role . '_name'] ?? ''), ENT_QUOTES);
                $step3[$role . '_nic']   = htmlspecialchars(trim($_POST[$role . '_nic'] ?? ''), ENT_QUOTES);
                $step3[$role . '_dob']   = trim($_POST[$role . '_dob'] ?? '');
                $step3[$role . '_phone'] = htmlspecialchars(trim($_POST[$role . '_phone'] ?? ''), ENT_QUOTES);
                $step3[$role . '_email'] = filter_var(trim($_POST[$role . '_email'] ?? ''), FILTER_SANITIZE_EMAIL);

                // Handle photo upload
                $photoKey = $role . '_photo';
                $photoPath = $_SESSION['reg_step3'][$photoKey] ?? null;
                if (isset($_FILES[$photoKey]) && $_FILES[$photoKey]['error'] === UPLOAD_ERR_OK) {
                    $photoPath = $this->handleUpload($_FILES[$photoKey], 'nominee_photos', ['image/png', 'image/jpeg'], 2 * 1024 * 1024);
                }
                $step3[$photoKey] = $photoPath;
            }

            // Validate: at least president name and email required
            if (empty($step3['president_name']) || empty($step3['president_email'])) {
                $data['error'] = 'President name and email are required.';
                foreach ($roles as $role) {
                    $data[$role . '_name']  = $step3[$role . '_name'];
                    $data[$role . '_nic']   = $step3[$role . '_nic'];
                    $data[$role . '_dob']   = $step3[$role . '_dob'];
                    $data[$role . '_phone'] = $step3[$role . '_phone'];
                    $data[$role . '_email'] = $step3[$role . '_email'];
                }
            } else {
                $_SESSION['reg_step3'] = $step3;
                $this->redirect('registration/step4');
            }
        }

        $this->view('registration/step3', $data);
    }

    // ---------------------------------------------------------------
    // STEP 4: Initial Club Assets
    // ---------------------------------------------------------------
    public function step4() {
        $data = [
            'title' => 'Step 4 — Club Registration',
            'step'  => 4,
            'error' => '',
        ];

        // Init assets session
        if (!isset($_SESSION['reg_step4'])) {
            $_SESSION['reg_step4'] = ['assets' => [], 'asset_photos' => []];
        }

        // Delete asset
        if (isset($_GET['delete_asset'])) {
            $idx = intval($_GET['delete_asset']);
            if (isset($_SESSION['reg_step4']['assets'][$idx])) {
                array_splice($_SESSION['reg_step4']['assets'], $idx, 1);
            }
            $this->redirect('registration/step4');
        }

        // Add new asset
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_asset'])) {
            $name = htmlspecialchars(trim($_POST['asset_name'] ?? ''), ENT_QUOTES);
            $qty  = intval($_POST['quantity'] ?? 1);
            $cond = htmlspecialchars(trim($_POST['condition'] ?? 'Good'), ENT_QUOTES);

            if (!empty($name) && $qty > 0) {
                $_SESSION['reg_step4']['assets'][] = [
                    'name'      => $name,
                    'quantity'  => $qty,
                    'condition' => $cond,
                ];
            }
            $this->redirect('registration/step4');
        }

        // Handle asset photo upload
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['asset_photo']) && $_FILES['asset_photo']['error'] === UPLOAD_ERR_OK) {
            $path = $this->handleUpload($_FILES['asset_photo'], 'asset_photos', ['image/jpeg', 'image/png'], 5 * 1024 * 1024);
            if ($path) {
                $_SESSION['reg_step4']['asset_photos'][] = $path;
            }
            $this->redirect('registration/step4');
        }

        $data['assets']       = $_SESSION['reg_step4']['assets'];
        $data['asset_photos'] = $_SESSION['reg_step4']['asset_photos'];

        $this->view('registration/step4', $data);
    }

    // ---------------------------------------------------------------
    // STEP 5: Verification & Disbursements (Bank Details)
    // ---------------------------------------------------------------
    public function step5() {
        $data = [
            'title'          => 'Step 5 — Club Registration',
            'step'           => 5,
            'error'          => '',
            'bank_name'      => $_SESSION['reg_step5']['bank_name'] ?? '',
            'bank_branch'    => $_SESSION['reg_step5']['bank_branch'] ?? '',
            'account_holder' => $_SESSION['reg_step5']['account_holder'] ?? '',
            'account_number' => $_SESSION['reg_step5']['account_number'] ?? '',
            'bank_confirmed' => $_SESSION['reg_step5']['bank_confirmed'] ?? false,
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $bankName      = htmlspecialchars(trim($_POST['bank_name'] ?? ''), ENT_QUOTES);
            $branch        = htmlspecialchars(trim($_POST['branch'] ?? ''), ENT_QUOTES);
            $accountHolder = htmlspecialchars(trim($_POST['account_holder'] ?? ''), ENT_QUOTES);
            $accountNumber = htmlspecialchars(trim($_POST['account_number'] ?? ''), ENT_QUOTES);
            $bankConfirmed = isset($_POST['bank_confirmed']);

            if (empty($bankName) || empty($accountHolder) || empty($accountNumber)) {
                $data['error'] = 'Bank name, account holder, and account number are required.';
                $data['bank_name']      = $bankName;
                $data['bank_branch']    = $branch;
                $data['account_holder'] = $accountHolder;
                $data['account_number'] = $accountNumber;
            } else {
                $_SESSION['reg_step5'] = [
                    'bank_name'      => $bankName,
                    'bank_branch'    => $branch,
                    'account_holder' => $accountHolder,
                    'account_number' => $accountNumber,
                    'bank_confirmed' => $bankConfirmed,
                ];
                $this->redirect('registration/step6');
            }
        }

        $this->view('registration/step5', $data);
    }

    // ---------------------------------------------------------------
    // STEP 6: Supporting Documents
    // ---------------------------------------------------------------
    public function step6() {
        $data = [
            'title' => 'Step 6 — Club Registration',
            'step'  => 6,
            'error' => '',
        ];

        // Init docs session
        if (!isset($_SESSION['reg_step6'])) {
            $_SESSION['reg_step6'] = [
                'constitution_path'  => '',
                'venue_proof_path'   => '',
                'nic_president_path' => '',
                'nic_secretary_path' => '',
                'nic_treasurer_path' => '',
                'activity_photos'    => [],
            ];
        }

        // Handle file uploads
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['field_name'])) {
            $field = $_POST['field_name'];
            $file  = $_FILES['doc_file'] ?? null;

            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $maxSize = 10 * 1024 * 1024; // 10MB
                $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];

                if (in_array($ext, $allowedExts) && $file['size'] <= $maxSize) {
                    $path = $this->handleUpload($file, 'registration_docs', ['image/jpeg', 'image/png', 'application/pdf'], $maxSize);
                    if ($path) {
                        if ($field === 'activity_photos') {
                            $_SESSION['reg_step6']['activity_photos'][] = $path;
                        } else {
                            $_SESSION['reg_step6'][$field] = $path;
                        }
                    }
                }
            }
            $this->redirect('registration/step6');
        }

        $data['docs'] = $_SESSION['reg_step6'];
        $this->view('registration/step6', $data);
    }

    // ---------------------------------------------------------------
    // STEP 7: Final Declaration
    // ---------------------------------------------------------------
    public function step7() {
        $data = [
            'title'    => 'Step 7 — Club Registration',
            'step'     => 7,
            'error'    => '',
            'sigValue' => $_SESSION['reg_step7']['digital_signature'] ?? '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_app'])) {
            $infoAccuracy  = isset($_POST['info_accuracy']);
            $termsAccepted = isset($_POST['terms_conditions']);
            $signature     = htmlspecialchars(trim($_POST['digital_signature'] ?? ''), ENT_QUOTES);

            if (!$infoAccuracy || !$termsAccepted || empty($signature)) {
                $data['error'] = 'You must acknowledge all declarations and provide a digital signature.';
                $data['sigValue'] = $signature;
            } else {
                $_SESSION['reg_step7'] = [
                    'info_accuracy'      => $infoAccuracy,
                    'terms_accepted'     => $termsAccepted,
                    'digital_signature'  => $signature,
                ];

                // Submit the full application
                $result = $this->submitApplication();

                if ($result) {
                    // Clear all reg step session data
                    for ($i = 1; $i <= 7; $i++) {
                        unset($_SESSION["reg_step{$i}"]);
                    }
                    $this->redirect('registration/success');
                } else {
                    $data['error'] = 'Failed to submit application. Please try again.';
                }
            }
        }

        $this->view('registration/step7', $data);
    }

    // ---------------------------------------------------------------
    // SUCCESS PAGE
    // ---------------------------------------------------------------
    public function success() {
        $data = [
            'title' => 'Application Submitted — YouthNexus',
        ];
        $this->view('registration/success', $data);
    }

    // ---------------------------------------------------------------
    // CANCEL — Clear session and redirect
    // ---------------------------------------------------------------
    public function cancel() {
        for ($i = 1; $i <= 7; $i++) {
            unset($_SESSION["reg_step{$i}"]);
        }
        $this->redirect('home');
    }

    // ===============================================================
    // PRIVATE HELPERS
    // ===============================================================

    /**
     * Handle file upload. Returns the saved file path or null on failure.
     */
    private function handleUpload($file, $subDir, $allowedMimes, $maxSize) {
        $uploadDir = '../uploads/' . $subDir . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $maxSize) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedMimes)) {
            return null;
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newName  = uniqid('yn_', true) . '.' . $ext;
        $target   = $uploadDir . $newName;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            return 'uploads/' . $subDir . '/' . $newName;
        }
        return null;
    }

    /**
     * Submit the complete club application from all session step data.
     * Creates: ClubApplication, ExecutiveNominee(s), ClubAsset(s), ClubApplicationPhoto(s).
     * Logs the action in AuditLog.
     */
    private function submitApplication() {
        $appModel     = $this->model('ClubApplicationModel');
        $nomineeModel = $this->model('ExecutiveNomineeModel');
        $assetModel   = $this->model('ClubAssetModel');
        $auditModel   = $this->model('AuditLogModel');

        $s1 = $_SESSION['reg_step1'] ?? [];
        $s2 = $_SESSION['reg_step2'] ?? [];
        $s3 = $_SESSION['reg_step3'] ?? [];
        $s5 = $_SESSION['reg_step5'] ?? [];
        $s6 = $_SESSION['reg_step6'] ?? [];
        $s7 = $_SESSION['reg_step7'] ?? [];

        $proposerId = $_SESSION['user_id'] ?? 0;
        if ($proposerId === 0) return false;

        try {
            // 1. Create ClubApplication record
            $applicationId = $appModel->createApplication([
                'proposer_user_id'   => $proposerId,
                'club_name'          => $s1['club_name'] ?? '',
                'description'        => $s1['description'] ?? '',
                'club_logo_path'     => $s1['club_logo_path'] ?? null,
                'category'           => $s1['category'] ?? null,
                'date_establishment' => $s1['date_establishment'] ?? null,
                'no_of_members'      => $s1['no_of_members'] ?? 0,
                'proposed_division_id' => $s2['division_id'] ?? null,
                'location_type'      => $s2['location_type'] ?? null,
                'street_address'     => $s2['street_address'] ?? null,
                'city'               => $s2['city'] ?? null,
                'state_province'     => $s2['state_province'] ?? null,
                'postal_code'        => $s2['postal_code'] ?? null,
                'country'            => $s2['country'] ?? 'Sri Lanka',
                'bank_name'          => $s5['bank_name'] ?? null,
                'bank_branch'        => $s5['bank_branch'] ?? null,
                'account_holder'     => $s5['account_holder'] ?? null,
                'account_number'     => $s5['account_number'] ?? null,
                'bank_confirmed'     => $s5['bank_confirmed'] ?? false,
                'constitution_path'  => $s6['constitution_path'] ?? null,
                'venue_proof_path'   => $s6['venue_proof_path'] ?? null,
                'nic_president_path' => $s6['nic_president_path'] ?? null,
                'nic_secretary_path' => $s6['nic_secretary_path'] ?? null,
                'nic_treasurer_path' => $s6['nic_treasurer_path'] ?? null,
                'info_accuracy'      => $s7['info_accuracy'] ?? false,
                'terms_accepted'     => $s7['terms_accepted'] ?? false,
                'digital_signature'  => $s7['digital_signature'] ?? null,
            ]);

            if (!$applicationId) return false;

            // 2. Create ExecutiveNominee records
            $roles = ['president', 'secretary', 'treasurer'];
            $roleMap = ['president' => 'President', 'secretary' => 'Secretary', 'treasurer' => 'Treasurer'];

            foreach ($roles as $prefix) {
                $name = $s3[$prefix . '_name'] ?? '';
                if (!empty($name)) {
                    $nomineeModel->createNominee([
                        'application_id' => $applicationId,
                        'role_type'      => $roleMap[$prefix],
                        'name'           => $name,
                        'email'          => $s3[$prefix . '_email'] ?? null,
                        'NIC'            => $s3[$prefix . '_nic'] ?? null,
                        'phone_number'   => $s3[$prefix . '_phone'] ?? null,
                        'date_of_birth'  => $s3[$prefix . '_dob'] ?? null,
                        'photo_path'     => $s3[$prefix . '_photo'] ?? null,
                    ]);
                }
            }

            // 3. Create ClubAsset records
            $s4 = $_SESSION['reg_step4'] ?? [];
            if (!empty($s4['assets'])) {
                foreach ($s4['assets'] as $asset) {
                    $assetModel->createAsset([
                        'application_id' => $applicationId,
                        'asset_name'     => $asset['name'],
                        'quantity'       => $asset['quantity'],
                        'condition'      => $asset['condition'],
                    ]);
                }
            }

            // 4. Create ClubApplicationPhoto records for activity photos
            if (!empty($s6['activity_photos'])) {
                $photoModel = $this->model('ClubApplicationPhotoModel');
                foreach ($s6['activity_photos'] as $photoPath) {
                    $photoModel->createPhoto($applicationId, $photoPath);
                }
            }

            // 5. Log the submission action
            $auditModel->logAction(
                $proposerId,
                'SUBMIT_CLUB_APPLICATION',
                'ClubApplication',
                $applicationId,
                'Club application submitted for: ' . ($s1['club_name'] ?? '')
            );

            return true;

        } catch (Exception $e) {
            return false;
        }
    }
}
