<?php

/**
 * Secretary — club secretary overview (C10).
 *
 * Presentation-only: mock data mirroring the future backend contract.
 * No database reads or writes. Full member-panel embedding across all
 * exec overviews lands in C15.
 *
 * Routes:
 *   secretary -> index()  (secretary only)
 */
class Secretary extends Controller {

    private function requireSecretary() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        $allowedRoles = ['ClubSecretary', 'secretary'];
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
            'userRole'                => $_SESSION['user_role'] ?? 'ClubSecretary',
            'userName'                => $memberName,
            'userEmail'               => $_SESSION['user_email'] ?? '',
            'userInitials'            => $_SESSION['user_initials'] ?? '',
            'unreadNotificationCount' => 2,
        ];
    }

    /**
     * Secretary overview: member/event/attendance/announcement shortcuts,
     * plus the member-base panels (announcements preview, upcoming events,
     * Social CV summary) per the subclass rule.
     */
    public function index() {
        $this->requireSecretary();

        $shortcuts = [
            ['title' => 'Register Member', 'desc' => 'Add a member — president approves', 'href' => 'club/members', 'icon' => 'user'],
            ['title' => 'Create Event', 'desc' => 'Submit an event for approval', 'href' => 'club/events', 'icon' => 'calendar'],
            ['title' => 'Mark Attendance', 'desc' => 'Single entry or bulk CSV', 'href' => 'club/attendance', 'icon' => 'check'],
            ['title' => 'Publish Announcement', 'desc' => 'Normal or Urgent club update', 'href' => 'announcements', 'icon' => 'bell'],
        ];

        $queue = [
            'pending_members' => 1,
            'pending_events'  => 1,
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
            'volunteer_hours' => 112,
            'events_count'    => 15,
            'leadership'      => 'Club Secretary',
        ];

        $data = $this->shell(
            'Secretary Overview — YouthNexus Pulse',
            'Secretary Overview',
            'Queues, shortcuts and personal summary of Amal Perera.',
            'secretary'
        );
        $data['shortcuts'] = $shortcuts;
        $data['queue'] = $queue;
        $data['announcements'] = $announcements;
        $data['upcomingEvents'] = $upcomingEvents;
        $data['socialCv'] = $socialCv;

        $this->view('secretary/index', $data);
    }
}
