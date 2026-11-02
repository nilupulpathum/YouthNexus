<?php

class Divisionalallocations extends Controller {
    private function requireTreasurer(): void {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        if (($_SESSION['user_role'] ?? '') !== 'DivisionalTreasurer') {
            $this->redirect('home');
        }
        if ((int) ($_SESSION['division_id'] ?? 0) < 1) {
            http_response_code(403);
            exit('Your user account is not assigned to a division.');
        }
    }

    private function verifyCsrf(): bool {
        $token = (string) ($_POST['csrf_token'] ?? '');
        return $token !== '' && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
    }

    private function setFlash(string $type, string $message): void {
        $_SESSION['divisional_allocation_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array {
        $flash = $_SESSION['divisional_allocation_flash'] ?? null;
        unset($_SESSION['divisional_allocation_flash']);
        return is_array($flash) ? $flash : null;
    }

    public function index(): void {
        $this->requireTreasurer();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $divisionId = (int) $_SESSION['division_id'];
        $ledgerModel = $this->model('DivisionalLedgerModel');
        $allocationModel = $this->model('DivisionalAllocationModel');
        $division = $ledgerModel->getDivision($divisionId);
        $ledger = $ledgerModel->ensureDivisionLedger($divisionId);
        if (!$division || !$ledger) {
            http_response_code(500);
            exit('The division finance profile could not be loaded.');
        }

        $this->view('divisionalallocations/index', [
            'division' => $division,
            'summary' => $allocationModel->getSummary($divisionId, (int) $ledger->ledger_id),
            'clubs' => $allocationModel->getClubs($divisionId),
            'sourceAccount' => $allocationModel->getSourceAccount($divisionId),
            'pendingRequests' => $allocationModel->getPendingRequests($divisionId),
            'history' => $allocationModel->getHistory($divisionId),
            'categories' => $allocationModel->getCategories($divisionId),
            'clubAccounts' => $allocationModel->getAllClubVerifiedAccounts($divisionId),
            'nextReference' => $allocationModel->generateReference(),
            'csrfToken' => $_SESSION['csrf_token'],
            'flash' => $this->pullFlash(),
            'userName' => $_SESSION['user_name'] ?? 'Divisional Treasurer',
            'userRole' => $_SESSION['user_role'],
            'userEmail' => $_SESSION['user_email'] ?? '',
        ]);
    }

    public function create(): void {
        $this->requireTreasurer();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'The request could not be verified. Please try again.');
            $this->redirect('divisionalallocations');
        }

        $data = $this->validatedAllocation();
        if (!$data) {
            $this->setFlash('error', 'Enter a valid club, amount, category, date, method, and purpose.');
            $this->redirect('divisionalallocations');
        }

        try {
            $divisionId = (int) $_SESSION['division_id'];
            $ledger = $this->model('DivisionalLedgerModel')->ensureDivisionLedger($divisionId);
            $model = $this->model('DivisionalAllocationModel');
            $account = $model->getSourceAccount($divisionId);
            if (!$account) {
                throw new RuntimeException('The division bank account is not available.');
            }
            $data['bank_account_id'] = (int) $account->bank_account_id;
            $model->createAllocation(
                $divisionId,
                (int) $ledger->ledger_id,
                (int) $_SESSION['user_id'],
                $data
            );
            $this->setFlash('success', 'Funds allocated and both ledgers were updated.');
        } catch (Throwable $exception) {
            $allowed = [
                'The division ledger does not have enough funds for this allocation.',
                'Select an eligible club from your division.',
                'The division bank account is not available.',
            ];
            $message = in_array($exception->getMessage(), $allowed, true)
                ? $exception->getMessage()
                : 'The allocation could not be completed.';
            $this->setFlash('error', $message);
        }

