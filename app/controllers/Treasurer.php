<?php

/**
 * Treasurer — club treasurer overview (C9).
 *
 * Presentation-only: mock data mirroring the future backend contract.
 * No database reads or writes. Full member-panel embedding across all
 * exec overviews lands in C15.
 *
 * Routes:
 *   treasurer -> index()  (treasurer only)
 */
class Treasurer extends Controller {

    private function requireTreasurer() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        $allowedRoles = ['ClubTreasurer', 'treasurer'];
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
            'userRole'                => $_SESSION['user_role'] ?? 'ClubTreasurer',
            'userName'                => $memberName,
            'userEmail'               => $_SESSION['user_email'] ?? '',
            'userInitials'            => $_SESSION['user_initials'] ?? '',
            'unreadNotificationCount' => 2,
        ];
    }

    /**
     * Treasurer overview: fund balance summary + ledger/asset shortcuts,
     * plus the member-base panels (announcements preview, upcoming events,
     * Social CV summary) per the subclass rule.
     */
    public function index() {
        $this->requireTreasurer();

        $funds = [
            'balance'  => 'Rs. 132,400',
            'income'   => 'Rs. 74,500',
            'expenses' => 'Rs. 25,500',
            'pending_voids' => 1,
        ];

        $shortcuts = [
            ['title' => 'Log Transaction', 'desc' => 'Income or expense with mandatory receipt', 'href' => 'club/ledger', 'icon' => 'file'],
            ['title' => 'Transfer Custody', 'desc' => 'Hand an Available asset to a custodian', 'href' => 'club/assets', 'icon' => 'user'],
            ['title' => 'Review Pending Voids', 'desc' => '1 void request awaiting the Divisional Treasurer', 'href' => 'club/ledger', 'icon' => 'eye'],
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
            'volunteer_hours' => 96,
            'events_count'    => 12,
            'leadership'      => 'Club Treasurer',
        ];

        $data = $this->shell(
            'Treasurer Overview — YouthNexus Pulse',
            'Treasurer Overview',
            'Funds, shortcuts and personal summary of Kasun Fernando.',
            'treasurer'
        );
        $data['funds'] = $funds;
        $data['shortcuts'] = $shortcuts;
        $data['announcements'] = $announcements;
        $data['upcomingEvents'] = $upcomingEvents;
        $data['socialCv'] = $socialCv;

        $this->view('treasurer/index', $data);
    }
}
