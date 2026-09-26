<?php

class Events extends Controller {

    public function index() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }

        $events = [
            [
                'id' => 1,
                'title' => 'Gampaha Youth Leadership Workshop 2026',
                'description' => 'Hands-on workshop series covering public speaking, team management, and project planning for divisional members.',
                'scope' => 'Divisional',
                'date' => 'Sep 15, 2026',
                'location' => 'Gampaha Town Hall',
                'status' => 'Pending',
                'remaining' => '2 DAYS LEFT',
                'rsvp_status' => null
            ],
            [
                'id' => 2,
                'title' => 'National Youth Conference 2026',
                'description' => 'The largest gathering of youth leaders in the country, focusing on innovation and social impact.',
                'scope' => 'National',
                'date' => 'Sep 22, 2026',
                'location' => 'BMICH, Colombo',
                'status' => 'Upcoming',
                'remaining' => '5 DAYS LEFT',
                'rsvp_status' => 'attending'
            ],
            [
                'id' => 3,
                'title' => 'Club Monthly Planning Session',
                'description' => 'Monthly in-club planning meeting to review projects and set targets for next month.',
                'scope' => 'Club-specific',
                'date' => 'Sep 8, 2026',
                'location' => 'Club Centre, Gampaha',
                'status' => 'Started',
                'remaining' => 'STARTED',
                'rsvp_status' => 'attending'
            ],
            [
                'id' => 4,
                'title' => 'Community Green Environment Cleanup',
                'description' => 'Voluntary environmental cleanup along the canal and central park in Gampaha.',
                'scope' => 'Club-specific',
                'date' => 'Sep 28, 2026',
                'location' => 'Gampaha Central Park',
                'status' => 'Upcoming',
                'remaining' => '11 DAYS LEFT',
                'rsvp_status' => null
            ],
            [
                'id' => 5,
                'title' => 'Zonal Sports & Wellness Festival',
                'description' => 'Inter-club sports meet and wellness programme for the zone.',
                'scope' => 'Zonal',
                'date' => 'Oct 4, 2026',
                'location' => 'Gampaha District Stadium',
                'status' => 'Upcoming',
                'remaining' => '17 DAYS LEFT',
                'rsvp_status' => null
            ],
            [
                'id' => 6,
                'title' => 'Divisional Skills Development Seminar',
                'description' => 'Day-long seminar on employability skills and career guidance for members.',
                'scope' => 'Divisional',
                'date' => 'Oct 11, 2026',
                'location' => 'Divisional Secretariat, Gampaha',
                'status' => 'Upcoming',
                'remaining' => '24 DAYS LEFT',
                'rsvp_status' => null
            ]
        ];

        $memberName = trim((string) ($_SESSION['user_name'] ?? '')) ?: 'YouthNexus User';

        $headerNotif = ZoneOverview::headerNotifications($this);

        $data = [
            'title' => 'Events — YouthNexus Pulse',
            'pageTitle' => 'Events',
            'pageDescription' => 'Browse, respond, and track all your events.',
            'currentRoute' => 'events',
            'userRole' => $_SESSION['user_role'] ?? 'ClubMember',
            'userName' => $memberName,
            'userEmail' => $_SESSION['user_email'] ?? '',
            'userInitials' => $_SESSION['user_initials'] ?? '',
            'unreadNotificationCount' => $headerNotif['count'],
            'headerNotifications' => $headerNotif['items'],
            'events' => $events
        ];

        $this->view('events/index', $data);
    }
}
