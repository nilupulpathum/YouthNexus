<?php

/**
 * Club — shared club-scope pages for club-level executives.
 *
 * Presentation-only (C1): mock data mirroring the future backend contract.
 * No database reads or writes. Full interactions land in C3/C4/C7/C8.
 *
 * Routes (see dashboard-sidebar.view.php):
 *   club/members -> members()  (president, secretary)
 *   club/events  -> events()   (president, secretary)
 *   club/assets  -> assets()   (president, treasurer)
 *   club/ledger  -> ledger()   (president, treasurer)
 */
class Club extends Controller {

    /**
     * Normalise session role the same way the sidebar does.
     */
    private function roleKey() {
        $key = strtolower(trim((string) ($_SESSION['user_role'] ?? '')));
        $key = preg_replace('/[^a-z]/', '', $key);
        $aliases = [
            'clubpresident' => 'president',
            'clubsecretary' => 'secretary',
            'clubtreasurer' => 'treasurer',
            'clubmember'    => 'member',
        ];
        return $aliases[$key] ?? $key;
    }

    private function requireRoles(array $allowed) {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        if (!in_array($this->roleKey(), $allowed, true)) {
            $this->redirect('home');
        }
        if ((int) ($_SESSION['club_id'] ?? 0) < 1) {
            http_response_code(403);
            exit('Your user account is not assigned to a club.');
        }
    }

    /**
     * Base view data every club page needs for the shared shell.
     */
    private function shell($title, $pageTitle, $pageDescription, $currentRoute) {
        $memberName = trim((string) ($_SESSION['user_name'] ?? '')) ?: 'YouthNexus User';
        return [
            'title'                   => $title,
            'pageTitle'               => $pageTitle,
            'pageDescription'         => $pageDescription,
            'currentRoute'            => $currentRoute,
            'userRole'                => $_SESSION['user_role'] ?? 'ClubPresident',
            'userName'                => $memberName,
            'userEmail'               => $_SESSION['user_email'] ?? '',
            'userInitials'            => $_SESSION['user_initials'] ?? '',
            'unreadNotificationCount' => 2,
        ];
    }

    public function index() {
        $this->redirect('home');
    }

    /**
     * Club roster (D1: real DB data scoped to the member's club).
     */
    public function members() {
        $this->requireRoles(['president', 'secretary']);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $userModel = $this->model('UserModel');

        $roleLabels = [
            'ClubPresident' => 'President',
            'ClubSecretary' => 'Secretary',
            'ClubTreasurer' => 'Treasurer',
            'ClubMember'    => 'Member',
            'Member'        => 'Member',
        ];
        $formatJoined = static function ($row) {
            $raw = $row->membership_date ?? $row->created_at ?? null;
            if (empty($raw)) {
                return '—';
            }
            $ts = strtotime((string) $raw);
            return $ts ? date('M Y', $ts) : '—';
        };

        $roster = [];
        foreach ($userModel->getClubRoster($clubId) as $u) {
            $roster[] = [
                'id'         => (int) $u->user_id,
                'name'       => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                'role'       => $roleLabels[$u->role] ?? $u->role,
                'email'      => $u->email ?? '',
                'phone'      => $u->phone_number ?? '—',
                'address'    => $u->address ?? '—',
                'nic'        => $u->NIC ?? '—',
                'joined'     => $formatJoined($u),
                'status'     => 'Active',
                'status_key' => 'active',
            ];
        }
        foreach ($userModel->getClubPending($clubId) as $u) {
            $roster[] = [
                'id'         => (int) $u->user_id,
                'name'       => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                'role'       => 'Member',
                'email'      => $u->email ?? '',
                'phone'      => $u->phone_number ?? '—',
                'address'    => $u->address ?? '—',
                'nic'        => $u->NIC ?? '—',
                'joined'     => $formatJoined($u),
                'status'     => 'Pending',
                'status_key' => 'pending',
            ];
        }

        $counts = $userModel->countClubRoster($clubId);

        $data = $this->shell(
            'Club Members - YouthNexus Pulse',
            'Club Members',
            'Roster of Gampaha Youth Development Club.',
            'club/members'
        );
        $data['stats'] = [
            'total'      => $counts['total'] + $counts['pending'],
            'executives' => $counts['executives'],
            'members'    => $counts['total'] - $counts['executives'],
            'pending'    => $counts['pending'],
        ];
        $data['roster'] = $roster;
        $data['can_manage'] = ($this->roleKey() === 'president');
        $data['can_register'] = ($this->roleKey() === 'secretary');
        $data['existing_nics'] = $userModel->getClubNics($clubId);
        $data['csrf_token'] = $_SESSION['csrf_token'];
        $data['flash'] = $this->pullFlash();

        $this->view('club/members', $data);
    }

