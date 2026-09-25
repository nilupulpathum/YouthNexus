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
        if ((int) ($_SESSION['zonal_id'] ?? 0) < 1) {
            http_response_code(403);
            exit('Your user account is not assigned to a zone.');
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

        $data['fundStats'] = $this->model('ZoneFundModel')->getStats((int) ($_SESSION['zonal_id'] ?? 0));
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

        $zonalId = (int) ($_SESSION['zonal_id'] ?? 0);
        $model = $this->model('ZoneFundModel');
        $filters = $this->fundFilters();
        $_SESSION['zonal_fund_csrf'] = $_SESSION['zonal_fund_csrf'] ?? bin2hex(random_bytes(32));
        $divisions = [];
        foreach ($model->getDivisions($zonalId) as $d) {
            $divisions[] = (object) ['zonal_id' => (int) $d->division_id, 'zonal_name' => $d->division_name, 'province' => '', 'hub_name' => ''];
        }
        $data += [
            'transferRoute' => 'zonaltreasurer', 'listRoute' => 'zonaltreasurer/allocate', 'isZonalMode' => true,
            'transfers' => $model->getTransfers($zonalId, $filters), 'stats' => $model->getStats($zonalId),
            'zones' => $divisions, 'bankAccounts' => $model->getBankAccounts($zonalId),
            'filters' => $filters, 'nextReference' => $model->generateReferenceNumber(),
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
            $model = $this->model('ZoneFundModel');
            $allocationId = $model->createAllocation((int) ($_SESSION['zonal_id'] ?? 0), (int) $_SESSION['user_id'], [
                'division_id' => $_POST['zone_id'] ?? 0,
                'bank_account_id' => $_POST['bank_account_id'] ?? 0,
                'amount' => str_replace([',', ' '], '', trim($_POST['amount'] ?? '')),
                'transfer_date' => $_POST['transfer_date'] ?? '',
                'method' => $_POST['disbursement_method'] ?? 'RTGS',
                'reference' => $_POST['reference'] ?? '',
                'purpose' => $_POST['purpose'] ?? '',
            ]);
            $_SESSION['zonal_fund_csrf'] = bin2hex(random_bytes(32));
            echo json_encode(['success' => true, 'transfer_id' => $allocationId,
                'redirect' => ROOT . '/zonaltreasurer/allocate']);
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function getreference() {
        $this->requireZonalTreasurer();
        $method = is_string($_GET['method'] ?? null) ? $_GET['method'] : 'RTGS';
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'reference' => $this->model('ZoneFundModel')->generateReferenceNumber($method)]);
    }

    public function exportledger() {
        $this->requireZonalTreasurer();
        $zonalId = (int) ($_SESSION['zonal_id'] ?? 0);
        $zone = $this->model('ZoneFundModel')->getZone($zonalId);
        $zoneName = preg_replace('/[^A-Za-z0-9]+/', '_', $zone->zonal_name ?? 'Zone');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $zoneName . '_Division_Transfers.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Reference', 'Division', 'Date', 'Amount (LKR)', 'Status', 'Purpose']);
        foreach ($this->model('ZoneFundModel')->getTransfers($zonalId, $this->fundFilters()) as $t) {
            $purpose = preg_match('/^[=+@\-\t\r\n]/', $t->purpose_description) ? "'" . $t->purpose_description : $t->purpose_description;
            fputcsv($out, [$t->reference_no, $t->target_zone_name, $t->transfer_date, number_format($t->amount, 2, '.', ''), $t->status, $purpose]);
        }
        fclose($out);
    }

    public function receipt($id = null) {
        $this->requireZonalTreasurer();
        $transfer = $this->model('ZoneFundModel')->find((int) ($_SESSION['zonal_id'] ?? 0), (int) $id);
        if (!$transfer) {
            http_response_code(404);
            echo 'Transfer not found in your zone.';
            return;
        }
        $this->view('zonaltreasurer/receipt', ['transfer' => $transfer, 'isZonalMode' => true,
            'receiptSubtitle' => ($transfer->from_zone_name ?? 'Zone') . ' - division allocation']);
    }

    private function auditState() {
        $key = (string)($_SESSION['user_id'] ?? 'demo');
        if (!isset($_SESSION['zonal_audit_demo'][$key])) {
            $_SESSION['zonal_audit_demo'][$key] = [
                'reports' => [
                    ['id' => 'DIV-01', 'division' => 'Gampaha Division', 'income' => 820000, 'expenses' => 614000, 'balance' => 206000, 'status' => 'Review complete'],
                    ['id' => 'DIV-02', 'division' => 'Ja-Ela Division', 'income' => 650000, 'expenses' => 428000, 'balance' => 222000, 'status' => 'Attention needed'],
                    ['id' => 'DIV-03', 'division' => 'Negombo Division', 'income' => 540000, 'expenses' => 398000, 'balance' => 142000, 'status' => 'Review complete'],
                ],
                'flags' => [
                    ['id' => 'ZA-101', 'division' => 'Ja-Ela Division', 'type' => 'Missing receipt', 'reference' => 'JAE-2026-EXP-041', 'amount' => 28500, 'reason' => 'Expense report has no supporting receipt.', 'status' => 'Open', 'note' => ''],
                    ['id' => 'ZA-102', 'division' => 'Gampaha Division', 'type' => 'Unspent allocation', 'reference' => 'GAM-GRANT-2026-04', 'amount' => 98000, 'reason' => 'Quarterly allocation remains materially unspent.', 'status' => 'Response received', 'note' => 'Division provided a reallocation schedule for the next programme cycle.'],
                ],
            ];
        }
        return $_SESSION['zonal_audit_demo'][$key];
    }

    private function saveAuditState(array $state) {
        $key = (string)($_SESSION['user_id'] ?? 'demo');
        $_SESSION['zonal_audit_demo'][$key] = $state;
    }

    private function auditCsrf() {
        $_SESSION['zonal_audit_csrf'] = $_SESSION['zonal_audit_csrf'] ?? bin2hex(random_bytes(32));
        return $_SESSION['zonal_audit_csrf'];
    }

    private function auditPost() {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !is_string($_POST['csrf_token'] ?? null)
            || !hash_equals($this->auditCsrf(), $_POST['csrf_token'])) {
            $_SESSION['zonal_audit_flash'] = 'Refresh the audit page before submitting this action.';
            $this->redirect('zonaltreasurer/audit');
        }
    }

    private function auditFlagIndex(array $flags, $id) {
        foreach ($flags as $index => $flag) if (($flag['id'] ?? '') === $id) return $index;
        return null;
    }

    /**
     * Audit own-zone divisional finance. NYSC retains final sign-off and locks.
     */
    public function audit() {
        $this->requireZonalTreasurer();
        $state = $this->auditState();
        $income = array_sum(array_column($state['reports'], 'income'));
        $expenses = array_sum(array_column($state['reports'], 'expenses'));
        $unresolved = array_values(array_filter($state['flags'], static fn($flag) => !in_array($flag['status'], ['Resolved', 'Escalated to NYSC'], true)));
        $data = $this->shell(
            'Audit Finance — YouthNexus Pulse',
            'Audit Finance',
            'Review own-zone divisional finance and escalate material issues to NYSC.',
            'zonaltreasurer/audit'
        );
        $data += ['reports' => $state['reports'], 'flags' => $state['flags'], 'income' => $income, 'expenses' => $expenses,
            'unresolvedCount' => count($unresolved), 'reviewReady' => empty($unresolved), 'csrf_token' => $this->auditCsrf(),
            'flash' => $_SESSION['zonal_audit_flash'] ?? ''];
        unset($_SESSION['zonal_audit_flash']);
        $this->view('zonaltreasurer/audit', $data);
    }

    public function flag() {
        $this->requireZonalTreasurer(); $this->auditPost(); $state = $this->auditState();
        $division = trim((string)($_POST['division'] ?? '')); $type = trim((string)($_POST['type'] ?? ''));
        $reference = trim((string)($_POST['reference'] ?? '')); $reason = trim((string)($_POST['reason'] ?? ''));
        $amount = (float)str_replace(',', '', (string)($_POST['amount'] ?? ''));
        $validDivisions = array_column($state['reports'], 'division');
        if (!in_array($division, $validDivisions, true) || $type === '' || $reference === '' || $reason === '' || mb_strlen($reason) > 1000 || $amount <= 0) {
            $_SESSION['zonal_audit_flash'] = 'Provide an own-zone division, issue details, positive amount and a discrepancy reason.';
        } else {
            $state['flags'][] = ['id' => 'ZA-' . (100 + count($state['flags']) + 1), 'division' => $division, 'type' => $type, 'reference' => $reference, 'amount' => $amount, 'reason' => $reason, 'status' => 'Open', 'note' => ''];
            $this->saveAuditState($state); $_SESSION['zonal_audit_flash'] = 'Discrepancy flagged for divisional finance review.';
        }
        $this->redirect('zonaltreasurer/audit');
    }

    public function clarify() {
        $this->requireZonalTreasurer(); $this->auditPost(); $state = $this->auditState(); $id = trim((string)($_POST['flag_id'] ?? ''));
        $query = trim((string)($_POST['query'] ?? '')); $index = $this->auditFlagIndex($state['flags'], $id);
        if ($index === null || $query === '' || mb_strlen($query) > 1000 || in_array($state['flags'][$index]['status'], ['Resolved', 'Escalated to NYSC'], true)) {
            $_SESSION['zonal_audit_flash'] = 'Enter a clarification request for an open flag.';
        } else {
            $state['flags'][$index]['status'] = 'Clarification requested'; $state['flags'][$index]['note'] = $query;
            $this->saveAuditState($state); $_SESSION['zonal_audit_flash'] = 'Clarification requested from the Divisional Treasurer; the Divisional Coordinator is notified.';
        }
        $this->redirect('zonaltreasurer/audit');
    }

    public function resolve() {
        $this->requireZonalTreasurer(); $this->auditPost(); $state = $this->auditState(); $id = trim((string)($_POST['flag_id'] ?? ''));
        $note = trim((string)($_POST['resolution_note'] ?? '')); $index = $this->auditFlagIndex($state['flags'], $id);
        if ($index === null || $state['flags'][$index]['status'] !== 'Response received' || $note === '' || mb_strlen($note) > 1000) {
            $_SESSION['zonal_audit_flash'] = 'Only a received divisional response can be resolved, and a resolution note is required.';
        } else {
            $state['flags'][$index]['status'] = 'Resolved'; $state['flags'][$index]['note'] = $note;
            $this->saveAuditState($state); $_SESSION['zonal_audit_flash'] = 'Flag resolved in the zonal review. This is not NYSC final sign-off.';
        }
        $this->redirect('zonaltreasurer/audit');
    }

    public function escalate() {
        $this->requireZonalTreasurer(); $this->auditPost(); $state = $this->auditState(); $id = trim((string)($_POST['flag_id'] ?? ''));
        $note = trim((string)($_POST['escalation_reason'] ?? '')); $index = $this->auditFlagIndex($state['flags'], $id);
        if ($index === null || in_array($state['flags'][$index]['status'], ['Resolved', 'Escalated to NYSC'], true) || $note === '' || mb_strlen($note) > 1000) {
            $_SESSION['zonal_audit_flash'] = 'Provide an escalation reason for an unresolved flag.';
        } else {
            $state['flags'][$index]['status'] = 'Escalated to NYSC'; $state['flags'][$index]['note'] = $note;
            $this->saveAuditState($state); $_SESSION['zonal_audit_flash'] = 'Flag escalated to NYSC for final authority.';
        }
        $this->redirect('zonaltreasurer/audit');
    }

    public function exportaudit() {
        $this->requireZonalTreasurer(); $state = $this->auditState();
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="Gampaha_Zone_Audit_Summary.csv"');
        $out = fopen('php://output', 'w'); fputcsv($out, ['Flag', 'Division', 'Type', 'Reference', 'Amount (LKR)', 'Status', 'Reason / latest note']);
        foreach ($state['flags'] as $flag) fputcsv($out, [$flag['id'], $flag['division'], $flag['type'], $flag['reference'], number_format((float)$flag['amount'], 2, '.', ''), $flag['status'], $flag['note'] ?: $flag['reason']]);
        fclose($out);
    }

    private function assetState() {
        $key = (string)($_SESSION['user_id'] ?? 'demo');
        if (!isset($_SESSION['zonal_asset_demo'][$key])) {
            $_SESSION['zonal_asset_demo'][$key] = [
                ['id' => 'ZA-201', 'name' => 'Portable PA System', 'category' => 'Audio Video Equipment', 'serial' => 'GZ-PA-2026-01', 'purchase_date' => 'Sep 4, 2026', 'valuation' => 185000, 'custodian' => 'Gampaha Zone Store', 'status' => 'Available', 'status_key' => 'available'],
                ['id' => 'ZA-202', 'name' => 'Programme Tent Set', 'category' => 'Events Equipment', 'serial' => 'GZ-TENT-2026-02', 'purchase_date' => 'Aug 18, 2026', 'valuation' => 96000, 'custodian' => 'Ja-Ela Division', 'status' => 'In use', 'status_key' => 'inuse'],
                ['id' => 'ZA-203', 'name' => 'Youth Sports Kit', 'category' => 'Sports', 'serial' => 'GZ-SPORT-2026-03', 'purchase_date' => 'Jul 29, 2026', 'valuation' => 72500, 'custodian' => 'Gampaha Zone Store', 'status' => 'Available', 'status_key' => 'available'],
                ['id' => 'ZA-204', 'name' => 'Projector', 'category' => 'Official Equipment', 'serial' => 'GZ-PROJ-2026-04', 'purchase_date' => 'Jun 10, 2026', 'valuation' => 132000, 'custodian' => 'Negombo Division', 'status' => 'In use', 'status_key' => 'inuse'],
            ];
        }
        return $_SESSION['zonal_asset_demo'][$key];
    }

    private function saveAssetState(array $assets) {
        $_SESSION['zonal_asset_demo'][(string)($_SESSION['user_id'] ?? 'demo')] = $assets;
    }

    private function zonalCsrf() {
        $_SESSION['zonal_treasurer_csrf'] = $_SESSION['zonal_treasurer_csrf'] ?? bin2hex(random_bytes(32));
        return $_SESSION['zonal_treasurer_csrf'];
    }

    private function validZonalPost($redirect) {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !is_string($_POST['csrf_token'] ?? null)
            || !hash_equals($this->zonalCsrf(), $_POST['csrf_token'])) {
            $_SESSION['zonal_treasurer_flash'] = 'Refresh the page before submitting this action.';
            $this->redirect($redirect);
        }
    }

    /**
     * Manage zonal-owned inventory and custodians. This is not asset verification.
     */
    public function assets() {
        $this->requireZonalTreasurer();
        $assets = $this->assetState();
        $category = trim((string)($_GET['category'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $search = strtolower(trim((string)($_GET['search'] ?? '')));
        $categories = ['Audio Video Equipment', 'Events Equipment', 'Sports', 'Official Equipment'];
        if ($category !== '' && !in_array($category, $categories, true)) $category = '';
        if (!in_array($status, ['', 'available', 'inuse'], true)) $status = '';
        $visible = array_values(array_filter($assets, static function ($asset) use ($category, $status, $search) {
            return ($category === '' || $asset['category'] === $category)
                && ($status === '' || $asset['status_key'] === $status)
                && ($search === '' || str_contains(strtolower($asset['name'] . ' ' . $asset['serial'] . ' ' . $asset['custodian']), $search));
        }));
        $data = $this->shell(
            'Zonal Assets — YouthNexus Pulse',
            'Zonal Assets',
            'Manage zonal assets and custodians across Gampaha Zone.',
            'zonaltreasurer/assets'
        );
        $data += ['assets' => $visible, 'categories' => $categories, 'category' => $category, 'status' => $status, 'search' => $search,
            'stats' => ['total' => count($assets), 'available' => count(array_filter($assets, static fn($asset) => $asset['status_key'] === 'available')),
                'in_use' => count(array_filter($assets, static fn($asset) => $asset['status_key'] === 'inuse')),
                'valuation' => array_sum(array_column($assets, 'valuation'))],
            'csrf_token' => $this->zonalCsrf(), 'flash' => $_SESSION['zonal_treasurer_flash'] ?? ''];
        unset($_SESSION['zonal_treasurer_flash']);
        $this->view('zonaltreasurer/assets', $data);
    }

    public function addasset() {
        $this->requireZonalTreasurer(); $this->validZonalPost('zonaltreasurer/assets');
        $name = trim((string)($_POST['name'] ?? '')); $serial = trim((string)($_POST['serial'] ?? ''));
        $category = trim((string)($_POST['category'] ?? '')); $date = trim((string)($_POST['purchase_date'] ?? ''));
        $valuation = (float)str_replace(',', '', (string)($_POST['valuation'] ?? ''));
        $categories = ['Audio Video Equipment', 'Events Equipment', 'Sports', 'Official Equipment'];
        $dateValue = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ($name === '' || mb_strlen($name) > 150 || $serial === '' || mb_strlen($serial) > 80 || !in_array($category, $categories, true)
            || !$dateValue || $dateValue->format('Y-m-d') !== $date || $valuation <= 0) {
            $_SESSION['zonal_treasurer_flash'] = 'Provide an asset name, serial, category, valid purchase date and positive valuation.';
        } else {
            $assets = $this->assetState();
            foreach ($assets as $asset) if (strcasecmp($asset['serial'], $serial) === 0) { $_SESSION['zonal_treasurer_flash'] = 'That asset serial already exists in the zonal inventory.'; $this->redirect('zonaltreasurer/assets'); }
            $assets[] = ['id' => 'ZA-' . (201 + count($assets)), 'name' => $name, 'category' => $category, 'serial' => $serial,
                'purchase_date' => $dateValue->format('M j, Y'), 'valuation' => $valuation, 'custodian' => 'Gampaha Zone Store', 'status' => 'Available', 'status_key' => 'available'];
            $this->saveAssetState($assets); $_SESSION['zonal_treasurer_flash'] = 'Asset registered in the Gampaha Zone inventory.';
        }
        $this->redirect('zonaltreasurer/assets');
    }

    public function transferasset() {
        $this->requireZonalTreasurer(); $this->validZonalPost('zonaltreasurer/assets');
        $id = trim((string)($_POST['asset_id'] ?? '')); $custodian = trim((string)($_POST['custodian'] ?? ''));
        $note = trim((string)($_POST['note'] ?? '')); $allowed = ['Gampaha Division', 'Ja-Ela Division', 'Negombo Division', 'Gampaha Zone Store'];
        $assets = $this->assetState(); $found = null;
        foreach ($assets as $index => $asset) if ($asset['id'] === $id) { $found = $index; break; }
        if ($found === null || !in_array($custodian, $allowed, true) || $note === '' || mb_strlen($note) > 500) {
            $_SESSION['zonal_treasurer_flash'] = 'Choose a zonal custodian and provide a transfer note.';
        } else {
            $assets[$found]['custodian'] = $custodian; $assets[$found]['status'] = $custodian === 'Gampaha Zone Store' ? 'Available' : 'In use';
            $assets[$found]['status_key'] = $custodian === 'Gampaha Zone Store' ? 'available' : 'inuse';
            $this->saveAssetState($assets); $_SESSION['zonal_treasurer_flash'] = 'Custody transfer recorded for the zonal asset.';
        }
        $this->redirect('zonaltreasurer/assets');
    }

    public function exportassets() {
        $this->requireZonalTreasurer();
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="Gampaha_Zone_Assets.csv"');
        $out = fopen('php://output', 'w'); fputcsv($out, ['Asset ID', 'Asset', 'Category', 'Serial', 'Purchase date', 'Valuation (LKR)', 'Custodian', 'Status']);
        foreach ($this->assetState() as $asset) fputcsv($out, [$asset['id'], $asset['name'], $asset['category'], $asset['serial'], $asset['purchase_date'], number_format($asset['valuation'], 2, '.', ''), $asset['custodian'], $asset['status']]);
        fclose($out);
    }

    /**
     * Record and view Gampaha Zone income and expenses with a running balance.
     */
    public function ledger() {
        $this->requireZonalTreasurer();
        $entries = $_SESSION['zonal_ledger_demo'][(string)($_SESSION['user_id'] ?? 'demo')] ?? [
            ['id' => 'ZL-101', 'date' => 'Sep 20, 2026', 'description' => 'NYSC allocation received', 'type' => 'Income', 'type_key' => 'income', 'amount' => 1500000, 'receipt' => 'NYSC-ALLOC-2026-091.pdf', 'status' => 'Recorded', 'status_key' => 'verified'],
            ['id' => 'ZL-102', 'date' => 'Sep 18, 2026', 'description' => 'Division allocation — Ja-Ela', 'type' => 'Expense', 'type_key' => 'expense', 'amount' => 200000, 'receipt' => 'GZ-DIV-2026-004.pdf', 'status' => 'Recorded', 'status_key' => 'verified'],
            ['id' => 'ZL-103', 'date' => 'Sep 12, 2026', 'description' => 'Zone leadership forum venue', 'type' => 'Expense', 'type_key' => 'expense', 'amount' => 85000, 'receipt' => 'GZ-EVT-2026-014.pdf', 'status' => 'Recorded', 'status_key' => 'verified'],
        ];
        $_SESSION['zonal_ledger_demo'][(string)($_SESSION['user_id'] ?? 'demo')] = $entries;
        $balance = 0;
        foreach (array_reverse($entries) as $entry) $balance += $entry['type_key'] === 'income' ? $entry['amount'] : -$entry['amount'];
        foreach ($entries as &$entry) { $entry['balance'] = $balance; $balance -= $entry['type_key'] === 'income' ? $entry['amount'] : -$entry['amount']; } unset($entry);
        $data = $this->shell(
            'Zonal Ledger — YouthNexus Pulse',
            'Zonal Ledger',
            'Record Gampaha Zone income and expenses with a running balance.',
            'zonaltreasurer/ledger'
        );
        $data += ['entries' => $entries, 'balance' => $entries ? $entries[0]['balance'] : 0,
            'income' => array_sum(array_map(static fn($entry) => $entry['type_key'] === 'income' ? $entry['amount'] : 0, $entries)),
            'expenses' => array_sum(array_map(static fn($entry) => $entry['type_key'] === 'expense' ? $entry['amount'] : 0, $entries)),
            'csrf_token' => $this->zonalCsrf(), 'flash' => $_SESSION['zonal_ledger_flash'] ?? ''];
        unset($_SESSION['zonal_ledger_flash']);
        $this->view('zonaltreasurer/ledger', $data);
    }

    public function logtransaction() {
        $this->requireZonalTreasurer(); $this->validZonalPost('zonaltreasurer/ledger');
        $type = trim((string)($_POST['type'] ?? '')); $amount = (float)str_replace(',', '', (string)($_POST['amount'] ?? ''));
        $date = trim((string)($_POST['transaction_date'] ?? '')); $description = trim((string)($_POST['description'] ?? '')); $receipt = trim((string)($_POST['receipt'] ?? ''));
        $dateValue = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!in_array($type, ['Income', 'Expense'], true) || $amount <= 0 || !$dateValue || $dateValue->format('Y-m-d') !== $date
            || $description === '' || mb_strlen($description) > 255 || $receipt === '' || mb_strlen($receipt) > 100) {
            $_SESSION['zonal_ledger_flash'] = 'Provide a transaction type, positive amount, date, description and receipt reference.';
        } else {
            $key = (string)($_SESSION['user_id'] ?? 'demo'); $entries = $_SESSION['zonal_ledger_demo'][$key] ?? [];
            array_unshift($entries, ['id' => 'ZL-' . (101 + count($entries)), 'date' => $dateValue->format('M j, Y'), 'description' => $description,
                'type' => $type, 'type_key' => strtolower($type), 'amount' => $amount, 'receipt' => $receipt, 'status' => 'Recorded', 'status_key' => 'verified']);
            $_SESSION['zonal_ledger_demo'][$key] = $entries; $_SESSION['zonal_ledger_flash'] = 'Zonal transaction recorded for this session.';
        }
        $this->redirect('zonaltreasurer/ledger');
    }

    public function exportzonalledger() {
        $this->requireZonalTreasurer(); $key = (string)($_SESSION['user_id'] ?? 'demo'); $entries = $_SESSION['zonal_ledger_demo'][$key] ?? [];
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="Gampaha_Zone_Ledger.csv"');
        $out = fopen('php://output', 'w'); fputcsv($out, ['Entry', 'Date', 'Description', 'Type', 'Amount (LKR)', 'Receipt reference', 'Status']);
        foreach ($entries as $entry) fputcsv($out, [$entry['id'], $entry['date'], $entry['description'], $entry['type'], number_format($entry['amount'], 2, '.', ''), $entry['receipt'], $entry['status']]);
        fclose($out);
    }

    private function voidState() {
        $key = (string)($_SESSION['user_id'] ?? 'demo');
        if (!isset($_SESSION['zonal_void_demo'][$key])) {
            $_SESSION['zonal_void_demo'][$key] = [
                ['id' => 'ZV-101', 'division' => 'Gampaha Division', 'reference' => 'GAM-EXP-2026-034', 'description' => 'Programme transport reimbursement', 'amount' => 18500, 'requested_on' => 'Sep 20, 2026', 'reason' => 'Duplicate reimbursement entry identified after reconciliation.', 'status' => 'Pending'],
                ['id' => 'ZV-102', 'division' => 'Ja-Ela Division', 'reference' => 'JAE-EXP-2026-041', 'description' => 'Workshop materials purchase', 'amount' => 12400, 'requested_on' => 'Sep 19, 2026', 'reason' => 'Supplier invoice was entered under the wrong programme code.', 'status' => 'Pending'],
                ['id' => 'ZV-103', 'division' => 'Negombo Division', 'reference' => 'NEG-EXP-2026-017', 'description' => 'Venue deposit', 'amount' => 25000, 'requested_on' => 'Sep 16, 2026', 'reason' => 'Event was cancelled before the venue confirmation date.', 'status' => 'Approved'],
            ];
        }
        return $_SESSION['zonal_void_demo'][$key];
    }

    private function saveVoidState(array $requests) {
        $_SESSION['zonal_void_demo'][(string)($_SESSION['user_id'] ?? 'demo')] = $requests;
    }

    /**
     * Review pending requests submitted by divisions within the zone.
     */
    public function voids() {
        $this->requireZonalTreasurer();
        $requests = $this->voidState();
        $status = trim((string)($_GET['status'] ?? 'Pending'));
        if (!in_array($status, ['Pending', 'Approved', 'Rejected', ''], true)) $status = 'Pending';
        $search = strtolower(trim((string)($_GET['search'] ?? '')));
        $visible = array_values(array_filter($requests, static function ($request) use ($status, $search) {
            return ($status === '' || $request['status'] === $status)
                && ($search === '' || str_contains(strtolower($request['division'] . ' ' . $request['reference'] . ' ' . $request['description']), $search));
        }));
        $data = $this->shell(
            'Void Requests — YouthNexus Pulse',
            'Void Requests',
            'Review division void requests within Gampaha Zone.',
            'zonaltreasurer/voids'
        );
        $data += ['requests' => $visible, 'status' => $status, 'search' => $search,
            'pendingCount' => count(array_filter($requests, static fn($request) => $request['status'] === 'Pending')),
            'csrf_token' => $this->zonalCsrf(), 'flash' => $_SESSION['zonal_void_flash'] ?? ''];
        unset($_SESSION['zonal_void_flash']);
        $this->view('zonaltreasurer/voids', $data);
    }

    private function decideVoid($decision) {
        $this->requireZonalTreasurer(); $this->validZonalPost('zonaltreasurer/voids');
        $id = trim((string)($_POST['void_id'] ?? '')); $remark = trim((string)($_POST['remark'] ?? ''));
        $requests = $this->voidState(); $found = null;
        foreach ($requests as $index => $request) if ($request['id'] === $id) { $found = $index; break; }
        if ($found === null || $requests[$found]['status'] !== 'Pending' || $remark === '' || mb_strlen($remark) > 1000) {
            $_SESSION['zonal_void_flash'] = 'A pending division request and a decision remark are required.';
        } else {
            $requests[$found]['status'] = $decision; $requests[$found]['remark'] = $remark;
            $this->saveVoidState($requests); $_SESSION['zonal_void_flash'] = 'Void request ' . strtolower($decision) . ' and the division has been notified.';
        }
        $this->redirect('zonaltreasurer/voids');
    }

    public function approvevoid() { $this->decideVoid('Approved'); }

    public function rejectvoid() { $this->decideVoid('Rejected'); }

    public function exportvoids() {
        $this->requireZonalTreasurer();
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="Gampaha_Zone_Division_Void_Requests.csv"');
        $out = fopen('php://output', 'w'); fputcsv($out, ['Request', 'Division', 'Transaction reference', 'Description', 'Amount (LKR)', 'Requested on', 'Reason', 'Status', 'Decision remark']);
        foreach ($this->voidState() as $request) fputcsv($out, [$request['id'], $request['division'], $request['reference'], $request['description'], number_format($request['amount'], 2, '.', ''), $request['requested_on'], $request['reason'], $request['status'], $request['remark'] ?? '']);
        fclose($out);
    }
}
