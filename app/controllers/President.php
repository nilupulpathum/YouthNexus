<?php

/**
 * President — club president overview (C2).
 *
 * Presentation-only: mock data mirroring the future backend contract.
 * No database reads or writes. Member-base panels land in C15;
 * leadership handover lands in C6.
 *
 * Routes:
 *   president -> index()  (president only)
 */
class President extends Controller {

    private function requirePresident() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        $allowedRoles = ['ClubPresident', 'president'];
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
            'userRole'                => $_SESSION['user_role'] ?? 'ClubPresident',
            'userName'                => $memberName,
            'userEmail'               => $_SESSION['user_email'] ?? '',
            'userInitials'            => $_SESSION['user_initials'] ?? '',
            'unreadNotificationCount' => 2,
        ];
    }

    /**
     * President overview: health score + 40/30/30 breakdown, pending club
     * events, exec-roster summary.
     */
    public function index() {
        $this->requirePresident();

        $health = [
            'score'  => 78,
            'label'  => 'Green',
            'state'  => 'Active',
            'events' => ['points' => 32, 'max' => 40],
            'finances' => ['points' => 24, 'max' => 30],
            'attendance' => ['points' => 22, 'max' => 30],
        ];

        $pendingEvents = [
            ['id' => 1, 'title' => 'Gampaha Youth Leadership Workshop 2026', 'date' => 'Sep 15, 2026 · 9:00 AM', 'location' => 'Gampaha Town Hall', 'type' => 'Workshop', 'budget' => 'Rs. 45,000', 'submitted_by' => 'Amal Perera (Secretary)'],
        ];

        $pendingMembers = [
            ['name' => 'Sanduni Wickrama', 'email' => 'sanduni@example.test', 'phone' => '+94 78 112 3344', 'address' => '12 Lake Road, Gampaha', 'nic' => '200512345678', 'joined' => 'Jan 2025', 'registered_by' => 'Amal Perera (Secretary)'],
        ];

        $execRoster = [
            ['name' => 'Nuwan Bandara',  'role' => 'President', 'status' => 'Active', 'status_key' => 'active'],
            ['name' => 'Amal Perera',    'role' => 'Secretary', 'status' => 'Active', 'status_key' => 'active'],
            ['name' => 'Kasun Fernando', 'role' => 'Treasurer', 'status' => 'Active', 'status_key' => 'active'],
        ];

        $announcements = [
            [
                'title'   => 'Divisional Leadership Summit 2025',
                'summary' => 'Confirm your attendance for the upcoming leadership summit by this Friday.',
                'scope'   => 'Divisional',
                'age'     => '2 days ago',
                'is_new'  => true,
            ],
            [
                'title'   => 'New Volunteer Hour Submission Policy',
                'summary' => 'Volunteer hours should be submitted within seven days of the activity.',
                'scope'   => 'National',
                'age'     => '1 week ago',
                'is_new'  => false,
            ],
        ];

        $upcomingEvents = [
            [
                'title'    => 'Gampaha Youth Leadership Workshop 2026',
                'date'     => 'Sep 15, 2026',
                'location' => 'Gampaha Town Hall',
                'scope'    => 'Divisional',
                'status'   => 'Attending',
                'status_key'=> 'attending',
            ],
            [
                'title'    => 'Club Planning Session',
                'date'     => 'Sep 28, 2026',
                'location' => 'Club Centre',
                'scope'    => 'Club',
                'status'   => 'Attending',
                'status_key'=> 'attending',
            ],
        ];

        $socialCv = [
            'volunteer_hours' => 136,
            'events_count'    => 18,
            'leadership'      => 'Club President',
        ];

        $data = $this->shell(
            'President Overview — YouthNexus Pulse',
            'President Overview',
            'Health, pending approvals and executives of Gampaha Youth Development Club.',
            'president'
        );
        $data['health'] = $health;
        $data['pendingEvents'] = $pendingEvents;
        $data['pendingMembers'] = $pendingMembers;
        $data['execRoster'] = $execRoster;
        $data['announcements'] = $announcements;
        $data['upcomingEvents'] = $upcomingEvents;
        $data['socialCv'] = $socialCv;

        $this->view('president/index', $data);
    }

    /**
     * Leadership handover (C6): successor member-ID verify (invalid →
     * error + abort) + asset-freeze inventory checklist + president
     * confirm → atomic demote/promote + handover log + member notify.
     * Presentation-only: no DB writes.
     */
    public function handover() {
        $this->requirePresident();

        $members = [
            ['id' => 'M-001', 'name' => 'Nuwan Bandara',    'role' => 'President', 'status' => 'Active',  'current' => true],
            ['id' => 'M-002', 'name' => 'Amal Perera',      'role' => 'Secretary', 'status' => 'Active',  'current' => false],
            ['id' => 'M-003', 'name' => 'Kasun Fernando',   'role' => 'Treasurer', 'status' => 'Active',  'current' => false],
            ['id' => 'M-004', 'name' => 'Dilini Jayasuriya','role' => 'Member',    'status' => 'Active',  'current' => false],
            ['id' => 'M-005', 'name' => 'Ruwan Silva',      'role' => 'Member',    'status' => 'Active',  'current' => false],
            ['id' => 'M-006', 'name' => 'Sanduni Wickrama', 'role' => 'Member',    'status' => 'Pending', 'current' => false],
        ];

        $freezeAssets = [
            ['serial' => 'AST-2024-001', 'name' => 'Sound System (Portable PA)', 'custodian' => 'Club Centre'],
            ['serial' => 'AST-2024-002', 'name' => 'Multimedia Projector',       'custodian' => 'Club Centre'],
            ['serial' => 'AST-2025-003', 'name' => 'Cricket Gear Set',           'custodian' => 'Ruwan Silva'],
            ['serial' => 'AST-2025-004', 'name' => 'First-Aid Kit',              'custodian' => 'Club Centre'],
        ];

        $data = $this->shell(
            'Leadership Handover — YouthNexus Pulse',
            'Leadership Handover',
            'Transfer the presidency of Gampaha Youth Development Club.',
            'president/handover'
        );
        $data['members'] = $members;
        $data['freezeAssets'] = $freezeAssets;

        $this->view('president/handover', $data);
    }
}
