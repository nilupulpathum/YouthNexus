<?php

class Profile extends Controller {

    public function index() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }

        $headerNotif = ZoneOverview::headerNotifications($this);

        $data = [
            'title' => 'Social CV — YouthNexus Pulse',
            'pageTitle' => 'Social CV',
            'pageDescription' => 'Your verified youth development profile.',
            'currentRoute' => 'profile',
            'userRole' => $_SESSION['user_role'] ?? 'ClubMember',
            'userName' => $_SESSION['user_name'] ?? 'Nuwan Bandara',
            'userEmail' => $_SESSION['user_email'] ?? '',
            'userInitials' => $_SESSION['user_initials'] ?? 'NB',
            'unreadNotificationCount' => $headerNotif['count'],
            'headerNotifications' => $headerNotif['items'],
            'profile' => [
                'name' => 'Nuwan Bandara',
                'nic' => '199912345678',
                'member_id' => 'MEM-2024-0142',
                'location' => 'Gampaha Youth Development Club · Gampaha Division · Western Zone',
                'member_since' => 'Jan 2024',
                'bio' => 'Youth club member from Gampaha serving as club president. Active in community service projects, environmental programmes, and divisional leadership activities.'
            ],
            'stats' => [
                ['label' => 'Volunteer Hours', 'value' => '136h', 'icon' => 'clock'],
                ['label' => 'Events Attended', 'value' => '24', 'icon' => 'calendar'],
                ['label' => 'Years Active', 'value' => '3 yrs', 'icon' => 'award'],
                ['label' => 'Club Health Contribution', 'value' => '87%', 'icon' => 'heart'],
            ],
            'skills' => [
                ['name' => 'COMMUNITY SERVICE', 'level' => 'Gold', 'events' => 18, 'icon' => 'user'],
                ['name' => 'ENVIRONMENTAL WORK', 'level' => 'Silver', 'events' => 9, 'icon' => 'leaf'],
                ['name' => 'LEADERSHIP & MGMT', 'level' => 'Gold', 'events' => 14, 'icon' => 'award'],
                ['name' => 'EVENT COORDINATION', 'level' => 'Silver', 'events' => 7, 'icon' => 'calendar'],
                ['name' => 'FIRST AID & HEALTH', 'level' => 'Bronze', 'events' => 3, 'icon' => 'heart'],
                ['name' => 'ARTS & CULTURE', 'level' => 'Bronze', 'events' => 2, 'icon' => 'palette'],
                ['name' => 'SPORTS & RECREATION', 'level' => 'Bronze', 'events' => 5, 'icon' => 'ball'],
                ['name' => 'FINANCIAL MANAGEMENT', 'level' => 'Bronze', 'events' => 2, 'icon' => 'file'],
            ],
            'positions' => [
                [
                    'role' => 'President',
                    'date' => 'JAN 2026 — PRESENT',
                    'club' => 'Gampaha Youth Development Club — Gampaha',
                    'description' => 'Lead club activities, chair meetings, and report to the divisional coordinator.'
                ],
                [
                    'role' => 'Secretary',
                    'date' => 'JAN 2025 — DEC 2025',
                    'club' => 'Gampaha Youth Development Club — Gampaha',
                    'description' => 'Managed correspondence, meeting minutes, and member registration processes.'
                ]
            ],
            'timeline' => [
                ['title' => 'Gampaha Youth Leadership Workshop 2026', 'date' => 'Sep 15, 2026', 'location' => 'Gampaha Town Hall', 'role' => 'Organizer', 'hours' => '16h', 'scope' => 'Divisional'],
                ['title' => 'Zonal Sports & Wellness Festival', 'date' => 'Aug 10, 2026', 'location' => 'Gampaha District Stadium', 'role' => 'Volunteer', 'hours' => '8h', 'scope' => 'Zonal'],
                ['title' => 'Divisional Skills Development Seminar', 'date' => 'Jul 3, 2026', 'location' => 'Gampaha', 'role' => 'Participant', 'hours' => '6h', 'scope' => 'Divisional'],
            ],
            'endorsements' => [
                [
                    'name' => 'Divisional Coordinator',
                    'role' => 'DIVISIONAL COORDINATOR, GAMPAHA DIVISION',
                    'text' => 'Nuwan has consistently demonstrated leadership, dedication, and the ability to inspire peers. A reliable and impactful member.',
                    'date' => 'AUG 2026'
                ]
            ]
        ];

        $this->view('profile/index', $data);
    }
}
