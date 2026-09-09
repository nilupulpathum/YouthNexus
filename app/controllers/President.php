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

        $execRoster = [
            ['name' => 'Nuwan Bandara',  'role' => 'President', 'status' => 'Active', 'status_key' => 'active'],
            ['name' => 'Amal Perera',    'role' => 'Secretary', 'status' => 'Active', 'status_key' => 'active'],
            ['name' => 'Kasun Fernando', 'role' => 'Treasurer', 'status' => 'Active', 'status_key' => 'active'],
        ];

        $data = $this->shell(
            'President Overview — YouthNexus Pulse',
            'President Overview',
            'Health, pending approvals and executives of Gampaha Youth Development Club.',
            'president'
        );
        $data['health'] = $health;
        $data['pendingEvents'] = $pendingEvents;
        $data['execRoster'] = $execRoster;

        $this->view('president/index', $data);
    }
}
