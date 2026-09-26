<?php

require_once __DIR__ . '/../core/DivisionalReportPdf.php';

class Zonalreports extends Controller {
    private function requireZonalReportAccess(): void {
        if (empty($_SESSION['user_id'])) $this->redirect('auth/signin');
        $allowedRoles = ['ZonalCoordinator', 'ZonalSecretary', 'ZonalTreasurer'];
        if (!in_array((string) ($_SESSION['user_role'] ?? ''), $allowedRoles, true)) {
            $this->redirect('home');
        }
        if ((int) ($_SESSION['zonal_id'] ?? 0) < 1) {
            http_response_code(403);
            exit('Your user account is not assigned to a zone.');
        }
    }

    private function verifyCsrf(): bool {
        $token = (string) ($_POST['csrf_token'] ?? '');
        return $token !== '' && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
    }

    private function setFlash(string $type, string $message): void {
        $_SESSION['zonal_reports_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array {
        $flash = $_SESSION['zonal_reports_flash'] ?? null;
        unset($_SESSION['zonal_reports_flash']);
        return is_array($flash) ? $flash : null;
    }

    public function index(): void {
        $this->requireZonalReportAccess();
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $zonalId = (int) $_SESSION['zonal_id'];
        $role = (string) $_SESSION['user_role'];
        $model = $this->model('ZoneReportModel');
        try {
            $zone = $model->getZone($zonalId);
            $reports = $model->getReports($zonalId, $role);
            $catalog = $model->getCatalog($role);
        } catch (Throwable $exception) {
            http_response_code(500);
            exit('Report data could not be loaded. Run the divisional reports migration and try again.');
        }
        $this->view('zonalreports/index', [
            'zone' => $zone,
            'reports' => $reports,
            'catalog' => $catalog,
            'summary' => $model->getSummary($reports),
            'csrfToken' => $_SESSION['csrf_token'],
            'flash' => $this->pullFlash(),
            'userName' => $_SESSION['user_name'] ?? 'Zonal Officer',
            'userRole' => $_SESSION['user_role'],
            'userEmail' => $_SESSION['user_email'] ?? '',
        ]);
    }

    public function create(): void {
        $this->requireZonalReportAccess();
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $zonalId = (int) $_SESSION['zonal_id'];
        $role = (string) $_SESSION['user_role'];
        $model = $this->model('ZoneReportModel');
        try {
            $zone = $model->getZone($zonalId);
            $catalog = $model->getCatalog($role);
        } catch (Throwable $exception) {
            http_response_code(500);
            exit('Report configuration could not be loaded. Run the divisional reports migration and try again.');
        }
        $aggregateMode = $role === 'ZonalSecretary'
            && (string) ($_GET['mode'] ?? '') === 'aggregate';
        $this->view('zonalreports/create', [
            'zone' => $zone,
            'catalog' => $catalog,
            'aggregateMode' => $aggregateMode,
            'csrfToken' => $_SESSION['csrf_token'],
            'flash' => $this->pullFlash(),
            'userName' => $_SESSION['user_name'] ?? 'Zonal Officer',
            'userRole' => $role,
            'userEmail' => $_SESSION['user_email'] ?? '',
        ]);
    }

    public function generate(): void {
        $this->requireZonalReportAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'The report request could not be verified. Please try again.');
            $this->redirect('zonalreports/create');
        }
        $typeId = (int) ($_POST['report_type_id'] ?? 0);
        $start = trim((string) ($_POST['date_start'] ?? ''));
        $end = trim((string) ($_POST['date_end'] ?? ''));
        $format = (string) ($_POST['format'] ?? 'OnScreen');
        if (!in_array($format, ['OnScreen', 'PDF', 'CSV'], true)) $format = 'OnScreen';
        if (!$this->validDate($start) || !$this->validDate($end) || $start > $end) {
            $this->setFlash('error', 'Select a valid report period.');
            $this->redirect('zonalreports/create');
        }
        try {
            $model = $this->model('ZoneReportModel');
            $reportId = $model->createReport(
                (int) $_SESSION['zonal_id'], (int) $_SESSION['user_id'],
                (string) $_SESSION['user_role'], $typeId, $start, $end, $format
            );
            if ($format === 'CSV') $this->redirect('zonalreports/export/' . $reportId);
            if ($format === 'PDF') $this->redirect('zonalreports/pdf/' . $reportId);
            $this->redirect('zonalreports/preview/' . $reportId);
        } catch (Throwable $exception) {
            $this->setFlash('error', 'The report could not be generated from the selected period.');
            $this->redirect('zonalreports/create');
        }
    }

    public function preview($reportId = null): void {
        $this->requireZonalReportAccess();
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $model = $this->model('ZoneReportModel');
        $report = $model->getReport(
            (int) $_SESSION['zonal_id'], (int) $reportId, (string) $_SESSION['user_role']
        );
        if (!$report) {
            $this->setFlash('error', 'The selected report was not found.');
            $this->redirect('zonalreports');
        }
        $data = $model->getReportData((int) $_SESSION['zonal_id'], $report);
        $this->view('zonalreports/preview', [
            'report' => $report,
            'reportData' => $data,
            'zone' => $model->getZone((int) $_SESSION['zonal_id']),
            'csrfToken' => $_SESSION['csrf_token'],
            'userName' => $_SESSION['user_name'] ?? 'Zonal Officer',
            'userRole' => $_SESSION['user_role'],
            'userEmail' => $_SESSION['user_email'] ?? '',
        ]);
    }

    public function export($reportId = null): void {
        $this->requireZonalReportAccess();
        $model = $this->model('ZoneReportModel');
        $report = $model->getReport(
            (int) $_SESSION['zonal_id'], (int) $reportId, (string) $_SESSION['user_role']
        );
        if (!$report) {
            $this->setFlash('error', 'The selected report was not found.');
            $this->redirect('zonalreports');
        }
        $data = $model->getReportData((int) $_SESSION['zonal_id'], $report);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="zone-report-' . (int) $report->report_id . '.csv"');
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
        $this->requireZonalReportAccess();
        $model = $this->model('ZoneReportModel');
        $zonalId = (int) $_SESSION['zonal_id'];
        $report = $model->getReport($zonalId, (int) $reportId, (string) $_SESSION['user_role']);
        if (!$report) {
            $this->setFlash('error', 'The selected report was not found.');
            $this->redirect('zonalreports');
        }
        $zone = $model->getZone($zonalId);
        $data = $model->getReportData($zonalId, $report);
        $filename = 'zone-report-' . (int) $report->report_id . '-' . date('Ymd', strtotime((string) $report->generated_at)) . '.pdf';
        $pdf = DivisionalReportPdf::render($report, (object) ['division_name' => $zone->zonal_name ?? 'Zone'], $data);
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
        $this->requireZonalReportAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'The archive request could not be verified.');
            $this->redirect('zonalreports');
        }
        $changed = $this->model('ZoneReportModel')->archiveReport(
            (int) $_SESSION['zonal_id'],
            (int) $reportId,
            (int) $_SESSION['user_id'],
            (string) $_SESSION['user_role']
        );
        $this->setFlash($changed ? 'success' : 'error', $changed ? 'The report was archived.' : 'The report could not be archived.');
        $this->redirect('zonalreports');
    }

    public function restore($reportId = null): void {
        $this->requireZonalReportAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'The restore request could not be verified.');
            $this->redirect('zonalreports');
        }
        $changed = $this->model('ZoneReportModel')->restoreReport(
            (int) $_SESSION['zonal_id'], (int) $reportId, (string) $_SESSION['user_role']
        );
        $this->setFlash($changed ? 'success' : 'error', $changed ? 'The report was restored.' : 'The report could not be restored.');
        $this->redirect('zonalreports');
    }

    private function validDate(string $value): bool {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }
}
