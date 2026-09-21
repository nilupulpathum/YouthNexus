<?php

class Divisionalaudits extends Controller {
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
        $_SESSION['divisional_audit_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array {
        $flash = $_SESSION['divisional_audit_flash'] ?? null;
        unset($_SESSION['divisional_audit_flash']);
        return is_array($flash) ? $flash : null;
    }

    public function index(): void {
        $this->requireTreasurer();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $divisionId = (int) $_SESSION['division_id'];
        $model = $this->model('DivisionalAuditModel');
        $division = $this->model('DivisionalLedgerModel')->getDivision($divisionId);
        if (!$division) {
            http_response_code(500);
            exit('The division profile could not be loaded.');
        }

        $this->view('divisionalaudits/index', [
            'division' => $division,
            'summary' => $model->getSummary($divisionId),
            'queue' => $model->getQueue($divisionId),
            'clubs' => $model->getClubs($divisionId),
            'csrfToken' => $_SESSION['csrf_token'],
            'flash' => $this->pullFlash(),
            'userName' => $_SESSION['user_name'] ?? 'Divisional Treasurer',
            'userRole' => $_SESSION['user_role'],
            'userEmail' => $_SESSION['user_email'] ?? '',
        ]);
    }

    public function start(): void {
        $this->requireTreasurer();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'The request could not be verified. Please try again.');
            $this->redirect('divisionalaudits');
        }

        $data = $this->validatedPeriod();
        if (!$data) {
            $this->setFlash('error', 'Select a club, audit type, and valid audit period.');
            $this->redirect('divisionalaudits');
        }
        try {
            $auditId = $this->model('DivisionalAuditModel')->startAudit(
                (int) $_SESSION['division_id'],
                (int) $_SESSION['user_id'],
                $data
            );
            $this->setFlash('success', 'The club audit was started.');
            $this->redirect('divisionalaudits/review/' . $auditId);
        } catch (Throwable $exception) {
            $allowed = [
                'This club already has an open audit.',
                'Select a club with an active ledger from your division.',
            ];
            $this->setFlash('error', in_array($exception->getMessage(), $allowed, true)
                ? $exception->getMessage()
                : 'The club audit could not be started.');
            $this->redirect('divisionalaudits');
        }
    }

    public function review($auditId = null): void {
        $this->requireTreasurer();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $model = $this->model('DivisionalAuditModel');
        $audit = $model->getAudit((int) $_SESSION['division_id'], (int) $auditId);
        if (!$audit) {
            http_response_code(404);
            exit('The audit record was not found.');
        }
        $this->view('divisionalaudits/review', [
            'audit' => $audit,
            'entries' => $model->getEntries((int) $_SESSION['division_id'], (int) $auditId),
            'csrfToken' => $_SESSION['csrf_token'],
            'flash' => $this->pullFlash(),
            'userName' => $_SESSION['user_name'] ?? 'Divisional Treasurer',
            'userRole' => $_SESSION['user_role'],
            'userEmail' => $_SESSION['user_email'] ?? '',
        ]);
    }

    public function refresh($auditId = null): void {
        $this->requireTreasurer();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf() || (int) $auditId < 1) {
            $this->setFlash('error', 'The request could not be verified. Please try again.');
            $this->redirect('divisionalaudits');
        }
        try {
            $this->model('DivisionalAuditModel')->refreshAudit(
                (int) $_SESSION['division_id'],
                (int) $auditId
            );
            $this->setFlash('success', 'The audit totals and receipt checks were refreshed.');
        } catch (Throwable $exception) {
            $message = $exception->getMessage() === 'Completed audits cannot be recalculated.'
                ? $exception->getMessage()
                : 'The audit could not be refreshed.';
            $this->setFlash('error', $message);
        }
        $this->redirect('divisionalaudits/review/' . (int) $auditId);
    }

    public function note($auditId = null): void {
        $this->requireTreasurer();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf() || (int) $auditId < 1) {
            $this->setFlash('error', 'The request could not be verified. Please try again.');
            $this->redirect('divisionalaudits');
        }
        $reason = trim((string) ($_POST['reason'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $notesLength = function_exists('mb_strlen') ? mb_strlen($notes) : strlen($notes);
        $validReasons = ['Missing Receipt', 'Balance Difference', 'Incorrect Entry', 'Other'];
        if (!in_array($reason, $validReasons, true) || $notes === '' || $notesLength > 2000) {
            $this->setFlash('error', 'Select a reason and enter audit notes.');
            $this->redirect('divisionalaudits/review/' . (int) $auditId);
        }
        try {
            $this->model('DivisionalAuditModel')->sendAuditNote(
                (int) $_SESSION['division_id'],
                (int) $auditId,
                $reason,
                $notes
            );
            $this->setFlash('success', 'The audit note was sent to the club treasurer.');
        } catch (Throwable $exception) {
            $allowed = [
                'Completed audits cannot receive audit notes.',
                'The club does not have an active treasurer.',
            ];
            $this->setFlash('error', in_array($exception->getMessage(), $allowed, true)
                ? $exception->getMessage()
                : 'The audit note could not be sent.');
        }
        $this->redirect('divisionalaudits/review/' . (int) $auditId);
    }

    public function complete($auditId = null): void {
        $this->requireTreasurer();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf() || (int) $auditId < 1) {
            $this->setFlash('error', 'The request could not be verified. Please try again.');
            $this->redirect('divisionalaudits');
        }
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $exportReport = (string) ($_POST['export_report'] ?? 'none');
        $notesLength = function_exists('mb_strlen') ? mb_strlen($notes) : strlen($notes);
        if ($notesLength > 2000) {
            $this->setFlash('error', 'Audit notes cannot exceed 2000 characters.');
            $this->redirect('divisionalaudits/review/' . (int) $auditId);
        }
        try {
            $this->model('DivisionalAuditModel')->completeAudit(
                (int) $_SESSION['division_id'],
                (int) $auditId,
                (int) $_SESSION['user_id'],
                $notes
            );
            $this->setFlash('success', 'The club audit was marked as completed.');
            if ($exportReport === 'csv') {
                $this->redirect('divisionalaudits/export/' . (int) $auditId);
            }
        } catch (Throwable $exception) {
            $allowed = [
                'This audit is already completed.',
                'Resolve the audit difference and open flags before completion.',
            ];
            $this->setFlash('error', in_array($exception->getMessage(), $allowed, true)
                ? $exception->getMessage()
                : 'The audit could not be completed.');
        }
        $this->redirect('divisionalaudits/review/' . (int) $auditId);
    }

    public function export($auditId = null): void {
        $this->requireTreasurer();
        $auditId = (int) $auditId;
        $model = $this->model('DivisionalAuditModel');
        $audit = $model->getAudit((int) $_SESSION['division_id'], $auditId);
        if (!$audit) {
            http_response_code(404);
            exit('The audit record was not found.');
        }
        $entries = $model->getEntries((int) $_SESSION['division_id'], $auditId);
        $filename = 'club-audit-' . $auditId . '-' . date('Ymd') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['YouthNexus Club Finance Audit']);
        fputcsv($output, ['Club', $audit->club_name]);
        fputcsv($output, ['Audit Type', $audit->audit_type]);
        fputcsv($output, ['Period', $audit->period_start . ' to ' . $audit->period_end]);
        fputcsv($output, ['Status', $audit->audit_status]);
        fputcsv($output, ['Opening Balance', number_format((float) $audit->opening_balance, 2, '.', '')]);
        fputcsv($output, ['Total Income', number_format((float) $audit->total_income, 2, '.', '')]);
        fputcsv($output, ['Total Expenses', number_format((float) $audit->total_expenses, 2, '.', '')]);
        fputcsv($output, ['Expected Closing', number_format((float) $audit->expected_closing_balance, 2, '.', '')]);
        fputcsv($output, ['Actual Closing', number_format((float) $audit->actual_closing_balance, 2, '.', '')]);
        fputcsv($output, []);
        fputcsv($output, ['Date', 'Reference', 'Type', 'Category', 'Description', 'Amount', 'Status', 'Receipt']);
        foreach ($entries as $entry) {
            fputcsv($output, [
                $entry->date,
                $entry->reference_no,
                $entry->type,
                $entry->category,
                $entry->description,
                number_format((float) $entry->amount, 2, '.', ''),
                $entry->status,
                $entry->attachment_url ? 'Attached' : 'Missing',
            ]);
        }
        fclose($output);
        exit;
    }

    private function validatedPeriod(): ?array {
        $clubId = filter_var($_POST['club_id'] ?? null, FILTER_VALIDATE_INT);
        $auditType = (string) ($_POST['audit_type'] ?? '');
        $start = trim((string) ($_POST['period_start'] ?? ''));
        $end = trim((string) ($_POST['period_end'] ?? ''));
        $startDate = DateTime::createFromFormat('Y-m-d', $start);
        $endDate = DateTime::createFromFormat('Y-m-d', $end);
        if ($clubId === false || $clubId < 1
            || !in_array($auditType, ['Weekly', 'BiWeekly', 'Monthly'], true)
            || !$startDate || $startDate->format('Y-m-d') !== $start
            || !$endDate || $endDate->format('Y-m-d') !== $end
            || $start > $end) {
            return null;
        }
        return [
            'club_id' => (int) $clubId,
            'audit_type' => $auditType,
            'period_start' => $start,
            'period_end' => $end,
        ];
    }
}
