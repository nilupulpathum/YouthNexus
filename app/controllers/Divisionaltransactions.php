<?php

class Divisionaltransactions extends Controller {
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
        $_SESSION['divisional_transaction_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array {
        $flash = $_SESSION['divisional_transaction_flash'] ?? null;
        unset($_SESSION['divisional_transaction_flash']);
        return is_array($flash) ? $flash : null;
    }

    private function validatedEntry(): ?array {
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
            return null;
        }

        return [
            'type' => $type,
            'amount' => round((float) $amount, 2),
            'category' => $category,
            'description' => $description,
            'date' => $date,
        ];
    }

    public function index(): void {
        $this->requireTreasurer();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $model = $this->model('DivisionalLedgerModel');
        $division = $model->getDivision((int) $_SESSION['division_id']);
        $ledger = $model->ensureDivisionLedger((int) $_SESSION['division_id']);
        if (!$division || !$ledger) {
            http_response_code(500);
            exit('The division finance profile could not be loaded.');
        }

        $this->view('divisionaltransactions/index', [
            'division' => $division,
            'summary' => $model->getSummary((int) $ledger->ledger_id),
            'transactions' => $model->getEntries((int) $ledger->ledger_id),
            'categories' => $model->getCategories((int) $ledger->ledger_id),
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
            $this->redirect('divisionaltransactions');
        }

        $data = $this->validatedEntry();
        if (!$data) {
            $this->setFlash('error', 'Enter a valid type, amount, date, category, and description.');
            $this->redirect('divisionaltransactions');
        }

        $attachmentUrl = null;
        try {
            $attachmentUrl = FinanceReceipt::store($_FILES['receipt'] ?? null);
            $model = $this->model('DivisionalLedgerModel');
            $ledger = $model->ensureDivisionLedger((int) $_SESSION['division_id']);
            $data['attachment_url'] = $attachmentUrl;
            $data['reconciled'] = 0;
            $model->createEntry((int) $ledger->ledger_id, (int) $_SESSION['user_id'], $data);
            $this->setFlash('success', 'Transaction saved and the division balance was updated.');
        } catch (Throwable $exception) {
            FinanceReceipt::remove($attachmentUrl);
            $this->setFlash('error', $exception instanceof InvalidArgumentException
                ? $exception->getMessage()
                : 'The transaction could not be saved.');
        }

        $this->redirect('divisionaltransactions');
    }

    public function update($entryId = null): void {
        $this->requireTreasurer();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'The request could not be verified. Please try again.');
            $this->redirect('divisionaltransactions');
        }

        $data = $this->validatedEntry();
        if (!$data || (int) $entryId < 1) {
            $this->setFlash('error', 'Enter a valid type, amount, date, category, and description.');
            $this->redirect('divisionaltransactions');
        }

        $newAttachment = null;
        try {
            $model = $this->model('DivisionalLedgerModel');
            $ledger = $model->ensureDivisionLedger((int) $_SESSION['division_id']);
            $entry = $model->getEntry((int) $ledger->ledger_id, (int) $entryId);
            if (!$entry || $entry->status !== 'Approved' || (int) $entry->has_pending_void === 1) {
                throw new RuntimeException('This transaction cannot be edited.');
            }

            $newAttachment = FinanceReceipt::store($_FILES['receipt'] ?? null);
            $removeReceipt = isset($_POST['remove_receipt']) && $_POST['remove_receipt'] === '1';
            $data['attachment_url'] = $newAttachment ?: ($removeReceipt ? null : $entry->attachment_url);
            $oldAttachment = $model->updateEntry((int) $ledger->ledger_id, (int) $entryId, $data);

            if (($newAttachment || $removeReceipt) && $oldAttachment !== $data['attachment_url']) {
                FinanceReceipt::remove($oldAttachment);
            }
            $this->setFlash('success', 'Transaction updated and the division balance was recalculated.');
        } catch (Throwable $exception) {
            FinanceReceipt::remove($newAttachment);
            $message = $exception instanceof InvalidArgumentException
                ? $exception->getMessage()
                : ($exception->getMessage() === 'This transaction cannot be edited.'
                    ? $exception->getMessage()
                    : 'The transaction could not be updated.');
            $this->setFlash('error', $message);
        }

        $this->redirect('divisionaltransactions');
    }
}
