<?php

class Divisionalledger extends Controller {
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
        $_SESSION['divisional_ledger_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array {
        $flash = $_SESSION['divisional_ledger_flash'] ?? null;
        unset($_SESSION['divisional_ledger_flash']);
        return is_array($flash) ? $flash : null;
    }

    public function index(): void {
        $this->requireTreasurer();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $divisionId = (int) $_SESSION['division_id'];
        $model = $this->model('DivisionalLedgerModel');
        $division = $model->getDivision($divisionId);
        $ledger = $model->ensureDivisionLedger($divisionId);

        if (!$division || !$ledger) {
            http_response_code(500);
            exit('The division finance profile could not be loaded.');
        }

        $this->view('divisionalledger/index', [
            'division' => $division,
            'ledger' => $ledger,
            'summary' => $model->getSummary((int) $ledger->ledger_id),
            'entries' => $model->getEntries((int) $ledger->ledger_id),
            'reconciliation' => $model->getReconciliation((int) $ledger->ledger_id),
            'pendingActions' => $model->getPendingActions((int) $ledger->ledger_id),
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
            $this->redirect('divisionalledger');
        }

        $type = (string) ($_POST['type'] ?? '');
        $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
        $category = trim((string) ($_POST['category'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $date = trim((string) ($_POST['date'] ?? ''));
        $validDate = DateTime::createFromFormat('Y-m-d', $date);
        $categoryLength = function_exists('mb_strlen') ? mb_strlen($category) : strlen($category);
        $descriptionLength = function_exists('mb_strlen') ? mb_strlen($description) : strlen($description);

        if (!in_array($type, ['Income', 'Expense'], true) || $amount === false || $amount <= 0
            || $amount > 9999999999999.99 || $category === '' || $categoryLength > 100
            || $description === '' || $descriptionLength > 2000
            || !$validDate || $validDate->format('Y-m-d') !== $date) {
            $this->setFlash('error', 'Enter a valid type, amount, date, category, and description.');
            $this->redirect('divisionalledger');
        }

        $attachmentUrl = null;
        try {
            $attachmentUrl = FinanceReceiptStorage::store($_FILES['receipt'] ?? null);
            $model = $this->model('DivisionalLedgerModel');
            $ledger = $model->ensureDivisionLedger((int) $_SESSION['division_id']);
            $model->createEntry((int) $ledger->ledger_id, (int) $_SESSION['user_id'], [
                'type' => $type,
                'amount' => round((float) $amount, 2),
                'category' => $category,
                'description' => $description,
                'date' => $date,
                'attachment_url' => $attachmentUrl,
                'reconciled' => isset($_POST['reconciled']) ? 1 : 0,
            ]);
            $this->setFlash('success', 'Ledger entry saved and the running balance was updated.');
        } catch (Throwable $exception) {
            if ($attachmentUrl) {
                FinanceReceiptStorage::remove($attachmentUrl);
            }
            $this->setFlash('error', $exception instanceof InvalidArgumentException
                ? $exception->getMessage()
                : 'The ledger entry could not be saved.');
        }

        $this->redirect('divisionalledger');
    }

    public function reconcile($entryId = null): void {
        $this->requireTreasurer();
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid session token.']);
            return;
        }

        $model = $this->model('DivisionalLedgerModel');
        $ledger = $model->ensureDivisionLedger((int) $_SESSION['division_id']);
        $value = ($_POST['reconciled'] ?? '0') === '1';
        $updated = $model->setReconciled((int) $ledger->ledger_id, (int) $entryId, $value);
        if (!$updated) {
            http_response_code(404);
        }
        echo json_encode(['success' => $updated]);
    }

    public function export(): void {
        $this->requireTreasurer();
        $model = $this->model('DivisionalLedgerModel');
        $ledger = $model->ensureDivisionLedger((int) $_SESSION['division_id']);
        $entries = $model->getEntries((int) $ledger->ledger_id);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="division-ledger-' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date', 'Reference', 'Description', 'Category', 'Type', 'Amount (LKR)', 'Balance (LKR)', 'Status', 'Reconciled']);
        foreach ($entries as $entry) {
            fputcsv($output, [
                CsvSecurity::cell($entry->date), CsvSecurity::cell($entry->reference_no),
                CsvSecurity::cell($entry->description), CsvSecurity::cell($entry->category),
                CsvSecurity::cell($entry->type), number_format((float) $entry->amount, 2, '.', ''),
                number_format((float) $entry->running_balance, 2, '.', ''),
                CsvSecurity::cell($entry->status), (int) $entry->reconciled === 1 ? 'Yes' : 'No',
            ]);
        }
        fclose($output);
    }

}
