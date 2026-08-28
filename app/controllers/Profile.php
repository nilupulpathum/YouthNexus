<?php

class Profile extends Controller {

    public function index() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }

        $data = [
            'title' => 'Social CV — YouthNexus Pulse',
            'pageTitle' => 'Social CV',
            'pageDescription' => 'Your verified youth development profile.',
            'currentRoute' => 'profile',
            'profile' => [
                'name' => 'Jamie Dela Cruz',
                'nic' => '199512345678',
                'member_id' => 'MEM-2022-0941',
                'location' => 'Metro Youth Club · Manila Division · Metro District',
                'member_since' => 'Jan 2022',
                'bio' => 'Passionate youth leader and community builder from Manila. Dedicated to empowering my peers through structured programs, environmental projects, and leadership development. Proud to have served as club president and contributed to national-level initiatives.'
            ],
            'stats' => [
                ['label' => 'Volunteer Hours', 'value' => '136h', 'icon' => 'hours'],
                ['label' => 'Events Attended', 'value' => '24', 'icon' => 'calendar'],
                ['label' => 'Years Active', 'value' => '3 yrs', 'icon' => 'time'],
                ['label' => 'Club Health Contribution', 'value' => '87%', 'icon' => 'heart'],
            ],
            'skills' => [
                ['name' => 'COMMUNITY SERVICE', 'level' => 'Gold', 'events' => 18, 'icon' => 'community'],
                ['name' => 'ENVIRONMENTAL WORK', 'level' => 'Silver', 'events' => 9, 'icon' => 'env'],
                ['name' => 'LEADERSHIP & MGMT', 'level' => 'Gold', 'events' => 14, 'icon' => 'leadership'],
                ['name' => 'EVENT COORDINATION', 'level' => 'Silver', 'events' => 7, 'icon' => 'event'],
                ['name' => 'FIRST AID & HEALTH', 'level' => 'Bronze', 'events' => 3, 'icon' => 'health'],
                ['name' => 'ARTS & CULTURE', 'level' => 'Bronze', 'events' => 2, 'icon' => 'arts'],
                ['name' => 'SPORTS & RECREATION', 'level' => 'Bronze', 'events' => 5, 'icon' => 'sports'],
                ['name' => 'FINANCIAL MANAGEMENT', 'level' => 'Bronze', 'events' => 2, 'icon' => 'finance'],
            ],
            'positions' => [
                [
                    'role' => 'President',
                    'date' => 'JAN 2024 — PRESENT',
                    'club' => 'Metro Youth Club — Manila',
                    'description' => 'Led 200+ members, organized 12 events, managed budget and reported to divisional coordinator.'
                ],
                [
                    'role' => 'Secretary',
                    'date' => 'JAN 2023 — DEC 2023',
                    'club' => 'Metro Youth Club — Manila',
                    'description' => 'Managed correspondence, meeting minutes, and member registration processes.'
                ]
            ],
            'timeline' => [
                ['title' => 'National Youth Conference 2025', 'date' => 'Jun 15, 2025', 'location' => 'Manila', 'role' => 'Organizer', 'hours' => '16h', 'scope' => 'National'],
                ['title' => 'Zonal Sports & Wellness Festival', 'date' => 'May 10, 2025', 'location' => 'Rizal Park', 'role' => 'Volunteer', 'hours' => '8h', 'scope' => 'Zonal'],
                ['title' => 'Divisional Leadership Workshop', 'date' => 'Apr 3, 2025', 'location' => 'Quezon City', 'role' => 'Participant', 'hours' => '6h', 'scope' => 'Divisional'],
            ],
            'endorsements' => [
                [
                    'name' => 'Atty. Renaldo Cruz',
                    'role' => 'DIVISIONAL COORDINATOR, MANILA DIVISION',
                    'text' => 'Jamie has consistently demonstrated exceptional leadership, dedication, and the ability to inspire peers. A highly reliable and impactful member.',
                    'date' => 'APR 2025'
                ]
            ]
        ];

        $this->view('profile/index', $data);
    }
}
