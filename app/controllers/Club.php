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
    public function events() {
        $this->requireRoles(['president', 'secretary']);

        $clubEvents = [
            ['id' => 1, 'title' => 'Gampaha Youth Leadership Workshop 2026', 'date' => 'Sep 15, 2026 · 9:00 AM', 'datetime' => '2026-09-15T09:00', 'location' => 'Gampaha Town Hall', 'type' => 'Workshop', 'budget' => 'Rs. 45,000', 'submitted_by' => 'Amal Perera (Secretary)', 'status' => 'Pending Approval', 'status_key' => 'pending'],
            ['id' => 2, 'title' => 'Community Green Environment Cleanup',    'date' => 'Sep 28, 2026 · 8:00 AM', 'datetime' => '2026-09-28T08:00', 'location' => 'Gampaha Central Park', 'type' => 'Community Service', 'budget' => 'Rs. 12,000', 'submitted_by' => 'Amal Perera (Secretary)', 'status' => 'Approved',         'status_key' => 'approved'],
            ['id' => 3, 'title' => 'Club Monthly Planning Session',          'date' => 'Oct 5, 2026 · 5:00 PM',  'datetime' => '2026-10-05T17:00', 'location' => 'Club Centre, Gampaha', 'type' => 'Meeting', 'budget' => 'Rs. 5,000', 'submitted_by' => 'Amal Perera (Secretary)', 'status' => 'Approved',         'status_key' => 'approved'],
            ['id' => 4, 'title' => 'Avurudu Celebration & Fundraiser',       'date' => 'Apr 12, 2026 · 10:00 AM', 'datetime' => '2026-04-12T10:00', 'location' => 'Club Centre, Gampaha', 'type' => 'Fundraiser', 'budget' => 'Rs. 30,000', 'submitted_by' => 'Amal Perera (Secretary)', 'status' => 'Completed',        'status_key' => 'completed'],
        ];

        $data = $this->shell(
            'Club Events — YouthNexus Pulse',
            'Club Events',
            'Events organised by Gampaha Youth Development Club.',
            'club/events'
        );
        $data['stats'] = ['pending' => 1, 'approved' => 2, 'completed' => 1];
        $data['clubEvents'] = $clubEvents;
        $data['can_approve'] = ($this->roleKey() === 'president');
        $data['can_create'] = ($this->roleKey() === 'secretary');

        $this->view('club/events', $data);
    }

    /**
     * Club attendance (C5 mock, club-scoped). Secretary marks attendance
     * (event list, CSV bulk OR single entry, start/end time + remarks,
     * unlisted members default to absent); members see their own summary.
     * Presentation-only, no DB writes. Divisional Attendance untouched.
     */
    public function attendance() {
        $this->requireRoles(['secretary', 'member']);

        $attendanceEvents = [
            ['id' => 4, 'title' => 'Avurudu Celebration & Fundraiser', 'date' => 'Apr 12, 2026',
                'roster' => [
                    ['name' => 'Nuwan Bandara',    'status' => 'Present', 'status_key' => 'present'],
                    ['name' => 'Amal Perera',      'status' => 'Present', 'status_key' => 'present'],
                    ['name' => 'Kasun Fernando',   'status' => 'Absent',  'status_key' => 'absent'],
                    ['name' => 'Dilini Jayasuriya','status' => 'Present', 'status_key' => 'present'],
                    ['name' => 'Ruwan Silva',      'status' => 'Absent',  'status_key' => 'absent'],
                ]],
            ['id' => 2, 'title' => 'Community Green Environment Cleanup', 'date' => 'Sep 28, 2026',
                'roster' => [
                    ['name' => 'Nuwan Bandara',    'status' => 'Present', 'status_key' => 'present'],
                    ['name' => 'Amal Perera',      'status' => 'Present', 'status_key' => 'present'],
                    ['name' => 'Kasun Fernando',   'status' => 'Present', 'status_key' => 'present'],
                    ['name' => 'Dilini Jayasuriya','status' => 'Absent',  'status_key' => 'absent'],
                    ['name' => 'Ruwan Silva',      'status' => 'Present', 'status_key' => 'present'],
                ]],
        ];

        $mySummary = [
            'sessions' => 8,
            'rate'     => '89%',
            'absent'   => 1,
            'history'  => [
                ['title' => 'Avurudu Celebration & Fundraiser', 'date' => 'Apr 12, 2026', 'status' => 'Present', 'status_key' => 'present'],
                ['title' => 'Community Green Environment Cleanup', 'date' => 'Sep 28, 2026', 'status' => 'Present', 'status_key' => 'present'],
                ['title' => 'Club Monthly Planning Session', 'date' => 'Oct 5, 2026', 'status' => 'Absent', 'status_key' => 'absent'],
            ],
        ];

        $data = $this->shell(
            'Club Attendance — YouthNexus Pulse',
            'Club Attendance',
            'Attendance records of Gampaha Youth Development Club.',
            'club/attendance'
        );
        $data['attendanceEvents'] = $attendanceEvents;
        $data['mySummary'] = $mySummary;
        $data['can_mark'] = ($this->roleKey() === 'secretary');

        $this->view('club/attendance', $data);
    }

    /**
     * Club asset inventory. C7: secretary register-asset modal +
     * treasurer transfer-custody flow (Available-only + custodian + date
     * + history note). Presentation-only, no DB writes.
     */
    public function assets() {
        $this->requireRoles(['president', 'treasurer', 'secretary']);

        $assets = [
            ['id' => 1, 'name' => 'Sound System (Portable PA)', 'serial' => 'AST-2024-001', 'category' => 'Audio Video Equipments', 'purchase_date' => 'Jan 12, 2024', 'valuation' => 'Rs. 85,000',  'status' => 'Available', 'status_key' => 'available', 'custodian' => 'Club Centre'],
            ['id' => 2, 'name' => 'Multimedia Projector',       'serial' => 'AST-2024-002', 'category' => 'Audio Video Equipments', 'purchase_date' => 'Mar 3, 2024',  'valuation' => 'Rs. 120,000', 'status' => 'Available', 'status_key' => 'available', 'custodian' => 'Club Centre'],
            ['id' => 3, 'name' => 'Cricket Gear Set',           'serial' => 'AST-2025-003', 'category' => 'Sports',                 'purchase_date' => 'Jun 20, 2025', 'valuation' => 'Rs. 45,000',  'status' => 'In Use',    'status_key' => 'inuse',     'custodian' => 'Ruwan Silva'],
            ['id' => 4, 'name' => 'First-Aid Kit',              'serial' => 'AST-2025-004', 'category' => 'Official Equipments',    'purchase_date' => 'Feb 8, 2025',  'valuation' => 'Rs. 12,000',  'status' => 'Available', 'status_key' => 'available', 'custodian' => 'Club Centre'],
        ];

        $data = $this->shell(
            'Club Assets — YouthNexus Pulse',
            'Club Assets',
            'Inventory held by Gampaha Youth Development Club.',
            'club/assets'
        );
        $data['stats'] = ['total' => 4, 'available' => 3, 'in_use' => 1, 'valuation' => 'Rs. 262,000'];
        $data['assets'] = $assets;
        $data['can_transfer'] = ($this->roleKey() === 'treasurer');
        $data['can_register'] = ($this->roleKey() === 'secretary');
        $data['can_request'] = ($this->roleKey() === 'treasurer');
        // Outbox for division requests (C18). Payload shape is the contract
        // the division developer consumes later (see C13): item, category,
        // quantity, justification, club, requested_by, date, status.
        $data['divisionRequests'] = [
            ['item' => 'Volleyball net', 'category' => 'Sports', 'quantity' => 2, 'justification' => 'Inter-club tournament, Sep 2026', 'date' => 'Sep 5, 2026', 'status' => 'Pending', 'status_key' => 'pending'],
        ];

        $this->view('club/assets', $data);
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
