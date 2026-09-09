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
     * Club roster. Full role-assignment + registration flows land in C3.
     */
    public function members() {
        $this->requireRoles(['president', 'secretary']);

        $roster = [
            ['name' => 'Nuwan Bandara',  'role' => 'President', 'email' => 'nuwan@example.test',  'phone' => '+94 77 556 6778', 'address' => '45 Main Street, Gampaha',  'nic' => '199012345678', 'joined' => 'Jan 2024', 'status' => 'Active', 'status_key' => 'active'],
            ['name' => 'Amal Perera',    'role' => 'Secretary', 'email' => 'amal@example.test',   'phone' => '+94 71 223 4455', 'address' => '78 Station Road, Gampaha', 'nic' => '199512345678', 'joined' => 'Feb 2024', 'status' => 'Active', 'status_key' => 'active'],
            ['name' => 'Kasun Fernando', 'role' => 'Treasurer', 'email' => 'kasun@example.test',  'phone' => '+94 76 889 0011', 'address' => '7 Hill Street, Gampaha',   'nic' => '199812345678', 'joined' => 'Mar 2024', 'status' => 'Active', 'status_key' => 'active'],
            ['name' => 'Dilini Jayasuriya', 'role' => 'Member', 'email' => 'dilini@example.test', 'phone' => '+94 72 334 5566', 'address' => '9 Park Avenue, Gampaha',   'nic' => '200112345679', 'joined' => 'Jun 2024', 'status' => 'Active', 'status_key' => 'active'],
            ['name' => 'Ruwan Silva',    'role' => 'Member',    'email' => 'ruwan@example.test',  'phone' => '+94 75 667 8899', 'address' => '33 Temple Road, Gampaha', 'nic' => '200212345678', 'joined' => 'Aug 2024', 'status' => 'Active', 'status_key' => 'active'],
            ['name' => 'Sanduni Wickrama', 'role' => 'Member',  'email' => 'sanduni@example.test','phone' => '+94 78 112 3344', 'address' => '12 Lake Road, Gampaha',   'nic' => '200512345678', 'joined' => 'Jan 2025', 'status' => 'Pending', 'status_key' => 'pending'],
        ];

        $data = $this->shell(
            'Club Members — YouthNexus Pulse',
            'Club Members',
            'Roster of Gampaha Youth Development Club.',
            'club/members'
        );
        $data['stats'] = ['total' => 45, 'executives' => 3, 'members' => 41, 'pending' => 1];
        $data['roster'] = $roster;
        $data['can_manage'] = ($this->roleKey() === 'president');
        $data['can_register'] = ($this->roleKey() === 'secretary');
        // Mock NIC registry for the duplicate check (C3 demo; backend validates in C13).
        $data['existing_nics'] = ['200112345678', '199912345678', '200012345678'];

        $this->view('club/members', $data);
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

        $this->view('club/assets', $data);
    }

    /**
     * Club fund ledger (read-only summary). Log + void flows land in C8.
     */
    public function ledger() {
        $this->requireRoles(['president', 'treasurer']);

        $transactions = [
            ['id' => 1, 'date' => 'Sep 2, 2026',  'description' => 'Membership drive collections', 'type' => 'Income',  'type_key' => 'income',  'amount' => 'Rs. 24,500',  'balance' => 'Rs. 132,400', 'status' => 'Verified', 'status_key' => 'verified'],
            ['id' => 2, 'date' => 'Aug 28, 2026', 'description' => 'Sports equipment purchase',    'type' => 'Expense', 'type_key' => 'expense', 'amount' => 'Rs. 18,000',  'balance' => 'Rs. 107,900', 'status' => 'Verified', 'status_key' => 'verified'],
            ['id' => 3, 'date' => 'Aug 15, 2026', 'description' => 'Divisional grant received',    'type' => 'Income',  'type_key' => 'income',  'amount' => 'Rs. 50,000',  'balance' => 'Rs. 125,900', 'status' => 'Verified', 'status_key' => 'verified'],
            ['id' => 4, 'date' => 'Aug 9, 2026',  'description' => 'Venue hire for seminar',       'type' => 'Expense', 'type_key' => 'expense', 'amount' => 'Rs. 7,500',   'balance' => 'Rs. 75,900',  'status' => 'Pending Void',  'status_key' => 'pending-void'],
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