    /**
     * Secretary registers a member into the president's approval queue.
     */
    public function register() {
        $this->requireRoles(['secretary']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('club/members');
        }
        if (!$this->verifyCsrf()) {
            $this->setFlash('error', 'Invalid request. Please try again.');
            $this->redirect('club/members');
        }

        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $nic     = trim($_POST['nic'] ?? '');

        if ($name === '' || $email === '' || $phone === '' || $address === '' || $nic === '') {
            $this->setFlash('error', 'All fields are required.');
            $this->redirect('club/members');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('error', 'Enter a valid email address.');
            $this->redirect('club/members');
        }

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $userModel = $this->model('UserModel');
        if ($userModel->emailOrNicTaken($email, $nic)) {
            $this->setFlash('error', 'This email or NIC is already registered.');
            $this->redirect('club/members');
        }

        $club = $this->model('ClubModel')->findById($clubId);
        $newId = $userModel->registerClubMember($clubId, (int) ($club->division_id ?? 0), $name, $email, $phone, $address, $nic);
        $this->model('AuditLogModel')->log($_SESSION['user_id'], 'REGISTER_MEMBER', 'User', $newId, "Registered {$name} ({$email})");
        $this->setFlash('success', $name . ' added — awaiting president approval.');
        $this->redirect('club/members');
    }

    private function verifyCsrf(): bool {
        $token = (string) ($_POST['csrf_token'] ?? '');
        return $token !== '' && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
    }

