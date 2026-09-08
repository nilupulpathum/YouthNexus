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

        $memberName = trim((string) ($_SESSION['user_name'] ?? 'Nuwan Bandara')) ?: 'Nuwan Bandara';
        $memberInitials = strtoupper(
            substr($memberName, 0, 1) . substr(strrchr(' ' . $memberName, ' '), 1, 1)
        );

        $memberDashboard = [
            'member' => [
                'name'      => $memberName,
                'role'      => 'Member',
                'club_name' => $_SESSION['club_name'] ?? 'Gampaha Youth Development Club',
                'initials'   => $memberInitials ?: 'JD',
            ],
            'tiles' => [
                'volunteer_hours'      => 136,
                'upcoming_events'      => 5,
                'unread_announcements'=> 2,
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
                    'title'    => 'Gampaha Youth Leadership Workshop 2026',
                    'date'     => 'Sep 15, 2026',
                    'location' => 'Gampaha Town Hall',
                    'scope'    => 'Divisional',
                    'status'   => 'Attending',
                    'status_key'=> 'attending',
                ],
                [
                    'title'    => 'Divisional Skills Development Seminar',
                    'date'     => 'Sep 22, 2026',
                    'location' => 'Gampaha',
                    'scope'    => 'Divisional',
                    'status'   => 'Pending',
                    'status_key'=> 'pending',
                ],
                [
                    'title'    => 'Club Planning Session',
                    'date'     => 'Sep 28, 2026',
                    'location' => 'Club Centre',
                    'scope'    => 'Club',
                    'status'   => 'Attending',
                    'status_key'=> 'attending',
                ],
            ],
            'recent_activity' => [
                ['label' => 'Marked attendance at Divisional Youth Summit', 'meta' => 'Yesterday, 3:45 PM', 'icon' => 'check'],
                ['label' => 'Volunteer hours submitted for Community Clean-up Drive', 'meta' => 'Sep 9, 10:12 AM', 'icon' => 'hours'],
                ['label' => 'RSVP’d to Gampaha Youth Leadership Workshop', 'meta' => 'Sep 8, 2:00 PM', 'icon' => 'event'],
                ['label' => 'Read announcement: New Volunteer Hour Policy', 'meta' => 'Sep 7, 9:20 AM', 'icon' => 'read'],
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
            'unreadNotificationCount' => 2,
            'memberDashboard'         => $memberDashboard,
        ]);
    }
}
