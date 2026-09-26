<?php

/**
 * Audit Controller
 *
 * Annual Financial Audit feature for NYSC Administration, aligned to the workflow:
 *   Phase 1 — index(): scope & financial-year selection + recent audit reports.
 *   Phase 1 — run():   compiles financial data for the selected scope (creates/refreshes audit).
 *   Phase 2–4 — report($id): math check panel, red flag table and auditor actions.
 *   Phase 4 — clarify(): send clarification request; signoff(): approve & lock the year.
 *
 * Routes:
 *   /audit                  → index()
 *   /audit/run              → run()      [POST]
 *   /audit/report/{id}      → report($id)
 *   /audit/rerun            → rerun()    [POST]
 *   /audit/clarify          → clarify()  [POST]
 *   /audit/signoff          → signoff()  [POST]
 *   /audit/resolveflag/{id} → resolveflag($id)
 *   /audit/export           → export()   [GET]
 */
class Audit extends Controller {

    /**
     * Enforce NYSC Administrator access.
     */
    protected function requireNYSCAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'NYSCAdministrator') {
            if (empty($_SESSION['user_id'])) {
                $this->redirect('auth/signin');
            }
            $this->redirect('home');
        }
    }

    protected function isJsonRequest() {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xReq   = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return strpos($accept, 'application/json') !== false
            || strtolower($xReq) === 'xmlhttprequest'
            || (isset($_GET['format']) && $_GET['format'] === 'json');
    }

    private function auditorIdentity() {
        $auditorName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
        if (empty($auditorName)) {
            $auditorName = $_SESSION['user_name'] ?? 'NYSC Administrator';
        }
        $auditorRole = $_SESSION['user_role'] ?? 'NYSCAdministrator';
        return [$auditorName, $auditorRole];
    }

    private function flash() {
        $flash = [
            'flashSuccess' => $_SESSION['audit_flash_success'] ?? null,
            'flashError'   => $_SESSION['audit_flash_error'] ?? null,
        ];
        unset($_SESSION['audit_flash_success'], $_SESSION['audit_flash_error']);
        return $flash;
    }

    /**
     * PHASE 1 — Audit dashboard: parameters, compile action and the register
     * of audit reports already produced.
     */
    public function index() {
        $this->requireNYSCAdmin();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $model = $this->model('AuditModel');

        [$auditorName, $auditorRole] = $this->auditorIdentity();

        $this->view('audit/index', [
            'title'           => 'Annual Audit — YouthNexus',
            'pageTitle'       => 'Annual Financial Audit',
            'pageDescription' => 'Select an entity and financial year to compile its statutory audit.',
            'currentRoute'    => 'audit',
            'userRole'        => $_SESSION['user_role'] ?? 'NYSCAdministrator',
            'userName'        => $auditorName,
            'auditorName'     => $auditorName,
            'auditorRole'     => $auditorRole,
            'scopes'          => $model->getAvailableScopes(),
            'years'           => $model->getSelectableYears(),
            'audits'          => $model->getAuditList(),
        ] + $this->flash() + ['csrf_token' => $_SESSION['csrf_token']]);
    }

    /**
     * PHASE 1 — Compile financial data for the selected scope & year,
     * running the math check and red flag detection, then open the report.
     */
    public function run() {
        $this->requireNYSCAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('audit');
        }

        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['audit_flash_error'] = 'Invalid session token. Please try again.';
            $this->redirect('audit');
        }

        // Entity select posts "Level:ID" (e.g. "Zonal:6", "National:0").
        $entity     = trim($_POST['entity'] ?? 'National:0');
        $parts      = explode(':', $entity, 2);
        $scopeLevel = ucfirst(strtolower(trim($parts[0] ?? 'National')));
        $scopeId    = (int)($parts[1] ?? 0);
        $year       = (int)($_POST['year'] ?? date('Y'));

        if (!in_array($scopeLevel, ['National', 'Zonal', 'Divisional', 'Club'], true)) {
            $_SESSION['audit_flash_error'] = 'Invalid audit scope selected.';
            $this->redirect('audit');
        }
        if ($scopeLevel !== 'National' && $scopeId <= 0) {
            $_SESSION['audit_flash_error'] = 'Please select the specific entity to audit.';
            $this->redirect('audit');
        }

        $model = $this->model('AuditModel');
        $auditId = $model->runAuditCheck($scopeLevel, $scopeId, $year, $_SESSION['user_id'] ?? 1);

        $_SESSION['audit_flash_success'] = 'Audit compiled for ' . $model->scopeLabel($scopeLevel, $scopeId) . ' — FY ' . $year . '. Review the math check and red flags below.';
        $this->redirect("audit/report/{$auditId}");
    }

    /**
     * PHASE 2–4 — Audit report: math check, red flags and auditor actions.
     */
    public function report($auditId = null) {
        $this->requireNYSCAdmin();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $model = $this->model('AuditModel');
        $audit = $model->getAuditById((int)$auditId);

        if (!$audit) {
            $_SESSION['audit_flash_error'] = 'Audit report not found. Compile a new audit from the dashboard.';
            $this->redirect('audit');
        }

        [$auditorName, $auditorRole] = $this->auditorIdentity();

        // Sign-off eligibility per the workflow: math passed AND no unresolved flags.
        $unresolvedCount = $model->countUnresolvedFlags($audit->audit_id);
        $canSignOff = !$audit->locked
            && $audit->math_check_status === 'Passed'
            && $unresolvedCount === 0;

        $this->view('audit/report', [
            'title'           => 'Audit Report — YouthNexus',
            'pageTitle'       => 'Annual Financial Audit Report',
            'pageDescription' => 'Math verification, red flags and sign-off for the selected entity & financial year.',
            'currentRoute'    => 'audit',
            'userRole'        => $_SESSION['user_role'] ?? 'NYSCAdministrator',
            'userName'        => $auditorName,
            'auditorName'     => $auditorName,
            'auditorRole'     => $auditorRole,
            'audit'           => $audit,
            'unresolvedCount' => $unresolvedCount,
            'canSignOff'      => $canSignOff,
        ] + $this->flash() + ['csrf_token' => $_SESSION['csrf_token']]);
    }

    /**
     * Re-run math check & red flag detection for an existing audit.
     */
    public function rerun() {
        $this->requireNYSCAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('audit');
        }

        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['audit_flash_error'] = 'Invalid session token. Please try again.';
            $this->redirect('audit');
        }

        $auditId = (int)($_POST['audit_id'] ?? 0);
        $model   = $this->model('AuditModel');
        $audit   = $model->getAuditById($auditId);

        if (!$audit) {
            $_SESSION['audit_flash_error'] = 'Audit record not found.';
            $this->redirect('audit');
        }
        if ($audit->locked) {
            $_SESSION['audit_flash_error'] = 'This audit is signed off and locked; figures can no longer be recalculated.';
            $this->redirect("audit/report/{$auditId}");
        }

        $model->runAuditCheck($audit->scope_level, $audit->scope_id, $audit->financial_year, $_SESSION['user_id'] ?? 1);

        $_SESSION['audit_flash_success'] = 'Audit recalculated — math check and red flags refreshed from the current ledger data.';
        $this->redirect("audit/report/{$auditId}");
    }

    /**
     * PHASE 4 — Send clarification request to the entity's officers.
     */
    public function clarify() {
        $this->requireNYSCAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('audit');
        }

        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['audit_flash_error'] = 'Invalid session token. Please try again.';
            $this->redirect('audit');
        }

        $auditId      = (int)($_POST['audit_id'] ?? 0);
        $queryText    = trim($_POST['query'] ?? '');
        $deadlineDays = max(1, (int)($_POST['deadline_days'] ?? 7));
        $flagIds      = !empty($_POST['flag_ids']) ? (array)$_POST['flag_ids'] : [];

        $model  = $this->model('AuditModel');
        $result = $model->requestClarification($auditId, $flagIds, $queryText, $_SESSION['user_id'] ?? 1, $deadlineDays);

        if ($result['success']) {
            $_SESSION['audit_flash_success'] = $result['message'];
        } else {
            $_SESSION['audit_flash_error'] = $result['message'];
        }

        $this->redirect("audit/report/{$auditId}");
    }

    /**
     * PHASE 4 — Formal sign-off & financial-year lock.
     */
    public function signoff() {
        $this->requireNYSCAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('audit');
        }

        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['audit_flash_error'] = 'Invalid session token. Please try again.';
            $this->redirect('audit');
        }

        $auditId = (int)($_POST['audit_id'] ?? 0);
        $model   = $this->model('AuditModel');
        $result  = $model->signOffAudit($auditId, $_SESSION['user_id'] ?? 1);

        if ($result['success']) {
            $_SESSION['audit_flash_success'] = $result['message'];
        } else {
            $_SESSION['audit_flash_error'] = $result['message'];
        }

        $this->redirect("audit/report/{$auditId}");
    }

    /**
     * Mark an individual red flag as resolved / accepted.
     */
    public function resolveflag($flagId = null) {
        $this->requireNYSCAdmin();

        $flagId = (int)($flagId ?? $_POST['flag_id'] ?? 0);
        $model  = $this->model('AuditModel');

        $auditIdForFlag = $model->getFlagAuditId($flagId);
        $success = $model->resolveFlag($flagId);

        if ($success) {
            $_SESSION['audit_flash_success'] = 'Audit exception marked as resolved / accepted.';
        } else {
            $_SESSION['audit_flash_error'] = 'Unable to resolve the audit exception.';
        }

        $this->redirect($auditIdForFlag ? "audit/report/{$auditIdForFlag}" : 'audit');
    }

    /**
     * Export the audit summary as CSV.
     */
    public function export() {
        $this->requireNYSCAdmin();

        $auditId = (int)($_GET['audit_id'] ?? 0);
        $model   = $this->model('AuditModel');

        if (!$auditId) {
            $this->redirect('audit');
        }

        $audit = $model->getAuditById($auditId);
        $year  = $audit ? $audit->financial_year : date('Y');

        $csv = $model->exportAuditCsv($auditId);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="youthnexus_audit_summary_FY' . $year . '.csv"');
        echo $csv;
        exit;
    }
}
