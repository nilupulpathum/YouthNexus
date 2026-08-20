<?php

class Member extends Controller {

    /**
     * Restrict this dashboard to authenticated Club Member accounts.
     * The Member alias is retained for local/demo compatibility with the
     * existing shared sidebar role vocabulary.
     */
    private function requireMember() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }

        $allowedRoles = ['ClubMember', 'Member'];
        if (!in_array($_SESSION['user_role'] ?? '', $allowedRoles, true)) {
            $this->redirect('home');
        }
    }

    /**
     * Render the Club Member dashboard with presentation-only data.
     *
     * This task intentionally does not query the database or perform writes.
     * The array keys mirror the future backend response contract so the view
     * can later be connected to real aggregates without a markup rewrite.
     */
    public function index() {
        $this->requireMember();

        $memberName = trim((string) ($_SESSION['user_name'] ?? 'Jamie Dela Cruz')) ?: 'Jamie Dela Cruz';
        $memberInitials = strtoupper(
            substr($memberName, 0, 1) . substr(strrchr(' ' . $memberName, ' '), 1, 1)
        );

        $memberDashboard = [
            'member' => [
                'name'      => $memberName,
                'role'      => 'Member',
                'club_name' => $_SESSION['club_name'] ?? 'Kaduwela Youth Club',
                'initials'   => $memberInitials ?: 'JD',
            ],
            'tiles' => [
                'volunteer_hours'      => 136,
                'upcoming_events'      => 5,
                'unread_announcements'=> 3,
                'latest_certificate'  => 'Youth Leadership — Verified',
            ],
            'announcements' => [
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
            ],
            'upcoming_events_list' => [
                [
                    'title'    => 'National Youth Conference',
                    'date'     => 'Jun 15, 2025',
                    'location' => 'Manila',
                    'scope'    => 'National',
                    'status'   => 'Attending',
                    'status_key'=> 'attending',
                ],
                [
                    'title'    => 'Divisional Skills Workshop',
                    'date'     => 'Jun 22, 2025',
                    'location' => 'Quezon City',
                    'scope'    => 'Divisional',
                    'status'   => 'Pending',
                    'status_key'=> 'pending',
                ],
                [
                    'title'    => 'Club Planning Session',
                    'date'     => 'Jun 28, 2025',
                    'location' => 'Club HQ',
                    'scope'    => 'Club',
                    'status'   => 'Attending',
                    'status_key'=> 'attending',
                ],
            ],
            'recent_activity' => [
                ['label' => 'Marked attendance at Zonal Youth Summit', 'meta' => 'Yesterday, 3:45 PM', 'icon' => 'check'],
                ['label' => 'Volunteer hours submitted for Community Clean-up Drive', 'meta' => 'Jun 9, 10:12 AM', 'icon' => 'hours'],
                ['label' => 'RSVP’d to National Youth Conference', 'meta' => 'Jun 8, 2:00 PM', 'icon' => 'event'],
                ['label' => 'Read announcement: New Volunteer Hour Policy', 'meta' => 'Jun 7, 9:20 AM', 'icon' => 'read'],
            ],
        ];

        $this->view('member/index', [
            'title'                   => 'Member Dashboard — YouthNexus Pulse',
            'pageTitle'               => 'Welcome back, ' . $memberName,
            'pageDescription'         => "Here’s what’s happening with your activities at {$memberDashboard['member']['club_name']}.",
            'currentRoute'            => 'member',
            'userRole'                => $_SESSION['user_role'] ?? 'ClubMember',
            'userName'                => $memberName,
            'userEmail'               => $_SESSION['user_email'] ?? '',
            'userInitials'            => $_SESSION['user_initials'] ?? $memberInitials,
            'unreadNotificationCount' => $memberDashboard['tiles']['unread_announcements'],
            'memberDashboard'         => $memberDashboard,
        ]);
    }
}
