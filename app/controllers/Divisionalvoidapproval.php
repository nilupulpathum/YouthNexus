<?php

class Divisionalvoidapproval extends Controller {
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
        $_SESSION['divisional_void_approval_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array {
        $flash = $_SESSION['divisional_void_approval_flash'] ?? null;
        unset($_SESSION['divisional_void_approval_flash']);
        return is_array($flash) ? $flash : null;
    }

    public function index(): void {
        $this->requireTreasurer();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $divisionId = (int) $_SESSION['division_id'];
        $model = $this->model('DivisionalVoidApprovalModel');
        $division = $this->model('DivisionalLedgerModel')->getDivision($divisionId);
        if (!$division) {
            http_response_code(500);
            exit('The division finance profile could not be loaded.');
        }

        $pendingRequests = $model->getPendingRequests($divisionId);
        $this->view('divisionalvoidapproval/index', [
            'division' => $division,
            'summary' => $model->getSummary($divisionId),
            'clubs' => $model->getClubs($divisionId),
            'pendingRequests' => $pendingRequests,
            'reviewEvidence' => $model->getReviewEvidence($divisionId, $pendingRequests),
            'decidedRequests' => $model->getDecidedRequests($divisionId),
            'csrfToken' => $_SESSION['csrf_token'],
            'flash' => $this->pullFlash(),
            'userName' => $_SESSION['user_name'] ?? 'Divisional Treasurer',
            'userRole' => $_SESSION['user_role'],
            'userEmail' => $_SESSION['user_email'] ?? '',
        ]);
    }

    public function decide($requestId = null): void {
        $this->requireTreasurer();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf() || (int) $requestId < 1) {
            $this->setFlash('error', 'The request could not be verified. Please try again.');
            $this->redirect('divisionalvoidapproval');
        }

        $decision = (string) ($_POST['decision'] ?? '');
        $remarks = trim((string) ($_POST['remarks'] ?? ''));
        $remarksLength = function_exists('mb_strlen') ? mb_strlen($remarks) : strlen($remarks);
        if (!in_array($decision, ['approve', 'reject'], true)
            || $remarksLength > 1000
            || ($decision === 'reject' && $remarksLength < 5)) {
            $this->setFlash('error', 'Select a decision and provide remarks when rejecting a request.');
            $this->redirect('divisionalvoidapproval');
        }

        try {
            $this->model('DivisionalVoidApprovalModel')->decide(
                (int) $_SESSION['division_id'],
                (int) $requestId,
                (int) $_SESSION['user_id'],
                $decision,
                $remarks !== '' ? $remarks : null
            );
            $message = 'The void request was rejected.';
            if ($decision === 'approve') {
                $message = 'The void request was approved and the club ledger balance was updated.';
            }
            $this->setFlash('success', $message);
        } catch (Throwable $exception) {
            $allowed = [
                'This void request is no longer pending.',
                'The ledger entry is no longer eligible to be voided.',
                'The club ledger is not active.',
            ];
            $this->setFlash(
                'error',
                in_array($exception->getMessage(), $allowed, true)
                    ? $exception->getMessage()
                    : 'The void request decision could not be saved.'
            );
        }

        $this->redirect('divisionalvoidapproval');
    }

    public function export(): void {
        $this->requireTreasurer();
        $model = $this->model('DivisionalVoidApprovalModel');
        $divisionId = (int) $_SESSION['division_id'];
        $requests = array_merge(
            $model->getPendingRequests($divisionId),
            $model->getDecidedRequests($divisionId)
        );

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="club-void-requests-' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Request ID', 'Club', 'Ledger Entry', 'Amount', 'Type', 'Reason', 'Requested At', 'Status', 'Remarks', 'Decided At']);
        foreach ($requests as $request) {
            $safe = static function ($value): string {
                $text = (string) $value;
                return preg_match('/^[=+\-@]/', $text) ? "'" . $text : $text;
            };
            fputcsv($output, [
                'VR-' . str_pad((string) $request->void_request_id, 4, '0', STR_PAD_LEFT),
                $safe($request->club_name),
                $safe($request->reference_no),
                number_format((float) $request->amount, 2, '.', ''),
                $request->type,
                $safe($request->reason),
                $request->requested_at,
                $request->status,
                $safe($request->remarks ?? ''),
                $request->decided_at ?? '',
            ]);
        }
        fclose($output);
        exit;
    }
}
