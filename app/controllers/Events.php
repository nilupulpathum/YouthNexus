<?php

class Events extends Controller {

    public function index() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }

        $events = [
            [
                'id' => 1,
                'title' => 'Divisional Skills & Leadership Workshop',
                'description' => 'Hands-on workshop series covering public speaking, team management, and project planning for divisional members.',
                'scope' => 'Divisional',
                'date' => 'Jun 12, 2025',
                'location' => 'Quezon City',
                'status' => 'Pending',
                'remaining' => '2 DAYS LEFT',
                'rsvp_status' => null
            ],
            [
                'id' => 2,
                'title' => 'National Youth Conference 2025',
                'description' => 'The largest gathering of youth leaders in the country, focusing on innovation and social impact.',
                'scope' => 'National',
                'date' => 'Jun 15, 2025',
                'location' => 'Manila',
                'status' => 'Upcoming',
                'remaining' => '5 DAYS LEFT',
                'rsvp_status' => 'attending'
            ],
            [
                'id' => 3,
                'title' => 'Club Monthly Planning Session',
                'description' => 'Monthly in-club planning meeting to review projects and set targets for next month.',
                'scope' => 'Club-specific',
                'date' => 'Jun 8, 2025',
                'location' => 'Local Hub',
                'status' => 'Started',
                'remaining' => 'STARTED',
                'rsvp_status' => 'attending'
            ]
        ];

        // Duplicate for demo to show "Showing 6 events"
        $events = array_merge($events, $events);

        $data = [
            'title' => 'Events — YouthNexus Pulse',
            'pageTitle' => 'Events',
            'pageDescription' => 'Browse, respond, and track all your events.',
            'currentRoute' => 'events',
            'events' => $events
        ];

        $this->view('events/index', $data);
    }
}
