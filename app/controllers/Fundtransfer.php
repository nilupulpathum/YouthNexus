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

        $this->view('fundtransfer/index', [
            'title'           => 'Fund Transfer — YouthNexus',
            'pageTitle'       => 'National Fund Disbursement & Zonal Ledger',
            'pageDescription' => 'Manage inter-governmental grants, RTGS clearance, and zonal treasury allocations.',
            'currentRoute'    => 'fundtransfer',
            'userRole'        => $_SESSION['user_role'] ?? 'NYSCAdministrator',
            'userName'        => $_SESSION['user_name'] ?? 'N. Fernando',
            'userDesignation' => 'Div. Secretary',
            'transfers'       => $transfers,
            'stats'           => $stats,
            'zones'           => $zones,
            'bankAccounts'    => $bankAccounts,
            'filters'         => $filters,
            'nextReference'   => $nextReference,
            'csrf_token'      => $_SESSION['csrf_token'],
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
                    'bank'        => $transfer->bank_name,
                    'branch'      => $transfer->branch_name,
                    'account'     => $transfer->account_number,
                    'account_label' => $transfer->bank_account_name,
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

        // 2. Input extraction
        $zoneId     = !empty($_POST['zone_id']) ? (int)$_POST['zone_id'] : 0;
        $bankAccId  = !empty($_POST['bank_account_id']) ? (int)$_POST['bank_account_id'] : 0;
        $amountRaw  = trim($_POST['amount'] ?? '');
        $amount     = (float)str_replace([',', ' '], '', $amountRaw);
        $date       = trim($_POST['transfer_date'] ?? '');
        $method     = trim($_POST['disbursement_method'] ?? 'RTGS');
        $reference  = trim($_POST['reference'] ?? '');
        $purpose    = trim($_POST['purpose'] ?? '');

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
                'target_zonal_id'     => $zoneId,
                'source_account_id'   => $bankAccId,
                'amount'              => $amount,
                'transfer_date'       => $date,
                'reference_no'        => $reference,
                'disbursement_method' => $method,
                'purpose_description' => $purpose,
                'status'              => 'Processing',
                'authorized_by'       => $userId,
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
}
