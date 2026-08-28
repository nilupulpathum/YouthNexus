<?php

class Volunteer extends Controller {

    public function index() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }

        $history = [
            [
                'id' => 1,
                'event' => 'Community Clean-up Drive',
                'date' => 'Jun 9, 2025',
                'hours' => 5,
                'status' => 'Verified',
                'status_key' => 'verified'
            ],
            [
                'id' => 2,
                'event' => 'Divisional Leadership Summit (Organizer)',
                'date' => 'Jun 10, 2025',
                'hours' => 8,
                'status' => 'Pending',
                'status_key' => 'pending'
            ],
            [
                'id' => 3,
                'event' => 'Zonal Sports Festival Assistance',
                'date' => 'May 25, 2025',
                'hours' => 4,
                'status' => 'Rejected',
                'status_key' => 'rejected',
                'reason' => 'Insufficient evidence provided.'
            ]
        ];

        $data = [
            'title' => 'Volunteer Hours — YouthNexus Pulse',
            'pageTitle' => 'Volunteer Hours',
            'pageDescription' => 'Track and submit your volunteer contributions.',
            'currentRoute' => 'volunteer-hours',
            'stats' => [
                'total' => 136,
                'approved' => 128,
                'pending' => 8,
                'rejected' => 4
            ],
            'history' => $history
        ];

        $this->view('volunteer/index', $data);
    }
}
