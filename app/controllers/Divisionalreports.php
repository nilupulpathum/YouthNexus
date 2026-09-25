<?php

require_once __DIR__ . '/../core/DivisionalReportPdf.php';

class Divisionalreports extends Controller {
    private function requireDivisionalReportAccess(): void {
        if (empty($_SESSION['user_id'])) $this->redirect('auth/signin');
        $allowedRoles = ['DivisionalCoordinator', 'DivisionalSecretary', 'DivisionalTreasurer'];
        if (!in_array((string) ($_SESSION['user_role'] ?? ''), $allowedRoles, true)) {
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
        $_SESSION['divisional_reports_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array {
        $flash = $_SESSION['divisional_reports_flash'] ?? null;
        unset($_SESSION['divisional_reports_flash']);
        return is_array($flash) ? $flash : null;
    }

    public function index(): void {
        $this->requireDivisionalReportAccess();
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $divisionId = (int) $_SESSION['division_id'];
        $role = (string) $_SESSION['user_role'];
        $model = $this->model('DivisionalReportModel');
        try {
            $division = $model->getDivision($divisionId);
            $reports = $model->getReports($divisionId, $role);
            $catalog = $model->getCatalog($role);
        } catch (Throwable $exception) {
            http_response_code(500);
            exit('Report data could not be loaded. Run the divisional reports migration and try again.');
        }
        $this->view('divisionalreports/index', [
            'division' => $division,
            'reports' => $reports,
            'catalog' => $catalog,
            'summary' => $model->getSummary($reports),
            'csrfToken' => $_SESSION['csrf_token'],
            'flash' => $this->pullFlash(),
            'userName' => $_SESSION['user_name'] ?? 'Divisional Officer',
            'userRole' => $_SESSION['user_role'],
            'userEmail' => $_SESSION['user_email'] ?? '',
        ]);
    }

    public function generate(): void {
        $this->requireDivisionalReportAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'The report request could not be verified. Please try again.');
            $this->redirect('divisionalreports');
        }
        $typeId = (int) ($_POST['report_type_id'] ?? 0);
        $start = trim((string) ($_POST['date_start'] ?? ''));
        $end = trim((string) ($_POST['date_end'] ?? ''));
        $format = (string) ($_POST['format'] ?? 'OnScreen');
        if (!in_array($format, ['OnScreen', 'PDF', 'CSV'], true)) $format = 'OnScreen';
        if (!$this->validDate($start) || !$this->validDate($end) || $start > $end) {
            $this->setFlash('error', 'Select a valid report period.');
            $this->redirect('divisionalreports');
        }
        try {
            $model = $this->model('DivisionalReportModel');
            $reportId = $model->createReport(
                (int) $_SESSION['division_id'], (int) $_SESSION['user_id'],
                (string) $_SESSION['user_role'], $typeId, $start, $end, $format
            );
            if ($format === 'CSV') $this->redirect('divisionalreports/export/' . $reportId);
            if ($format === 'PDF') $this->redirect('divisionalreports/pdf/' . $reportId);
            $this->redirect('divisionalreports/preview/' . $reportId);
        } catch (Throwable $exception) {
            $this->setFlash('error', 'The report could not be generated from the selected period.');
            $this->redirect('divisionalreports');
        }
    }

    public function preview($reportId = null): void {
        $this->requireDivisionalReportAccess();
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $model = $this->model('DivisionalReportModel');
        $report = $model->getReport(
            (int) $_SESSION['division_id'], (int) $reportId, (string) $_SESSION['user_role']
        );
        if (!$report) {
            $this->setFlash('error', 'The selected report was not found.');
            $this->redirect('divisionalreports');
        }
        $data = $model->getReportData((int) $_SESSION['division_id'], $report);
        $this->view('divisionalreports/preview', [
            'report' => $report,
            'reportData' => $data,
            'division' => $model->getDivision((int) $_SESSION['division_id']),
            'csrfToken' => $_SESSION['csrf_token'],
            'userName' => $_SESSION['user_name'] ?? 'Divisional Officer',
            'userRole' => $_SESSION['user_role'],
            'userEmail' => $_SESSION['user_email'] ?? '',
        ]);
    }

    public function export($reportId = null): void {
        $this->requireDivisionalReportAccess();
        $model = $this->model('DivisionalReportModel');
        $report = $model->getReport(
            (int) $_SESSION['division_id'], (int) $reportId, (string) $_SESSION['user_role']
        );
        if (!$report) {
            $this->setFlash('error', 'The selected report was not found.');
            $this->redirect('divisionalreports');
        }
        $data = $model->getReportData((int) $_SESSION['division_id'], $report);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="division-report-' . (int) $report->report_id . '.csv"');
        header('Cache-Control: private, no-store, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        $out = fopen('php://output', 'w');
        fputcsv($out, array_values($data['columns']));
        foreach ($data['rows'] as $row) {
            $values = [];
            foreach (array_keys($data['columns']) as $key) {
                $value = (string) ($row[$key] ?? '');
                $values[] = preg_match('/^[\x00-\x20]*[=+\-@]/', $value) ? "'" . $value : $value;
            }
            fputcsv($out, $values);
        }
        fclose($out);
        exit;
    }

    public function pdf($reportId = null): void {
        $this->requireDivisionalReportAccess();
        $model = $this->model('DivisionalReportModel');
        $divisionId = (int) $_SESSION['division_id'];
        $report = $model->getReport($divisionId, (int) $reportId, (string) $_SESSION['user_role']);
        if (!$report) {
            $this->setFlash('error', 'The selected report was not found.');
            $this->redirect('divisionalreports');
        }
        $division = $model->getDivision($divisionId);
        $data = $model->getReportData($divisionId, $report);
        $filename = 'division-report-' . (int) $report->report_id . '-' . date('Ymd', strtotime((string) $report->generated_at)) . '.pdf';
        $pdf = DivisionalReportPdf::render($report, $division, $data);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, no-store, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        echo $pdf;
        exit;
    }

    public function archive($reportId = null): void {
        $this->requireDivisionalReportAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'The archive request could not be verified.');
            $this->redirect('divisionalreports');
        }
        $changed = $this->model('DivisionalReportModel')->archiveReport(
            (int) $_SESSION['division_id'],
            (int) $reportId,
            (int) $_SESSION['user_id'],
            (string) $_SESSION['user_role']
        );
        $this->setFlash($changed ? 'success' : 'error', $changed ? 'The report was archived.' : 'The report could not be archived.');
        $this->redirect('divisionalreports');
    }

    public function restore($reportId = null): void {
        $this->requireDivisionalReportAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'The restore request could not be verified.');
            $this->redirect('divisionalreports');
        }
        $changed = $this->model('DivisionalReportModel')->restoreReport(
            (int) $_SESSION['division_id'], (int) $reportId, (string) $_SESSION['user_role']
        );
        $this->setFlash($changed ? 'success' : 'error', $changed ? 'The report was restored.' : 'The report could not be restored.');
        $this->redirect('divisionalreports');
    }

    private function validDate(string $value): bool {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }
}
