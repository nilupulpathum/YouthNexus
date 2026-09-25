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

    private function isJsonRequest(): bool {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        return strpos($accept, 'application/json') !== false || $requestedWith === 'xmlhttprequest';
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
            'nextReference' => $allocationModel->generateReference('RTGS'),
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
            if ($this->isJsonRequest()) {
                $this->json(['success' => false, 'error' => 'The request could not be verified. Refresh the page and try again.'], 400);
            }
            $this->setFlash('error', 'The request could not be verified. Please try again.');
            $this->redirect('divisionalallocations');
        }

        $data = $this->validatedAllocation();
        if (!$data) {
            if ($this->isJsonRequest()) {
                $this->json(['success' => false, 'error' => 'Enter a valid club, amount, category, date, method, and purpose.'], 422);
            }
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
            $allocationId = $model->createAllocation(
                $divisionId,
                (int) $ledger->ledger_id,
                (int) $_SESSION['user_id'],
                $data
            );
            if ($this->isJsonRequest()) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $this->json([
                    'success' => true,
                    'allocation_id' => $allocationId,
                    'message' => 'Funds allocated and both ledgers were updated.',
                    'redirect' => ROOT . '/divisionalallocations/details/' . $allocationId,
                ]);
            }
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
            if ($this->isJsonRequest()) {
                $this->json(['success' => false, 'error' => $message], 422);
            }
            $this->setFlash('error', $message);
        }

        $this->redirect('divisionalallocations');
    }

    public function details($allocationId = null): void {
        $this->requireTreasurer();
        $allocation = $this->getScopedAllocation((int) $allocationId);
        $this->view('divisionalallocations/details', [
            'allocation' => $allocation,
            'userName' => $_SESSION['user_name'] ?? 'Divisional Treasurer',
            'userRole' => $_SESSION['user_role'],
            'userEmail' => $_SESSION['user_email'] ?? '',
        ]);
    }

    public function receipt($allocationId = null): void {
        $this->requireTreasurer();
        $this->view('divisionalallocations/receipt', [
            'allocation' => $this->getScopedAllocation((int) $allocationId),
        ]);
    }

    public function getreference(): void {
        $this->requireTreasurer();
        $method = (string) ($_GET['method'] ?? 'RTGS');
        if (!in_array($method, ['RTGS', 'ChequeSLIPS'], true)) {
            $method = 'RTGS';
        }
        $this->json([
            'success' => true,
            'reference' => $this->model('DivisionalAllocationModel')->generateReference($method),
        ]);
    }

    public function exportledger(): void {
        $this->requireTreasurer();
        $divisionId = (int) $_SESSION['division_id'];
        $rows = $this->model('DivisionalAllocationModel')->getHistory($divisionId);
        $filename = 'Divisional_Fund_Allocations_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        if ($output === false) {
            http_response_code(500);
            exit('The allocation ledger could not be exported.');
        }
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['Reference', 'Club', 'Amount (LKR)', 'Category', 'Transfer Date', 'Method', 'Status', 'Purpose', 'Authorized By']);
        foreach ($rows as $row) {
            fputcsv($output, [
                $row->reference_no,
                $row->club_name,
                number_format((float) $row->amount, 2, '.', ''),
                $row->fund_category ?: 'Uncategorised',
                $row->transfer_date,
                $row->disbursement_method === 'RTGS' ? 'Bank Transfer / RTGS' : 'Cheque / SLIPS',
                $row->status,
                $row->purpose_description,
                trim((string) ($row->authorized_by_name ?? '')) ?: 'Divisional Treasurer',
            ]);
        }
        fclose($output);
        exit();
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

    private function getScopedAllocation(int $allocationId) {
        if ($allocationId < 1) {
            $this->redirect('divisionalallocations');
        }
        $allocation = $this->model('DivisionalAllocationModel')->findByIdForDivision(
            (int) $_SESSION['division_id'],
            $allocationId
        );
        if (!$allocation) {
            http_response_code(404);
            exit('Fund allocation not found in your division.');
        }
        return $allocation;
    }

    private function json(array $payload, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit();
    }
}
