<?php

/**
 * Zonaltreasurer — zonal finance workspace and session-only fund distribution.
 *
 * Presentation-only: mock data mirroring the future backend contract.
 * No database reads or writes. Full pages land in Z7 (dashboard +
 * allocate), Z8 (audit), Z9 (assets/ledger), Z10 (voids).
 *
 * Routes:
 *   zonaltreasurer -> index()  (zonal treasurer only)
 *   zonaltreasurer/allocate -> allocate()
 *   zonaltreasurer/audit -> audit()
 *   zonaltreasurer/assets -> assets()
 *   zonaltreasurer/voids -> voids()
 */
class Zonaltreasurer extends Controller {

    private function requireZonalTreasurer() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        $allowedRoles = ['ZonalTreasurer', 'zonaltreasurer'];
        if (!in_array($_SESSION['user_role'] ?? '', $allowedRoles, true)) {
            $this->redirect('home');
        }
    }

    /**
     * Base view data for the shared shell.
     */
    private function shell($title, $pageTitle, $pageDescription, $currentRoute) {
        $memberName = trim((string) ($_SESSION['user_name'] ?? '')) ?: 'YouthNexus User';
        return [
            'title'                   => $title,
            'pageTitle'               => $pageTitle,
            'pageDescription'         => $pageDescription,
            'currentRoute'            => $currentRoute,
            'userRole'                => $_SESSION['user_role'] ?? 'ZonalTreasurer',
            'userName'                => $memberName,
            'userEmail'               => $_SESSION['user_email'] ?? '',
            'userInitials'            => $_SESSION['user_initials'] ?? '',
            'unreadNotificationCount' => 2,
        ];
    }

    /**
     * Treasurer overview with the same fund totals as the allocation screen.
     */
    public function index() {
        $this->requireZonalTreasurer();

        $data = $this->shell(
            'Zonal Treasurer Overview — YouthNexus Pulse',
            'Zonal Treasurer Overview',
            'Funds, audits and assets of Gampaha Zone.',
            'zonaltreasurer'
        );

        $data['fundStats'] = $this->model('ZoneFundTransferMock')->stats();
        $this->view('zonaltreasurer/index', $data);
    }

    /**
     * Reuse the NYSC allocation screen with a zonal demo adapter.
     */
    public function allocate() {
        $this->requireZonalTreasurer();

        $data = $this->shell(
            'Allocate Funds — YouthNexus Pulse',
            'Allocate Funds',
            'Distribute NYSC funds to divisions under Gampaha Zone — demo.',
            'zonaltreasurer/allocate'
        );

        $mock = $this->model('ZoneFundTransferMock');
        $filters = $this->fundFilters();
        $_SESSION['zonal_fund_csrf'] = $_SESSION['zonal_fund_csrf'] ?? bin2hex(random_bytes(32));
        $data += [
            'transferRoute' => 'zonaltreasurer', 'listRoute' => 'zonaltreasurer/allocate', 'isZonalDemo' => true,
            'transfers' => $mock->transfers($filters), 'stats' => $mock->stats(),
            'zones' => $mock->divisions(), 'bankAccounts' => [$mock->account()],
            'filters' => $filters, 'nextReference' => $mock->reference(),
            'csrf_token' => $_SESSION['zonal_fund_csrf'],
        ];
        $this->view('zonaltreasurer/allocate', $data);
    }

    private function fundFilters() {
        $filters = [];
        foreach (['search', 'zone_id', 'status', 'quarter'] as $key) {
            $filters[$key] = is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : '';
        }
        return $filters;
    }

    public function create() {
        $this->requireZonalTreasurer();
        header('Content-Type: application/json');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo json_encode(['success' => false, 'error' => 'Use POST to allocate funds.']);
            return;
        }
        if (empty($_SESSION['zonal_fund_csrf']) || !is_string($_POST['csrf_token'] ?? null)
            || !hash_equals($_SESSION['zonal_fund_csrf'], $_POST['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Refresh the page before allocating funds.']);
            return;
        }
        try {
            foreach ($_POST as $value) {
                if (!is_string($value)) throw new InvalidArgumentException('Invalid form input.');
            }
            $transfer = $this->model('ZoneFundTransferMock')->create($_POST);
            $_SESSION['zonal_fund_csrf'] = bin2hex(random_bytes(32));
            echo json_encode(['success' => true, 'transfer_id' => $transfer->allocation_id,
                'redirect' => ROOT . '/zonaltreasurer/allocate']);
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function getreference() {
        $this->requireZonalTreasurer();
        $method = is_string($_GET['method'] ?? null) ? $_GET['method'] : 'RTGS';
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'reference' => $this->model('ZoneFundTransferMock')->reference($method)]);
    }

    public function exportledger() {
        $this->requireZonalTreasurer();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Demo_Zone_Division_Transfers.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Demo reference', 'Division', 'Date', 'Amount (LKR)', 'Status', 'Purpose']);
        foreach ($this->model('ZoneFundTransferMock')->transfers($this->fundFilters()) as $t) {
            $purpose = preg_match('/^[=+@\-\t\r\n]/', $t->purpose_description) ? "'" . $t->purpose_description : $t->purpose_description;
            fputcsv($out, [$t->reference_no, $t->target_zone_name, $t->transfer_date, number_format($t->amount, 2, '.', ''), $t->status, $purpose]);
        }
        fclose($out);
    }

    public function receipt($id = null) {
        $this->requireZonalTreasurer();
        $transfer = $this->model('ZoneFundTransferMock')->find($id);
        if (!$transfer) {
            http_response_code(404);
            echo 'Demo transfer not found.';
            return;
        }
        $this->view('zonaltreasurer/receipt', ['transfer' => $transfer, 'isZonalDemo' => true,
            'receiptSubtitle' => 'Gampaha Zone — DEMO division allocation; no money transferred']);
    }

    /**
     * Audit finance placeholder (Z8 builds breakdown + flag flow).
     */
    public function audit() {
        $this->requireZonalTreasurer();

        $data = $this->shell(
            'Audit Finance — YouthNexus Pulse',
            'Audit Finance',
            'Review divisional financial reports.',
            'zonaltreasurer/audit'
        );

        $this->view('zonaltreasurer/audit', $data);
    }

    /**
     * Zonal assets placeholder (Z9 builds C7 shape at zonal scope).
     */
    public function assets() {
        $this->requireZonalTreasurer();

        $data = $this->shell(
            'Zonal Assets — YouthNexus Pulse',
            'Zonal Assets',
            'Zonal-level asset inventory.',
            'zonaltreasurer/assets'
        );

        $this->view('zonaltreasurer/assets', $data);
    }

    /**
     * Zonal ledger placeholder (Z9 builds the C8 shape at zonal scope —
     * added 2026-09-12 per the zonal treasurer workflow step "Log Zonal
     * Transactions").
     */
    public function ledger() {
        $this->requireZonalTreasurer();

        $data = $this->shell(
            'Zonal Ledger — YouthNexus Pulse',
            'Zonal Ledger',
            'Income and expense at zonal level.',
            'zonaltreasurer/ledger'
        );

        $this->view('zonaltreasurer/ledger', $data);
    }

    /**
     * Void approvals placeholder (Z10 builds Pending-Void queue).
     */
    public function voids() {
        $this->requireZonalTreasurer();

        $data = $this->shell(
            'Void Requests — YouthNexus Pulse',
            'Void Requests',
            'Pending void requests from divisions and clubs.',
            'zonaltreasurer/voids'
        );

        $this->view('zonaltreasurer/voids', $data);
    }
}