    private function setFlash(string $type, string $message): void {
        $_SESSION['club_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array {
        $flash = $_SESSION['club_flash'] ?? null;
        unset($_SESSION['club_flash']);
        return is_array($flash) ? $flash : null;
    }

    /**
     * Club events. C4: president approve/request-changes modal +
     * secretary create-event form (title, date/time, location, type,
     * budget + future-date validation). Presentation-only, no DB writes.
     */
    /**
     * Club events (D2: real DB data scoped to the member's club).
     */
    public function events() {
        $this->requireRoles(['president', 'secretary']);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $eventModel = $this->model('EventModel');

        $statusMap = [
            'PendingApproval' => ['Pending Approval', 'pending'],
            'Approved'        => ['Approved', 'approved'],
            'Completed'       => ['Completed', 'completed'],
            'Rejected'        => ['Changes Requested', 'rejected'],
            'Draft'           => ['Draft', 'draft'],
        ];
        $clubEvents = [];
        foreach ($eventModel->getClubEvents($clubId) as $ev) {
            [$label, $key] = $statusMap[$ev->status] ?? [$ev->status, 'pending'];
            $start = strtotime((string) $ev->start_datetime);
            $clubEvents[] = [
                'id'           => (int) $ev->event_id,
                'title'        => $ev->title ?? '',
                'date'         => $start ? date('M d, Y g:i A', $start) : '—',
                'location'     => $ev->location ?? '—',
                'type'         => $ev->event_type ?? '—',
                'submitted_by' => trim(($ev->creator_name ?? '') . ' (' . ($ev->creator_role ?? '') . ')'),
                'status'       => $label,
                'status_key'   => $key,
            ];
        }

        $counts = $eventModel->countClubEventsByStatus($clubId);

        $data = $this->shell(
            'Club Events - YouthNexus Pulse',
            'Club Events',
            'Events organised by Gampaha Youth Development Club.',
            'club/events'
        );
        $data['stats'] = [
            'pending'   => $counts['PendingApproval'],
            'approved'  => $counts['Approved'],
            'completed' => $counts['Completed'],
        ];
        $data['clubEvents'] = $clubEvents;
        $data['can_approve'] = ($this->roleKey() === 'president');
        $data['can_create'] = ($this->roleKey() === 'secretary');
        $data['csrf_token'] = $_SESSION['csrf_token'];
        $data['flash'] = $this->pullFlash();

        $this->view('club/events', $data);
    }

    /**
     * Secretary creates an event (enters as PendingApproval).
     */
    public function createEvent() {
        $this->requireRoles(['secretary']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('club/events');
        }
        if (!$this->verifyCsrf()) {
            $this->setFlash('error', 'Invalid request. Please try again.');
            $this->redirect('club/events');
        }

        $title    = trim($_POST['title'] ?? '');
        $date     = trim($_POST['date'] ?? '');
        $time     = trim($_POST['time'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $type     = trim($_POST['type'] ?? '');
        if ($title === '' || $date === '' || $time === '' || $location === '' || $type === '') {
            $this->setFlash('error', 'All fields are required.');
            $this->redirect('club/events');
        }

        $start = strtotime($date . ' ' . $time);
        if (!$start || $start <= time()) {
            $this->setFlash('error', 'Event date must be in the future.');
            $this->redirect('club/events');
        }

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $club = $this->model('ClubModel')->findById($clubId);
        $eventId = $this->model('EventModel')->createClubEvent(
            $clubId, (int) ($club->division_id ?? 0), (int) $_SESSION['user_id'],
            substr($title, 0, 150), $type, $location,
            date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $start + 7200)
        );
        $this->model('AuditLogModel')->log($_SESSION['user_id'], 'CREATE_EVENT', 'Event', $eventId, "Created '{$title}'");
        $this->setFlash('success', 'Event submitted for approval.');
        $this->redirect('club/events');
    }

    /**
     * Secretary marks an approved event completed with attendance evidence.
     */
    public function completeEvent() {
        $this->requireRoles(['secretary']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('club/events');
        }
        if (!$this->verifyCsrf()) {
            $this->setFlash('error', 'Invalid request. Please try again.');
            $this->redirect('club/events');
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);
        if ($eventId < 1) {
            $this->setFlash('error', 'Invalid event.');
            $this->redirect('club/events');
        }

        try {
            $evidenceUrl = EventEvidenceStorage::store($_FILES['sheet'] ?? null);
        } catch (InvalidArgumentException | RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('club/events');
        }

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $rows = $this->model('EventModel')->completeClubEvent($clubId, $eventId);
        if ($rows < 1) {
            $this->setFlash('error', 'Event not found in your club.');
            $this->redirect('club/events');
        }
        $this->model('AuditLogModel')->log($_SESSION['user_id'], 'COMPLETE_EVENT', 'Event', $eventId, "Completed with evidence {$evidenceUrl}");
        $this->setFlash('success', 'Event marked complete - evidence saved.');
        $this->redirect('club/events');
    }

    /**
     * Club attendance (C5 mock, club-scoped). Secretary marks attendance
     * (event list, CSV bulk OR single entry, start/end time + remarks,
     * unlisted members default to absent); members see their own summary.
     * Presentation-only, no DB writes. Divisional Attendance untouched.
     */
    /**
     * Club attendance (D3: real DB data scoped to the member's club).
     */
    public function attendance() {
        $this->requireRoles(['secretary', 'member']);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $attModel = $this->model('AttendanceModel');

        $data = $this->shell(
            'Club Attendance - YouthNexus Pulse',
            'Club Attendance',
            'Attendance records of Gampaha Youth Development Club.',
            'club/attendance'
        );
        $data['csrf_token'] = $_SESSION['csrf_token'];
        $data['flash'] = $this->pullFlash();
        $data['can_mark'] = ($this->roleKey() === 'secretary');

        if ($data['can_mark']) {
            $events = [];
            foreach ($attModel->getClubMarkableEvents($clubId) as $ev) {
                $start = strtotime((string) $ev->start_datetime);
                $roster = [];
                foreach ($attModel->getClubEventRoster($clubId, (int) $ev->event_id) as $r) {
                    $present = ($r->att_status ?? null) === 'Present';
                    $roster[] = [
                        'id'         => (int) $r->user_id,
                        'name'       => trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')),
                        'status'     => $r->att_status === null ? 'Unmarked' : ($present ? 'Present' : 'Absent'),
                        'status_key' => $r->att_status === null ? 'unmarked' : ($present ? 'present' : 'absent'),
                    ];
                }
                $events[] = [
                    'id'     => (int) $ev->event_id,
                    'title'  => $ev->title ?? '',
                    'date'   => $start ? date('M d, Y', $start) : '—',
                    'status' => $ev->status ?? '',
                    'roster' => $roster,
                ];
            }
            $data['attendanceEvents'] = $events;
            $members = [];
            foreach ($this->model('UserModel')->getClubRoster($clubId) as $u) {
                $members[] = [
                    'id'   => (int) $u->user_id,
                    'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                ];
            }
            $data['members'] = $members;
        } else {
            $summary = $attModel->getClubMemberSummary($clubId, (int) $_SESSION['user_id']);
            $history = [];
            foreach ($summary['history'] as $h) {
                $ts = strtotime((string) $h->start_datetime);
                $present = ($h->status ?? '') === 'Present';
                $history[] = [
                    'title'      => $h->title ?? '',
                    'date'       => $ts ? date('M d, Y', $ts) : '—',
                    'status'     => $present ? 'Present' : 'Absent',
                    'status_key' => $present ? 'present' : 'absent',
                ];
            }
            $data['mySummary'] = [
                'sessions' => $summary['sessions'],
                'rate'     => $summary['rate'],
                'absent'   => $summary['absent'],
                'history'  => $history,
            ];
        }

        $this->view('club/attendance', $data);
    }

    /**
     * Secretary saves attendance (single entry or bulk CSV).
     */
    public function saveAttendance() {
        $this->requireRoles(['secretary']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('club/attendance');
        }
        if (!$this->verifyCsrf()) {
            $this->setFlash('error', 'Invalid request. Please try again.');
            $this->redirect('club/attendance');
        }

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $attModel = $this->model('AttendanceModel');
        $event = $attModel->getClubEventInScope($clubId, $eventId);
        if (!$event) {
            $this->setFlash('error', 'Event not found in your club.');
            $this->redirect('club/attendance');
        }

        $in = $this->combineEventTime($event->start_datetime, trim($_POST['check_in'] ?? ''));
        $out = $this->combineEventTime($event->start_datetime, trim($_POST['check_out'] ?? ''));
        if ($in === false || $out === false || ($in && $out && $out <= $in)) {
            $this->setFlash('error', 'End time must be after start time.');
            $this->redirect('club/attendance');
        }
        $remark = substr(trim($_POST['remark'] ?? ''), 0, 500);

        $entries = [];
        if (!empty($_FILES['csv']['tmp_name']) && $_FILES['csv']['error'] === UPLOAD_ERR_OK) {
            $entries = $this->parseAttendanceCsv($_FILES['csv']['tmp_name'], $clubId);
            if ($entries === null) {
                $this->setFlash('error', 'Could not read the CSV file.');
                $this->redirect('club/attendance');
            }
        } else {
            $memberId = (int) ($_POST['single_member'] ?? 0);
            $status = $_POST['single_status'] ?? '';
            if ($memberId < 1 || !in_array($status, ['Present', 'Absent'], true)) {
                $this->setFlash('error', 'Choose a member and a status.');
                $this->redirect('club/attendance');
            }
            $entries[] = ['id' => $memberId, 'status' => $status];
        }

        $saved = 0;
        $skipped = 0;
        foreach ($entries as $entry) {
            if ($attModel->saveClubAttendance($clubId, $eventId, (int) $entry['id'], $entry['status'], $in, $out, $remark ?: null, (int) $_SESSION['user_id'])) {
                $saved++;
            } else {
                $skipped++;
            }
        }

        $this->model('AuditLogModel')->log($_SESSION['user_id'], 'SAVE_ATTENDANCE', 'Event', $eventId, "Saved {$saved} record(s), skipped {$skipped}");
        $msg = "Saved {$saved} attendance record(s).";
        if ($skipped > 0) {
            $msg .= " {$skipped} unmatched.";
        }
        $this->setFlash($saved > 0 ? 'success' : 'error', $msg);
        $this->redirect('club/attendance');
    }

    private function combineEventTime($eventStart, $hhmm) {
        if ($hhmm === '' || $hhmm === null) {
            return null;
        }
        if (!preg_match('/^\d{2}:\d{2}$/', (string) $hhmm)) {
            return false;
        }
        $day = date('Y-m-d', strtotime((string) $eventStart));
        return $day . ' ' . $hhmm . ':00';
    }

    private function parseAttendanceCsv($path, $clubId) {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return null;
        }
        $roster = [];
        foreach ($this->model('UserModel')->getClubRoster($clubId) as $u) {
            $roster[strtolower(trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')))] = (int) $u->user_id;
            $roster[strtolower(trim($u->email ?? ''))] = (int) $u->user_id;
        }
        $entries = [];
        $first = true;
        while (($row = fgetcsv($handle)) !== false) {
            $name = strtolower(trim($row[0] ?? ''));
            $statusRaw = strtolower(trim($row[1] ?? ''));
            if ($first && ($name === 'name' || $name === 'email' || $name === '')) {
                $first = false;
                if ($name === 'name' || $name === 'email') {
                    continue;
                }
            }
            $first = false;
            if ($name === '' || !isset($roster[$name])) {
                continue;
            }
            if (in_array($statusRaw, ['present', 'p'], true)) {
                $status = 'Present';
            } elseif (in_array($statusRaw, ['absent', 'a'], true)) {
                $status = 'Absent';
            } else {
                continue;
            }
            $entries[] = ['id' => $roster[$name], 'status' => $status];
        }
        fclose($handle);
        return $entries;
    }

    /**
     * Club asset inventory (D4: real stock/request data, Club scope).
     */
    public function assets() {
        $this->requireRoles(['president', 'treasurer', 'secretary']);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $assetModel = $this->model('ClubAssetModel');

        $assets = [];
        foreach ($assetModel->getInventory($clubId) as $s) {
            $assets[] = [
                'id'       => (int) $s->catalog_item_id,
                'name'     => $s->item_name ?? '',
                'category' => $s->category ?? '',
                'sku'      => $s->sku ?? '',
                'unit'     => $s->unit ?? '',
                'quantity' => (int) $s->quantity,
                'status'   => 'Available',
                'status_key' => 'available',
            ];
        }

        $requests = [];
        foreach ($assetModel->getRequests($clubId) as $r) {
            $ts = strtotime((string) $r->requested_at);
            $requests[] = [
                'item'       => $r->item_name ?? '',
                'category'   => $r->category ?? '',
                'quantity'   => (int) $r->quantity,
                'reason'     => $r->reason ?? '',
                'date'       => $ts ? date('M d, Y', $ts) : '—',
                'status'     => $r->status ?? '',
                'status_key' => strtolower($r->status ?? ''),
            ];
        }

        $catalog = $assetModel->getCatalog();
        $categories = [];
        foreach ($catalog as $item) {
            $categories[$item->category] = true;
        }

        $custodians = [];
        foreach ($this->model('UserModel')->getClubRoster($clubId) as $u) {
            $custodians[] = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
        }

        $summary = $assetModel->getSummary($clubId);

        $data = $this->shell(
            'Club Assets — YouthNexus Pulse',
            'Club Assets',
            'Inventory held by Gampaha Youth Development Club.',
            'club/assets'
        );
        $data['stats'] = [
            'units'         => $summary['units'],
            'items'         => $summary['items'],
            'open_requests' => $summary['open_requests'],
        ];
        $data['assets'] = $assets;
        $data['can_transfer'] = ($this->roleKey() === 'treasurer');
        $data['can_register'] = ($this->roleKey() === 'secretary');
        $data['can_request'] = ($this->roleKey() === 'treasurer');
        $data['divisionRequests'] = $requests;
        $data['catalog'] = $catalog;
        $data['categories'] = array_keys($categories);
        $data['custodians'] = $custodians;
        $data['csrf_token'] = $_SESSION['csrf_token'];
        $data['flash'] = $this->pullFlash();

        $this->view('club/assets', $data);
    }

    /**
     * Secretary records stock into the club inventory.
     */
    public function registerAsset() {
        $this->requireRoles(['secretary']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('club/assets');
        }
        if (!$this->verifyCsrf()) {
            $this->setFlash('error', 'Invalid request. Please try again.');
            $this->redirect('club/assets');
        }

        $itemId = (int) ($_POST['catalog_item_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);
        $note = substr(trim($_POST['note'] ?? ''), 0, 500);

        try {
            $this->model('ClubAssetModel')->addStock((int) ($_SESSION['club_id'] ?? 0), $itemId, $quantity, (int) $_SESSION['user_id'], $note);
        } catch (InvalidArgumentException $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('club/assets');
        }
        $this->setFlash('success', 'Stock recorded in the club inventory.');
        $this->redirect('club/assets');
    }

    /**
     * Treasurer records a custody transfer inside the club.
     */
    public function transferAsset() {
        $this->requireRoles(['treasurer']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('club/assets');
        }
        if (!$this->verifyCsrf()) {
            $this->setFlash('error', 'Invalid request. Please try again.');
            $this->redirect('club/assets');
        }

        $itemId = (int) ($_POST['catalog_item_id'] ?? 0);
        $custodian = trim($_POST['custodian'] ?? '');
        $date = trim($_POST['transfer_date'] ?? '');
        $note = substr(trim($_POST['note'] ?? ''), 0, 500);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->setFlash('error', 'Enter a valid transfer date.');
            $this->redirect('club/assets');
        }

        try {
            $this->model('ClubAssetModel')->transferCustody((int) ($_SESSION['club_id'] ?? 0), $itemId, $custodian, $date, $note, (int) $_SESSION['user_id']);
        } catch (InvalidArgumentException $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('club/assets');
        }
        $this->setFlash('success', 'Custody transfer recorded.');
        $this->redirect('club/assets');
    }

    /**
     * Treasurer requests stock from the division queue.
     */
    public function requestAsset() {
        $this->requireRoles(['treasurer']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('club/assets');
        }
        if (!$this->verifyCsrf()) {
            $this->setFlash('error', 'Invalid request. Please try again.');
            $this->redirect('club/assets');
        }

        $itemId = (int) ($_POST['catalog_item_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);
        $reason = substr(trim($_POST['reason'] ?? ''), 0, 500);

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $club = $this->model('ClubModel')->findById($clubId);
        try {
            $this->model('ClubAssetModel')->requestFromDivision($clubId, (int) ($club->division_id ?? 0), $itemId, $quantity, (int) $_SESSION['user_id'], $reason);
        } catch (InvalidArgumentException $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('club/assets');
        }
        $this->setFlash('success', 'Request sent to the division queue.');
        $this->redirect('club/assets');
    }

    /**
     * Club fund ledger (read-only summary). Log + void flows land in C8.
     */
    public function ledger() {
        $this->requireRoles(['president', 'treasurer']);

        $transactions = [
            ['id' => 1, 'date' => 'Sep 2, 2026',  'description' => 'Membership drive collections', 'type' => 'Income',  'type_key' => 'income',  'amount' => 'Rs. 24,500',  'balance' => 'Rs. 132,400', 'receipt' => 'receipt-sep-drive.txt',  'status' => 'Verified', 'status_key' => 'verified'],
            ['id' => 2, 'date' => 'Aug 28, 2026', 'description' => 'Sports equipment purchase',    'type' => 'Expense', 'type_key' => 'expense', 'amount' => 'Rs. 18,000',  'balance' => 'Rs. 107,900', 'receipt' => 'receipt-sports-gear.txt', 'status' => 'Verified', 'status_key' => 'verified'],
            ['id' => 3, 'date' => 'Aug 15, 2026', 'description' => 'Divisional grant received',    'type' => 'Income',  'type_key' => 'income',  'amount' => 'Rs. 50,000',  'balance' => 'Rs. 125,900', 'receipt' => 'receipt-div-grant.txt',   'status' => 'Verified', 'status_key' => 'verified'],
            ['id' => 4, 'date' => 'Aug 9, 2026',  'description' => 'Venue hire for seminar',       'type' => 'Expense', 'type_key' => 'expense', 'amount' => 'Rs. 7,500',   'balance' => 'Rs. 75,900',  'receipt' => 'venue-quote-aug.txt',     'status' => 'Pending Void',  'status_key' => 'pending-void'],
        ];

        $data = $this->shell(
            'Club Ledger — YouthNexus Pulse',
            'Club Ledger',
            'Fund position of Gampaha Youth Development Club.',
            'club/ledger'
        );
        $data['stats'] = ['balance' => 'Rs. 132,400', 'income' => 'Rs. 74,500', 'expenses' => 'Rs. 25,500'];
        $data['transactions'] = $transactions;
        $data['can_log'] = ($this->roleKey() === 'treasurer');

        $this->view('club/ledger', $data);
    }
}
