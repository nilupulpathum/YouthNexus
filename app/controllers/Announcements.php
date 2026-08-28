<?php

class Announcements extends Controller {

    public function index() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }

        $announcements = [
            [
                'id' => 1,
                'title' => 'Divisional Leadership Summit 2025 - Confirmation Required',
                'summary' => 'All division members are invited to the upcoming Leadership Summit on June 20. Please confirm your attendance and transport will be arranged for outstation members. Kindly submit dietary requirements if applicable.',
                'scope' => 'Divisional',
                'date' => 'Jun 10, 2025',
                'age' => '2 days ago',
                'is_new' => true,
                'is_unread' => true,
                'attachment' => 'Summit_Agenda_2025.pdf',
                'attachment_size' => '245 KB'
            ],
            [
                'id' => 2,
                'title' => 'National Policy Update: Volunteer Hour Submission Guidelines',
                'summary' => 'Effective July 1, 2025, all volunteer hours must be submitted within 7 days of the activity. The summit will focus on strategic planning for the second half of the year, leadership workshops, and networking sessions with national representatives.',
                'scope' => 'National',
                'date' => 'Jun 8, 2025',
                'age' => '4 days ago',
                'is_new' => true,
                'is_unread' => true,
                'attachment' => 'Volunteer_Policy_v2.pdf',
                'attachment_size' => '1.2 MB'
            ],
            [
                'id' => 3,
                'title' => 'Club Monthly Newsletter - May Edition',
                'summary' => 'The May edition of the YouthNexus Club Newsletter is now published. It covers event highlights, member spotlights, and upcoming activities for June. Download and share...',
                'scope' => 'Club-specific',
                'date' => 'Jun 1, 2025',
                'age' => 'Jun 1',
                'is_new' => false,
                'is_unread' => false,
                'attachment' => 'Newsletter_May2025.pdf',
                'attachment_size' => '3.5 MB'
            ]
        ];

        $data = [
            'title' => 'Announcements — YouthNexus Pulse',
            'pageTitle' => 'Announcements',
            'pageDescription' => 'Stay updated with all club, divisional, zonal, and national communications.',
            'currentRoute' => 'announcements',
            'unreadCount' => 3,
            'announcements' => $announcements
        ];

        $this->view('announcements/index', $data);
    }
}