        $this->redirect('divisionalallocations');
    }

    public function decide($requestId = null): void {
        $this->requireTreasurer();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf() || (int) $requestId < 1) {
            $this->setFlash('error', 'The request could not be verified. Please try again.');
            $this->redirect('divisionalallocations');
        }

        $decision = (string) ($_POST['decision'] ?? '');
        if (!in_array($decision, ['approve', 'reject'], true)) {
            $this->setFlash('error', 'Select a valid decision.');
            $this->redirect('divisionalallocations');
        }

        try {
            $divisionId = (int) $_SESSION['division_id'];
            $model = $this->model('DivisionalAllocationModel');
            if ($decision === 'approve') {
                $ledger = $this->model('DivisionalLedgerModel')->ensureDivisionLedger($divisionId);
                $model->approveRequest(
                    $divisionId,
                    (int) $ledger->ledger_id,
                    (int) $requestId,
                    (int) $_SESSION['user_id']
                );
                $this->setFlash('success', 'The fund request was approved and the ledgers were updated.');
            } else {
                $model->rejectRequest($divisionId, (int) $requestId);
                $this->setFlash('success', 'The fund request was rejected.');
            }
        } catch (Throwable $exception) {
            $allowed = [
                'The division ledger does not have enough funds for this allocation.',
                'This fund request is no longer pending.',
                'This fund request has already been reviewed.',
            ];
            $message = in_array($exception->getMessage(), $allowed, true)
                ? $exception->getMessage()
                : 'The fund request could not be reviewed.';
            $this->setFlash('error', $message);
        }

        $this->redirect('divisionalallocations');
    }

    public function details($allocationId = null): void {
        $this->requireTreasurer();
        $allocationId = filter_var($allocationId, FILTER_VALIDATE_INT);
        if (!$allocationId || $allocationId < 1) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid allocation ID']);
            return;
        }

        $divisionId = (int) $_SESSION['division_id'];
        $model = $this->model('DivisionalAllocationModel');
        $details = $model->getAllocationDetails($divisionId, (int) $allocationId);

        if (!$details) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Allocation not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($details);
    }

    public function receipt($allocationId = null): void {
        $this->requireTreasurer();
        $allocationId = filter_var($allocationId, FILTER_VALIDATE_INT);
        if (!$allocationId || $allocationId < 1) {
            $this->setFlash('error', 'Invalid allocation reference.');
            $this->redirect('divisionalallocations');
        }

        $divisionId = (int) $_SESSION['division_id'];
        $model = $this->model('DivisionalAllocationModel');
        $transfer = $model->getAllocationDetails($divisionId, (int) $allocationId);

        if (!$transfer) {
            $this->setFlash('error', 'Disbursement record not found.');
            $this->redirect('divisionalallocations');
        }

        $this->view('divisionalallocations/receipt', [
            'transfer' => $transfer,
            'division' => $this->model('DivisionalLedgerModel')->getDivision($divisionId),
            'receiptSubtitle' => 'Divisional Secretariat — Official Club Fund Disbursement Voucher',
        ]);
    }

    private function validatedAllocation(): ?array {
        $clubId = filter_var($_POST['club_id'] ?? null, FILTER_VALIDATE_INT);
        $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
        $category = trim((string) ($_POST['fund_category'] ?? ''));
        $purpose = trim((string) ($_POST['purpose_description'] ?? ''));
        $date = trim((string) ($_POST['transfer_date'] ?? ''));
        $method = (string) ($_POST['disbursement_method'] ?? '');
        $validDate = DateTime::createFromFormat('Y-m-d', $date);
        $categoryLength = function_exists('mb_strlen') ? mb_strlen($category) : strlen($category);
        $purposeLength = function_exists('mb_strlen') ? mb_strlen($purpose) : strlen($purpose);

        if ($clubId === false || $clubId < 1 || $amount === false || $amount <= 0
            || $amount > 9999999999999.99 || $category === '' || $categoryLength > 100
            || $purpose === '' || $purposeLength > 2000
            || !$validDate || $validDate->format('Y-m-d') !== $date
            || !in_array($method, ['RTGS', 'ChequeSLIPS'], true)) {
            return null;
        }

        return [
            'club_id' => (int) $clubId,
            'amount' => round((float) $amount, 2),
            'fund_category' => $category,
            'purpose_description' => $purpose,
            'transfer_date' => $date,
            'disbursement_method' => $method,
        ];
    }

    public function accounts(): void {
        $this->requireTreasurer();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $divisionId = (int) $_SESSION['division_id'];
        $allocationModel = $this->model('DivisionalAllocationModel');
        $ledgerModel = $this->model('DivisionalLedgerModel');
        $division = $ledgerModel->getDivision($divisionId);
        $accounts = $allocationModel->getAllClubBankAccounts($divisionId);
        $clubs = $allocationModel->getClubs($divisionId);

        $flash = $this->pullFlash();

        $this->view('divisionalallocations/accounts', [
            'title'           => 'Manage Club Bank Accounts — YouthNexus',
            'pageTitle'       => 'Club Receiving Accounts',
            'pageDescription' => 'Configure verified receiving commercial bank accounts for registered youth clubs.',
            'currentRoute'    => 'divisionalallocations',
            'division'        => $division,
            'accounts'        => $accounts,
            'clubs'           => $clubs,
            'csrf_token'      => $_SESSION['csrf_token'],
            'flashSuccess'    => ($flash && $flash['type'] === 'success') ? $flash['message'] : null,
            'flashError'      => ($flash && $flash['type'] === 'error') ? $flash['message'] : null,
            'userRole'        => $_SESSION['user_role'] ?? 'DivisionalTreasurer',
            'userName'        => $_SESSION['user_name'] ?? 'Theresa Perera',
        ]);
    }

    public function getclubaccount(): void {
        $this->requireTreasurer();
        $clubId = (int) ($_GET['club_id'] ?? 0);
        $allocationModel = $this->model('DivisionalAllocationModel');
        $acc = $allocationModel->getVerifiedAccountForClub($clubId);

        header('Content-Type: application/json');
        if (!$acc) {
            echo json_encode([
                'success' => true,
                'found'   => false,
                'message' => 'No verified bank account registered for this club',
            ]);
            exit();
        }

        $clean = preg_replace('/[^\d]/', '', $acc->account_number);
        $masked = strlen($clean) >= 4 ? '****-' . substr($clean, -4) : '****-' . substr($acc->account_number, -4);

        echo json_encode([
            'success' => true,
            'found'   => true,
            'account' => [
                'bank_account_id' => (int) $acc->bank_account_id,
                'bank_name'       => $acc->bank_name,
                'branch_name'     => $acc->branch_name,
                'account_number'  => $acc->account_number,
                'masked_account'  => $masked,
                'account_label'   => $acc->account_label,
                'status'          => $acc->status,
                'club_name'       => $acc->club_name,
            ],
        ]);
        exit();
    }

    public function createaccount(): void {
        $this->requireTreasurer();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('divisionalallocations/accounts');
        }

        $this->verifyCsrf();
        $allocationModel = $this->model('DivisionalAllocationModel');
        $divisionId = (int) $_SESSION['division_id'];

        $clubId = (int) ($_POST['club_id'] ?? 0);
        $bankName = trim((string) ($_POST['bank_name'] ?? ''));
        $branchName = trim((string) ($_POST['branch_name'] ?? ''));
        $accountNumber = trim((string) ($_POST['account_number'] ?? ''));
        $accountLabel = trim((string) ($_POST['account_label'] ?? ''));

        if (!$clubId || $bankName === '' || $branchName === '' || $accountNumber === '' || $accountLabel === '') {
            $this->setFlash('error', 'All fields are required to register a club receiving account.');
            $this->redirect('divisionalallocations/accounts');
        }

        // Verify club belongs to this division
        $clubs = $allocationModel->getClubs($divisionId);
        $clubIds = array_map(fn($c) => (int)$c->club_id, $clubs);
        if (!in_array($clubId, $clubIds, true)) {
            $this->setFlash('error', 'Selected club is not within your division.');
            $this->redirect('divisionalallocations/accounts');
        }

        try {
            $allocationModel->createClubBankAccount([
                'club_id'        => $clubId,
                'bank_name'      => $bankName,
                'branch_name'    => $branchName,
                'account_number' => $accountNumber,
                'account_label'  => $accountLabel,
            ]);
            $this->setFlash('success', 'Club receiving bank account registered successfully.');
        } catch (\Throwable $t) {
            $this->setFlash('error', 'Failed to register account: ' . $t->getMessage());
        }

        $this->redirect('divisionalallocations/accounts');
    }

    public function editaccount(): void {
        $this->requireTreasurer();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('divisionalallocations/accounts');
        }

        $this->verifyCsrf();
        $allocationModel = $this->model('DivisionalAllocationModel');
        $divisionId = (int) $_SESSION['division_id'];

        $bankAccountId = (int) ($_POST['bank_account_id'] ?? 0);
        $clubId = (int) ($_POST['club_id'] ?? 0);
        $bankName = trim((string) ($_POST['bank_name'] ?? ''));
        $branchName = trim((string) ($_POST['branch_name'] ?? ''));
        $accountNumber = trim((string) ($_POST['account_number'] ?? ''));
        $accountLabel = trim((string) ($_POST['account_label'] ?? ''));

        if (!$bankAccountId || !$clubId || $bankName === '' || $branchName === '' || $accountNumber === '' || $accountLabel === '') {
            $this->setFlash('error', 'All fields are required to update account details.');
            $this->redirect('divisionalallocations/accounts');
        }

        // Verify account belongs to a club in this division
        $existing = $allocationModel->getBankAccountById($bankAccountId);
        if (!$existing || (int) $existing->division_id !== $divisionId) {
            $this->setFlash('error', 'Account record not found or access denied.');
            $this->redirect('divisionalallocations/accounts');
        }

        try {
            $allocationModel->updateClubBankAccount($bankAccountId, [
                'club_id'        => $clubId,
                'bank_name'      => $bankName,
                'branch_name'    => $branchName,
                'account_number' => $accountNumber,
                'account_label'  => $accountLabel,
            ]);
            $this->setFlash('success', 'Club bank account details updated successfully.');
        } catch (\Throwable $t) {
            $this->setFlash('error', 'Failed to update account: ' . $t->getMessage());
        }

        $this->redirect('divisionalallocations/accounts');
    }

    public function toggleaccountstatus(): void {
        $this->requireTreasurer();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('divisionalallocations/accounts');
        }

        $this->verifyCsrf();
        $allocationModel = $this->model('DivisionalAllocationModel');
        $divisionId = (int) $_SESSION['division_id'];
        $bankAccountId = (int) ($_POST['bank_account_id'] ?? 0);

        $existing = $allocationModel->getBankAccountById($bankAccountId);
        if (!$existing || (int) $existing->division_id !== $divisionId) {
            $this->setFlash('error', 'Account record not found or access denied.');
            $this->redirect('divisionalallocations/accounts');
        }

        try {
            $allocationModel->toggleAccountStatus($bankAccountId);
            $newStatus = $existing->status === 'Active' ? 'deactivated' : 'reactivated';
            $this->setFlash('success', "Club bank account successfully {$newStatus}.");
        } catch (\Throwable $t) {
            $this->setFlash('error', 'Failed to toggle status: ' . $t->getMessage());
        }

        $this->redirect('divisionalallocations/accounts');
    }
}
