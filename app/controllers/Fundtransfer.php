<?php

/**
 * Fundtransfer Controller
 *
 * Handles the Fund Transfer module for NYSC Administration.
 * Routes:
 *   /fundtransfer                   → index()
 *   /fundtransfer/view/{id}         → view($id)
 *   /fundtransfer/create            → create()  [POST]
 *   /fundtransfer/getreference      → getreference() [GET]
 *   /fundtransfer/exportledger      → exportledger() [GET]
 *   /fundtransfer/receipt/{id}      → receipt($id)   [GET]
 */
class Fundtransfer extends Controller {

    /** Enforce NYSCAdministrator role. */
    private function requireNYSCAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        if (($_SESSION['user_role'] ?? '') !== 'NYSCAdministrator') {
            $this->redirect('home');
        }
    }

    /** True if client expects JSON. */
    private function isJsonRequest() {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xReq   = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return strpos($accept, 'application/json') !== false
            || strtolower($xReq) === 'xmlhttprequest'
            || isset($_GET['format']) && $_GET['format'] === 'json';
    }

    // ---------------------------------------------------------------
    // INDEX: Fund Transfer dashboard
    // ---------------------------------------------------------------
    public function index() {
        $this->requireNYSCAdmin();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $model = $this->model('FundTransferModel');

        $filters = [
            'search'    => trim($_GET['search']   ?? ''),
            'zone_id'   => !empty($_GET['zone_id'])  ? (int)$_GET['zone_id']  : null,
            'status'    => trim($_GET['status']   ?? 'All'),
            'quarter'   => trim($_GET['quarter']  ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to']   ?? ''),
        ];

        $transfers    = $model->getTransfers($filters);
        $stats        = $model->getStats();
        $zones        = $model->getAllZones();
        $bankAccounts = $model->getActiveBankAccounts();

        $defaultMethod = 'RTGS';
        $nextReference = $model->generateReferenceNumber($defaultMethod);

        // Preload verified receiving accounts for instant reactive pairing in allocation modal
        $rawZonalAccounts = $model->getAllZonalBankAccounts();
        $zonalAccountsMap = [];
        foreach ($rawZonalAccounts as $za) {
            if ($za->status === 'Active' && $za->verification_status === 'Verified') {
                $cleanAcc = preg_replace('/[^\d]/', '', $za->account_number);
                $masked = strlen($cleanAcc) >= 4 ? '****-' . substr($cleanAcc, -4) : '****-' . substr($za->account_number, -4);
                $zonalAccountsMap[(int)$za->owner_id] = [
                    'bank_account_id'     => (int)$za->bank_account_id,
                    'bank_name'           => $za->bank_name,
                    'branch_name'         => $za->branch_name,
                    'account_number'      => $za->account_number,
                    'masked_account'      => $masked,
                    'account_label'       => $za->account_label,
                    'gateway_type'        => $za->gateway_type,
                    'verification_status' => $za->verification_status,
                ];
            }
        }

        $this->view('fundtransfer/index', [
            'title'            => 'Fund Transfer — YouthNexus',
            'pageTitle'        => 'National Fund Disbursement & Zonal Ledger',
            'pageDescription'  => 'Manage inter-governmental grants, RTGS clearance, and zonal treasury allocations.',
            'currentRoute'     => 'fundtransfer',
            'userRole'         => $_SESSION['user_role'] ?? 'NYSCAdministrator',
            'userName'         => $_SESSION['user_name'] ?? 'N. Fernando',
            'userDesignation'  => 'Div. Secretary',
            'transfers'        => $transfers,
            'stats'            => $stats,
            'zones'            => $zones,
            'bankAccounts'     => $bankAccounts,
            'zonalAccountsMap' => $zonalAccountsMap,
            'filters'          => $filters,
            'nextReference'    => $nextReference,
            'csrf_token'       => $_SESSION['csrf_token'],
        ]);
    }

    // ---------------------------------------------------------------
    // DETAILS: Transfer details (for modal AJAX or direct view)
    // ---------------------------------------------------------------
    public function details($id = null) {
        $this->requireNYSCAdmin();

        $transferId = (int)$id;
        if (!$transferId) {
            $this->redirect('fundtransfer');
        }

        $model    = $this->model('FundTransferModel');
        $transfer = $model->findById($transferId);

        if (!$transfer) {
            if ($this->isJsonRequest()) {
                header('Content-Type: application/json');
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Transfer record not found.']);
                exit();
            }
            $this->redirect('fundtransfer');
        }

        if ($this->isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success'  => true,
                'transfer' => [
                    'id'          => $transfer->allocation_id,
                    'ref'         => $transfer->reference_no,
                    'status'      => $transfer->status,
                    'amount'      => number_format((float)$transfer->amount, 2),
                    'amount_raw'  => (float)$transfer->amount,
                    'datetime'    => date('Y-m-d - h:i A', strtotime($transfer->created_at)),
                    'date'        => date('F j, Y', strtotime($transfer->transfer_date)),
                    'zone'        => $transfer->target_zone_name . ($transfer->target_province ? " ({$transfer->target_province}" . ($transfer->target_hub_name ? " - {$transfer->target_hub_name}" : '') . ")" : ''),
                    'zone_name'   => $transfer->target_zone_name,
                    // Sender details
                    'sender_bank'          => $transfer->source_bank_name ?? $transfer->bank_name ?? 'Bank of Ceylon',
                    'sender_branch'        => $transfer->source_branch_name ?? $transfer->branch_name ?? 'Colombo Fort (001)',
                    'sender_account'       => $transfer->source_account_number ?? $transfer->account_number ?? '8620-0012-3456-7890',
                    'sender_account_label' => $transfer->source_account_name ?? $transfer->bank_account_name ?? 'NYSC Colombo Operational Fund',
                    'sender_gateway'       => $transfer->source_gateway_type ?? $transfer->gateway_type ?? 'RTGS',
                    // Recipient details
                    'recipient_bank'          => $transfer->recipient_bank_name ?? 'People\'s Bank',
                    'recipient_branch'        => $transfer->recipient_branch_name ?? 'Zonal Branch',
                    'recipient_account'       => $transfer->recipient_account_number ?? '—',
                    'recipient_account_label' => $transfer->recipient_account_name ?? 'Zonal Treasury Fund',
                    'recipient_verification'  => $transfer->recipient_verification_status ?? 'Verified',
                    // Legacy flat fields
                    'bank'        => $transfer->recipient_bank_name ?? $transfer->bank_name ?? 'People\'s Bank',
                    'branch'      => $transfer->recipient_branch_name ?? $transfer->branch_name ?? 'Zonal Branch',
                    'account'     => $transfer->recipient_account_number ?? $transfer->account_number ?? '—',
                    'account_label' => $transfer->recipient_account_name ?? $transfer->bank_account_name ?? 'Zonal Treasury Fund',
                    'method'      => ($transfer->disbursement_method === 'RTGS') ? 'BOC SLIPS / RTGS Direct' : 'Gov Cheque / SLIPS',
                    'authorized'  => $transfer->authorized_by_name ? $transfer->authorized_by_name . ' (' . ($transfer->authorizer_role ?? 'NYSC Admin') . ')' : 'NYSC Administrator',
                    'purpose'     => $transfer->purpose_description,
                    'subtitle'    => 'Fund disbursement for ' . ($transfer->target_zone_name ?? 'Zonal Office'),
                ],
            ]);
            exit();
        }

        // Direct page render fallback
        $this->view('fundtransfer/details', [
            'title'           => 'Transfer Details — YouthNexus',
            'pageTitle'       => 'Transfer Details',
            'pageDescription' => 'Fund disbursement transaction details',
            'currentRoute'    => 'fundtransfer',
            'userRole'        => $_SESSION['user_role'] ?? 'NYSCAdministrator',
            'userName'        => $_SESSION['user_name'] ?? 'NYSC Admin',
            'transfer'        => $transfer,
        ]);
    }

    public function show($id = null) {
        return $this->details($id);
    }

    // ---------------------------------------------------------------
    // CREATE: New Fund Allocation (POST handler)
    // ---------------------------------------------------------------
    public function create() {
        $this->requireNYSCAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('fundtransfer');
        }

        $isJson = $this->isJsonRequest();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        // 1. CSRF validation
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            if ($isJson) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Security token expired. Please refresh the page.']);
                exit();
            }
            $this->redirect('fundtransfer');
        }

        $model = $this->model('FundTransferModel');

        $zoneId             = !empty($_POST['zone_id']) ? (int)$_POST['zone_id'] : 0;
        $bankAccId          = !empty($_POST['bank_account_id']) ? (int)$_POST['bank_account_id'] : 0;
        $recipientBankAccId = !empty($_POST['recipient_bank_account_id']) ? (int)$_POST['recipient_bank_account_id'] : 0;
        $amountRaw          = trim($_POST['amount'] ?? '');
        $amount             = (float)str_replace([',', ' '], '', $amountRaw);
        $date               = trim($_POST['transfer_date'] ?? '');
        $method             = trim($_POST['disbursement_method'] ?? 'RTGS');
        $reference          = trim($_POST['reference'] ?? '');
        $purpose            = trim($_POST['purpose'] ?? '');

        // Normalise method
        if (!in_array($method, ['RTGS', 'ChequeSLIPS'])) {
            $method = (strpos($method, 'RTGS') !== false) ? 'RTGS' : 'ChequeSLIPS';
        }

        // If no bank account specified, fetch default core account
        if (!$bankAccId) {
            $core = $model->getActiveBankAccounts();
            if (!empty($core)) {
                $bankAccId = (int)$core[0]->bank_account_id;
            }
        }

        $errors = [];

        if (!$zoneId) {
            $errors['zone_id'] = 'Target Zonal Office is required.';
        } else {
            // Validate that the target zone has a verified active recipient account
            $verifiedRecipient = $model->getVerifiedAccountForZone($zoneId);
            if (!$verifiedRecipient) {
                $errors['zone_id'] = 'No verified bank account registered for this zone. Transfers cannot be authorized without a registered recipient account.';
            } else {
                $recipientBankAccId = (int)$verifiedRecipient->bank_account_id;
            }
        }
        if (!$bankAccId) {
            $errors['bank_account_id'] = 'Source bank account is required.';
        }
        if ($amount <= 0) {
            $errors['amount'] = 'Please enter a valid disbursement amount greater than 0.';
        }
        if (empty($date) || !strtotime($date)) {
            $errors['transfer_date'] = 'A valid transfer date is required.';
        }
        if (empty($purpose)) {
            $errors['purpose'] = 'Purpose and description is required.';
        }

        if (!empty($errors)) {
            if ($isJson) {
                header('Content-Type: application/json');
                http_response_code(422);
                echo json_encode(['success' => false, 'errors' => $errors]);
                exit();
            }
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_old']    = $_POST;
            $this->redirect('fundtransfer');
        }

        // 3. Auto-generate or validate reference
        if (empty($reference)) {
            $reference = $model->generateReferenceNumber($method);
        }

        try {
            // 4. Persist FundAllocation and generate paired LedgerEntries
            $newId = $model->createTransfer([
                'target_zonal_id'      => $zoneId,
                'source_account_id'    => $bankAccId,
                'recipient_account_id' => $recipientBankAccId,
                'amount'               => $amount,
                'transfer_date'        => $date,
                'reference_no'         => $reference,
                'disbursement_method'  => $method,
                'purpose_description'  => $purpose,
                'status'               => 'Processing',
                'authorized_by'        => $userId,
            ]);

            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            if ($isJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success'     => true,
                    'transfer_id' => $newId,
                    'reference'   => $reference,
                    'message'     => 'Fund allocation authorized successfully. Ledger entries generated.',
                    'redirect'    => ROOT . '/fundtransfer',
                ]);
                exit();
            }

            $this->redirect('fundtransfer');

        } catch (Exception $e) {
            if ($isJson) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Database transaction failed: ' . $e->getMessage()]);
                exit();
            }
            $_SESSION['form_errors'] = ['general' => 'Transfer authorization failed: ' . $e->getMessage()];
            $_SESSION['form_old']    = $_POST;
            $this->redirect('fundtransfer');
        }
    }

    // ---------------------------------------------------------------
    // GET REFERENCE: Fetch next sequential reference via AJAX
    // ---------------------------------------------------------------
    public function getreference() {
        $this->requireNYSCAdmin();

        $method = trim($_GET['method'] ?? 'RTGS');
        $model  = $this->model('FundTransferModel');
        $ref    = $model->generateReferenceNumber($method);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'reference' => $ref]);
        exit();
    }

    // ---------------------------------------------------------------
    // EXPORT LEDGER: Download CSV of fund transfers
    // ---------------------------------------------------------------
    public function exportledger() {
        $this->requireNYSCAdmin();

        $model = $this->model('FundTransferModel');

        $filters = [
            'search'    => trim($_GET['search']   ?? ''),
            'zone_id'   => !empty($_GET['zone_id'])  ? (int)$_GET['zone_id']  : null,
            'status'    => trim($_GET['status']   ?? 'All'),
            'quarter'   => trim($_GET['quarter']  ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to']   ?? ''),
        ];

        $transfers = $model->getTransfers($filters);

        $filename = 'NYSC_Fund_Disbursements_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, [
            'Allocation ID',
            'Date',
            'Reference No',
            'Target Zone',
            'Province / Hub',
            'Disbursement Method',
            'Bank',
            'Account Number',
            'Amount (LKR)',
            'Status',
            'Purpose / Description',
            'Authorized By'
        ]);

        foreach ($transfers as $t) {
            $hubInfo = trim(($t->target_province ?? '') . ($t->target_hub_name ? ' - ' . $t->target_hub_name : ''));
            fputcsv($output, [
                $t->allocation_id,
                $t->transfer_date,
                $t->reference_no,
                $t->target_zone_name,
                $hubInfo,
                ($t->disbursement_method === 'RTGS') ? 'BOC Direct / RTGS' : 'Cheque / SLIPS',
                $t->bank_name,
                $t->account_number,
                number_format((float)$t->amount, 2, '.', ''),
                $t->status,
                $t->purpose_description,
                $t->authorized_by_name ?? 'NYSC Admin',
            ]);
        }

        fclose($output);
        exit();
    }

    // ---------------------------------------------------------------
    // RECEIPT: Printable / downloadable official receipt
    // ---------------------------------------------------------------
    public function receipt($id = null) {
        $this->requireNYSCAdmin();

        $transferId = (int)$id;
        if (!$transferId) {
            $this->redirect('fundtransfer');
        }

        $model    = $this->model('FundTransferModel');
        $transfer = $model->findById($transferId);

        if (!$transfer) {
            $this->redirect('fundtransfer');
        }

        $this->view('fundtransfer/receipt', [
            'transfer' => $transfer,
            'title'    => 'Official Disbursement Receipt — ' . $transfer->reference_no,
        ]);
    }

    // ---------------------------------------------------------------
    // ACCOUNTS: Zonal Bank Accounts Management Sub-Page
    // ---------------------------------------------------------------
    public function accounts() {
        $this->requireNYSCAdmin();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $model    = $this->model('FundTransferModel');
        $accounts = $model->getAllZonalBankAccounts();
        $zones    = $model->getAllZones();

        $this->view('fundtransfer/accounts', [
            'title'           => 'Manage Zonal Bank Accounts — YouthNexus',
            'pageTitle'       => 'Zonal Treasury Receiving Accounts',
            'pageDescription' => 'Configure verified receiving commercial bank accounts for provincial zonal offices.',
            'currentRoute'    => 'fundtransfer',
            'userRole'        => $_SESSION['user_role'] ?? 'NYSCAdministrator',
            'userName'        => $_SESSION['user_name'] ?? 'N. Fernando',
            'userDesignation' => 'Div. Secretary',
            'accounts'        => $accounts,
            'zones'           => $zones,
            'csrf_token'      => $_SESSION['csrf_token'],
            'flashSuccess'    => $_SESSION['flash_success'] ?? null,
            'flashError'      => $_SESSION['flash_error'] ?? null,
            'flashErrors'     => $_SESSION['flash_errors'] ?? [],
            'flashOld'        => $_SESSION['flash_old'] ?? [],
        ]);

        unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['flash_errors'], $_SESSION['flash_old']);
    }

    // ---------------------------------------------------------------
    // GET ZONAL ACCOUNT: Fetch verified recipient account for a zone via AJAX
    // ---------------------------------------------------------------
    public function getzonalaccount() {
        $this->requireNYSCAdmin();

        $zoneId = (int)($_GET['zone_id'] ?? 0);
        $model  = $this->model('FundTransferModel');
        $acc    = $model->getVerifiedAccountForZone($zoneId);

        header('Content-Type: application/json');
        if (!$acc) {
            echo json_encode([
                'success' => true,
                'found'   => false,
                'message' => 'No verified bank account registered for this zone',
            ]);
            exit();
        }

        $clean = preg_replace('/[^\d]/', '', $acc->account_number);
        $masked = strlen($clean) >= 4 ? '****-' . substr($clean, -4) : '****-' . substr($acc->account_number, -4);

        echo json_encode([
            'success' => true,
            'found'   => true,
            'account' => [
                'bank_account_id'     => (int)$acc->bank_account_id,
                'bank_name'           => $acc->bank_name,
                'branch_name'         => $acc->branch_name,
                'account_number'      => $acc->account_number,
                'masked_account'      => $masked,
                'account_label'       => $acc->account_label,
                'gateway_type'        => $acc->gateway_type,
                'verification_status' => $acc->verification_status,
                'status'              => $acc->status,
                'zone_name'           => $acc->zonal_name,
            ],
        ]);
        exit();
    }

    // ---------------------------------------------------------------
    // CREATE ACCOUNT: Register new zonal bank account
    // ---------------------------------------------------------------
    public function createaccount() {
        $this->requireNYSCAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('fundtransfer/accounts');
        }

        $isJson = $this->isJsonRequest();

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            if ($isJson) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Security token expired. Please refresh the page.']);
                exit();
            }
            $_SESSION['flash_error'] = 'Security token expired. Please try again.';
            $this->redirect('fundtransfer/accounts');
        }

        $model = $this->model('FundTransferModel');

        $zoneId         = (int)($_POST['zone_id'] ?? 0);
        $bankName       = trim($_POST['bank_name'] ?? '');
        $branchName     = trim($_POST['branch_name'] ?? '');
        $accountNumber  = trim($_POST['account_number'] ?? '');
        $accountLabel   = trim($_POST['account_label'] ?? '');
        $gatewayType    = trim($_POST['gateway_type'] ?? 'SLIPS');
        $verification   = trim($_POST['verification_status'] ?? 'Verified');

        $errors = [];
        if (!$zoneId) $errors['zone_id'] = 'Target Zonal Office is required.';
        if (empty($bankName)) $errors['bank_name'] = 'Bank name is required.';
        if (empty($branchName)) $errors['branch_name'] = 'Branch name is required.';
        if (empty($accountNumber)) $errors['account_number'] = 'Account number is required.';
        if (empty($accountLabel)) $errors['account_label'] = 'Account label / beneficiary title is required.';

        if (!empty($errors)) {
            if ($isJson) {
                header('Content-Type: application/json');
                http_response_code(422);
                echo json_encode(['success' => false, 'errors' => $errors]);
                exit();
            }
            $_SESSION['flash_errors'] = $errors;
            $_SESSION['flash_old']    = $_POST;
            $this->redirect('fundtransfer/accounts');
        }

        $newId = $model->createZonalBankAccount([
            'zone_id'             => $zoneId,
            'bank_name'           => $bankName,
            'branch_name'         => $branchName,
            'account_number'      => $accountNumber,
            'account_label'       => $accountLabel,
            'gateway_type'        => in_array($gatewayType, ['RTGS', 'SLIPS', 'API', 'Cheque']) ? $gatewayType : 'SLIPS',
            'verification_status' => in_array($verification, ['Verified', 'Unverified']) ? $verification : 'Verified',
        ]);

        $_SESSION['flash_success'] = "Zonal receiving account for {$bankName} registered successfully.";

        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'id'      => $newId,
                'message' => 'Account registered successfully.',
                'redirect'=> ROOT . '/fundtransfer/accounts',
            ]);
            exit();
        }

        $this->redirect('fundtransfer/accounts');
    }

    // ---------------------------------------------------------------
    // EDIT ACCOUNT: Update existing zonal bank account
    // ---------------------------------------------------------------
    public function editaccount() {
        $this->requireNYSCAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('fundtransfer/accounts');
        }

        $isJson = $this->isJsonRequest();

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            if ($isJson) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Security token expired.']);
                exit();
            }
            $_SESSION['flash_error'] = 'Security token expired.';
            $this->redirect('fundtransfer/accounts');
        }

        $model   = $this->model('FundTransferModel');
        $id      = (int)($_POST['bank_account_id'] ?? 0);
        $account = $model->getBankAccountById($id);

        if (!$account || $account->owner_level !== 'Zonal') {
            if ($isJson) {
                header('Content-Type: application/json');
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Account not found.']);
                exit();
            }
            $_SESSION['flash_error'] = 'Account record not found.';
            $this->redirect('fundtransfer/accounts');
        }

        $zoneId         = (int)($_POST['zone_id'] ?? $account->owner_id);
        $bankName       = trim($_POST['bank_name'] ?? '');
        $branchName     = trim($_POST['branch_name'] ?? '');
        $accountNumber  = trim($_POST['account_number'] ?? '');
        $accountLabel   = trim($_POST['account_label'] ?? '');
        $gatewayType    = trim($_POST['gateway_type'] ?? 'SLIPS');
        $verification   = trim($_POST['verification_status'] ?? 'Verified');

        $errors = [];
        if (!$zoneId) $errors['zone_id'] = 'Target Zonal Office is required.';
        if (empty($bankName)) $errors['bank_name'] = 'Bank name is required.';
        if (empty($branchName)) $errors['branch_name'] = 'Branch name is required.';
        if (empty($accountNumber)) $errors['account_number'] = 'Account number is required.';
        if (empty($accountLabel)) $errors['account_label'] = 'Account label is required.';

        if (!empty($errors)) {
            if ($isJson) {
                header('Content-Type: application/json');
                http_response_code(422);
                echo json_encode(['success' => false, 'errors' => $errors]);
                exit();
            }
            $_SESSION['flash_errors'] = $errors;
            $this->redirect('fundtransfer/accounts');
        }

        $model->updateZonalBankAccount($id, [
            'zone_id'             => $zoneId,
            'bank_name'           => $bankName,
            'branch_name'         => $branchName,
            'account_number'      => $accountNumber,
            'account_label'       => $accountLabel,
            'gateway_type'        => in_array($gatewayType, ['RTGS', 'SLIPS', 'API', 'Cheque']) ? $gatewayType : 'SLIPS',
            'verification_status' => in_array($verification, ['Verified', 'Unverified']) ? $verification : 'Verified',
        ]);

        $_SESSION['flash_success'] = "Account details for {$bankName} updated successfully.";

        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Account updated successfully.',
                'redirect'=> ROOT . '/fundtransfer/accounts',
            ]);
            exit();
        }

        $this->redirect('fundtransfer/accounts');
    }

    // ---------------------------------------------------------------
    // TOGGLE STATUS: Deactivate / Activate account (soft remove)
    // ---------------------------------------------------------------
    public function toggleaccountstatus($id = null) {
        $this->requireNYSCAdmin();

        $accountId = (int)($id ?: ($_POST['bank_account_id'] ?? 0));
        if (!$accountId) {
            $this->redirect('fundtransfer/accounts');
        }

        $model = $this->model('FundTransferModel');
        $account = $model->getBankAccountById($accountId);

        if (!$account) {
            $_SESSION['flash_error'] = 'Bank account not found.';
            $this->redirect('fundtransfer/accounts');
        }

        $model->toggleAccountStatus($accountId);
        $newStatus = ($account->status === 'Active') ? 'Deactivated' : 'Reactivated';
        $_SESSION['flash_success'] = "Account for {$account->zonal_name} was successfully {$newStatus}. Existing transfer records remain intact.";

        if ($this->isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "Account {$newStatus} successfully.",
                'redirect'=> ROOT . '/fundtransfer/accounts',
            ]);
            exit();
        }

        $this->redirect('fundtransfer/accounts');
    }
}
