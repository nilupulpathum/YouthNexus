<?php
/**
 * Reports Controller
 *
 * Handles the Manage Reports feature under NYSC Administration.
 *
 * Routes:
 *   /reports              → index()   – report library + filters
 *   /reports/create       → create()  – show create form (Phase 1)
 *   /reports/compile      → compile() – POST: save + show preview (Phase 3&4)
 *   /reports/preview/{id} → preview() – view an existing report preview
 *   /reports/archive/{id} → archive() – soft-delete a report
 *   /reports/share        → share()   – POST: record email/share action
 */
class Reports extends Controller {

    // ---------------------------------------------------------------
    // HELPERS
    // ---------------------------------------------------------------

    protected function requireNYSCAdmin(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'NYSCAdministrator') {
            if (empty($_SESSION['user_id'])) {
                $this->redirect('auth/signin');
            }
        }
    }

    protected function isJsonRequest(): bool {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xReq   = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return strpos($accept, 'application/json') !== false
            || strtolower($xReq) === 'xmlhttprequest'
            || (isset($_GET['format']) && $_GET['format'] === 'json');
    }

    private function currentUser(): int {
        return (int)($_SESSION['user_id'] ?? 1);
    }

    // ---------------------------------------------------------------
    // INDEX — Report library
    // ---------------------------------------------------------------

    public function index(): void {
        $this->requireNYSCAdmin();

        $model = $this->model('ReportModel');

        // Filter values from query string
        $selectedCategory = trim($_GET['category'] ?? '');
        $selectedType     = trim($_GET['type']     ?? '');
        $searchText       = trim($_GET['search']   ?? '');

        // Load catalog for dropdown rendering
        $catalog = $model->getCatalog();

        // Validate: type must belong to selected category
        $typeOptions = ($selectedCategory !== '' && isset($catalog[$selectedCategory]))
            ? $catalog[$selectedCategory]
            : [];
        $typeIsValid = ($selectedType !== '' && in_array($selectedType, $typeOptions));
        if (!$typeIsValid) {
            $selectedType = '';
        }

        // Fetch filtered reports
        $reports   = $model->getReports($selectedCategory, $selectedType, $searchText);
        $hasFilter = ($selectedCategory !== '' || $typeIsValid || $searchText !== '');

        $userName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
        if (empty($userName)) $userName = 'N. Fernando';

        // Flash messages
        $flashSuccess = $_SESSION['reports_flash_success'] ?? null;
        $flashError   = $_SESSION['reports_flash_error']   ?? null;
        unset($_SESSION['reports_flash_success'], $_SESSION['reports_flash_error']);

        $this->view('reports/index', [
            'title'             => 'Manage Reports — YouthNexus',
            'pageTitle'         => 'National Reports Management',
            'pageDescription'   => 'Create, filter, and aggregate reports across all youth clubs, divisions, and zones',
            'currentRoute'      => 'reports',
            'userRole'          => $_SESSION['user_role'] ?? 'NYSCAdministrator',
            'userName'          => $userName,
            'catalog'           => $catalog,
            'reports'           => array_values($reports),
            'selectedCategory'  => $selectedCategory,
            'selectedType'      => $selectedType,
            'typeOptions'       => $typeOptions,
            'searchText'        => $searchText,
            'hasFilter'         => $hasFilter,
            'flashSuccess'      => $flashSuccess,
            'flashError'        => $flashError,
        ]);
    }

    // ---------------------------------------------------------------
    // CREATE — Phase 1 configuration form (shown as modal overlay)
    // ---------------------------------------------------------------

    public function create(): void {
        $this->requireNYSCAdmin();

        $model   = $this->model('ReportModel');
        $catalog = $model->getCatalog();

        $scopes = [
            'Club'     => 'Club Summary (Single Club)',
            'Divisional' => 'Divisional Summary (Roll up Club submissions)',
            'Zonal'    => 'Zonal Summary (Roll up Divisional submissions)',
            'National' => 'National Summary (Roll up all Zonal submissions)',
        ];

        $formats = [
            ['id' => 'PDF',      'title' => 'Formal Printable (PDF)',  'desc' => 'Formatted for archival & official signing',   'icon_key' => 'file'],
            ['id' => 'CSV',      'title' => 'Data Spreadsheet (CSV)',  'desc' => 'Raw structured sub-ledger & event rows',      'icon_key' => 'table'],
            ['id' => 'OnScreen', 'title' => 'On-Screen Dashboard',     'desc' => 'Interactive summary cards & drilldown charts','icon_key' => 'bar-chart'],
        ];

        $userName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
        if (empty($userName)) $userName = 'N. Fernando';

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->view('reports/create', [
            'title'        => 'Create New Report — YouthNexus',
            'pageTitle'    => 'Create New Report',
            'currentRoute' => 'reports',
            'userRole'     => $_SESSION['user_role'] ?? 'NYSCAdministrator',
            'userName'     => $userName,
            'catalog'      => $catalog,
            'scopes'       => $scopes,
            'formats'      => $formats,
            'csrf_token'   => $_SESSION['csrf_token'],
        ]);
    }

    // ---------------------------------------------------------------
    // COMPILE — POST handler: save report + redirect to preview
    // ---------------------------------------------------------------

    public function compile(): void {
        $this->requireNYSCAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('reports/create');
        }

        // CSRF check
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
            if ($this->isJsonRequest()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid session token.']);
                exit;
            }
            $_SESSION['reports_flash_error'] = 'Invalid session token. Please try again.';
            $this->redirect('reports');
        }

        $model = $this->model('ReportModel');

        // Map scope label → ENUM value
        $scopeMap = [
            'Club Summary (Single Club)'                   => 'Club',
            'Divisional Summary (Roll up Club submissions)' => 'Divisional',
            'Zonal Summary (Roll up Divisional submissions)' => 'Zonal',
            'National Summary (Roll up all Zonal submissions)' => 'National',
        ];

        $category   = trim($_POST['category'] ?? '');
        $typeName   = trim($_POST['type']     ?? '');
        $scopeLabel = trim($_POST['scope']    ?? '');
        $scopeLevel = $scopeMap[$scopeLabel]  ?? 'National';
        $dateStart  = trim($_POST['date_from'] ?? '');
        $dateEnd    = trim($_POST['date_to']   ?? '');
        $format     = in_array($_POST['format'] ?? '', ['PDF','CSV','OnScreen']) ? $_POST['format'] : 'PDF';

        // Resolve type ID
        $typeId = $model->getTypeId($category, $typeName);
        if (!$typeId) {
            // Fallback: create with first type in category
            $catalog = $model->getCatalog();
            $firstType = $catalog[$category][0] ?? '';
            $typeId = $model->getTypeId($category, $firstType) ?: 1;
        }

        $reportId = $model->createReport([
            'report_type_id'  => $typeId,
            'scope_level'     => $scopeLevel,
            'scope_id'        => null,
            'date_range_start'=> $dateStart,
            'date_range_end'  => $dateEnd,
            'format'          => $format,
            'generated_by'    => $this->currentUser(),
        ]);

        if ($this->isJsonRequest()) {
            echo json_encode(['success' => true, 'report_id' => $reportId]);
            exit;
        }

        if ($reportId) {
            $this->redirect("reports/preview/{$reportId}");
        } else {
            $_SESSION['reports_flash_error'] = 'Failed to save the report. Please try again.';
            $this->redirect('reports/create');
        }
    }

    // ---------------------------------------------------------------
    // PREVIEW — Phase 3 & 4: view generated report
    // ---------------------------------------------------------------

    public function preview($reportId = null): void {
        $this->requireNYSCAdmin();

        $reportId = (int)($reportId ?? $_GET['id'] ?? 0);
        $model    = $this->model('ReportModel');
        $report   = $reportId ? $model->getReportById($reportId) : null;

        if (!$report) {
            // No DB record — show demo preview (matching source file behaviour)
            $report = (object)[
                'report_id'       => 0,
                'category'        => 'Financial',
                'type_name'       => 'National Consolidated Financial Rollup',
                'scope_level'     => 'National',
                'date_range_start'=> '2026-01-01',
                'date_range_end'  => '2026-06-30',
                'format'          => 'PDF',
                'first_name'      => 'N.',
                'last_name'       => 'Fernando',
                'generated_at'    => date('Y-m-d H:i:s'),
            ];
        }

        $previewData = $model->buildPreviewData(
            $report->category    ?? 'Financial',
            $report->type_name   ?? '',
            $report->scope_level ?? 'National',
            $report->scope_id    ?? null,
            $report->date_range_start ?? '2026-01-01',
            $report->date_range_end   ?? '2026-06-30'
        );

        $userName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
        if (empty($userName)) $userName = 'N. Fernando';

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->view('reports/preview', [
            'title'       => 'Report Preview — YouthNexus',
            'pageTitle'   => 'Report Preview',
            'currentRoute'=> 'reports',
            'userRole'    => $_SESSION['user_role'] ?? 'NYSCAdministrator',
            'userName'    => $userName,
            'report'      => $report,
            'kpis'        => $previewData['kpis'],
            'summaryRows' => $previewData['summaryRows'],
            'rawRows'     => $previewData['rawRows'],
            'csrf_token'  => $_SESSION['csrf_token'],
        ]);
    }

    // ---------------------------------------------------------------
    // ARCHIVE — soft-delete
    // ---------------------------------------------------------------

    public function archive($reportId = null): void {
        $this->requireNYSCAdmin();

        $reportId = (int)($reportId ?? $_POST['report_id'] ?? 0);
        $model    = $this->model('ReportModel');
        $success  = $model->archiveReport($reportId);

        if ($this->isJsonRequest()) {
            echo json_encode(['success' => $success]);
            exit;
        }

        if ($success) {
            $_SESSION['reports_flash_success'] = 'Report moved to archive.';
        } else {
            $_SESSION['reports_flash_error'] = 'Unable to archive report.';
        }
        $this->redirect('reports');
    }

    // ---------------------------------------------------------------
    // SHARE — record Email/Share action
    // ---------------------------------------------------------------

    public function share(): void {
        $this->requireNYSCAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('reports');
        }

        $reportId = (int)($_POST['report_id'] ?? 0);
        $email    = trim($_POST['recipient_email'] ?? '');
        $method   = in_array($_POST['method'] ?? '', ['Email','Link']) ? $_POST['method'] : 'Email';

        $model   = $this->model('ReportModel');
        $success = false;
        if ($reportId && $email) {
            $success = $model->shareReport($reportId, $this->currentUser(), $email, $method);
        }

        if ($this->isJsonRequest()) {
            echo json_encode(['success' => $success, 'message' => $success ? 'Report shared successfully.' : 'Failed to record share.']);
            exit;
        }

        if ($success) {
            $_SESSION['reports_flash_success'] = "Report shared to {$email} via {$method}.";
        } else {
            $_SESSION['reports_flash_error'] = 'Share failed. Please check the email address.';
        }
        $this->redirect("reports/preview/{$reportId}");
    }
}
