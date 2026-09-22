<?php

class Divisionalvoidrequest extends Controller {
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

    private function setFlash(string $type, string $message, bool $financeChanged = false): void {
        $_SESSION['divisional_void_request_flash'] = [
            'type' => $type,
            'message' => $message,
            'finance_changed' => $financeChanged,
        ];
    }

    private function pullFlash(): ?array {
        $flash = $_SESSION['divisional_void_request_flash'] ?? null;
        unset($_SESSION['divisional_void_request_flash']);
        return is_array($flash) ? $flash : null;
    }

    public function index(): void {
        $this->requireTreasurer();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $divisionId = (int) $_SESSION['division_id'];
        $ledgerModel = $this->model('DivisionalLedgerModel');
        $model = $this->model('DivisionalVoidRequestModel');
        $division = $ledgerModel->getDivision($divisionId);
        $ledger = $ledgerModel->ensureDivisionLedger($divisionId);
        if (!$division || !$ledger) {
            http_response_code(500);
            exit('The division finance profile could not be loaded.');
        }

        $this->view('divisionalvoidrequest/index', [
            'division' => $division,
            'recipient' => $model->getRecipient($divisionId),
            'summary' => $model->getSummary($divisionId),
            'entries' => $model->getEligibleEntries($divisionId),
            'requests' => $model->getRequests($divisionId),
            'currentUserId' => (int) $_SESSION['user_id'],
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
            $this->redirect('divisionalvoidrequest');
        }

        $entryId = filter_var($_POST['entry_id'] ?? null, FILTER_VALIDATE_INT);
        $reason = trim((string) ($_POST['reason'] ?? ''));
        $reasonLength = function_exists('mb_strlen') ? mb_strlen($reason) : strlen($reason);
        if ($entryId === false || $entryId < 1 || $reasonLength < 10 || $reasonLength > 1000) {
            $this->setFlash('error', 'Select a ledger entry and provide a reason between 10 and 1000 characters.');
            $this->redirect('divisionalvoidrequest');
        }

        try {
            $divisionId = (int) $_SESSION['division_id'];
            $model = $this->model('DivisionalVoidRequestModel');
            $recipient = $model->getRecipient($divisionId);
            if (!$recipient) {
                throw new RuntimeException('The Zonal Treasurer is not available.');
            }
            $model->createRequest(
                $divisionId,
                (int) $entryId,
                (int) $_SESSION['user_id'],
                (int) $recipient->user_id,
                $reason
            );
            $this->setFlash('success', 'The void request was submitted to the Zonal Treasurer.', true);
        } catch (Throwable $exception) {
            $allowed = [
                'Select an approved entry from your division ledger.',
                'The Zonal Treasurer is not available.',
                'A void request is already pending for this entry.',
            ];
            $this->setFlash(
                'error',
                in_array($exception->getMessage(), $allowed, true)
                    ? $exception->getMessage()
                    : 'The void request could not be submitted.'
            );
        }

        $this->redirect('divisionalvoidrequest');
    }

    public function withdraw($requestId = null): void {
        $this->requireTreasurer();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf() || (int) $requestId < 1) {
            $this->setFlash('error', 'The request could not be verified. Please try again.');
            $this->redirect('divisionalvoidrequest');
        }

        try {
            $this->model('DivisionalVoidRequestModel')->withdrawRequest(
                (int) $_SESSION['division_id'],
                (int) $requestId,
                (int) $_SESSION['user_id']
            );
            $this->setFlash('success', 'The pending void request was withdrawn.', true);
        } catch (Throwable $exception) {
            $allowed = [
                'Only a pending void request can be withdrawn.',
                'The void request is no longer pending.',
            ];
            $this->setFlash(
                'error',
                in_array($exception->getMessage(), $allowed, true)
                    ? $exception->getMessage()
                    : 'The void request could not be withdrawn.'
            );
        }

        $this->redirect('divisionalvoidrequest');
    }
}
