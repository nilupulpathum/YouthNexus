<?php

class Announcements extends Controller {

    public function index() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }

        $announcements = [
            [
                'id' => 1,
                'title' => 'Divisional Leadership Summit 2026 - Confirmation Required',
                'summary' => 'All division members are invited to the upcoming Leadership Summit on Sep 20. Please confirm your attendance and transport will be arranged for outstation members. Kindly submit dietary requirements if applicable.',
                'scope' => 'Divisional',
                'date' => 'Sep 10, 2026',
                'age' => '2 days ago',
                'is_new' => true,
                'is_unread' => true,
                'attachment' => 'Summit_Agenda_2026.pdf',
                'attachment_size' => '245 KB'
            ],
            [
                'id' => 2,
                'title' => 'National Policy Update: Volunteer Hour Submission Guidelines',
                'summary' => 'Effective Oct 1, 2026, all volunteer hours must be submitted within 7 days of the activity. The summit will focus on strategic planning for the second half of the year, leadership workshops, and networking sessions with national representatives.',
                'scope' => 'National',
                'date' => 'Sep 8, 2026',
                'age' => '4 days ago',
                'is_new' => true,
                'is_unread' => true,
                'attachment' => 'Volunteer_Policy_v2.pdf',
                'attachment_size' => '1.2 MB'
            ],
            [
                'id' => 3,
                'title' => 'Club Monthly Newsletter - August Edition',
                'summary' => 'The August edition of the YouthNexus Club Newsletter is now published. It covers event highlights, member spotlights, and upcoming activities for September. Download and share...',
                'scope' => 'Club-specific',
                'date' => 'Sep 1, 2026',
                'age' => 'Sep 1',
                'is_new' => false,
                'is_unread' => false,
                'attachment' => 'Newsletter_Aug2026.pdf',
                'attachment_size' => '3.5 MB'
            ]
        ];

        $memberName = trim((string) ($_SESSION['user_name'] ?? '')) ?: 'YouthNexus User';

        $data = [
            'title' => 'Announcements — YouthNexus Pulse',
            'pageTitle' => 'Announcements',
            'pageDescription' => 'Stay updated with all club, divisional, zonal, and national communications.',
            'currentRoute' => 'announcements',
            'userRole' => $_SESSION['user_role'] ?? 'ClubMember',
            'userName' => $memberName,
            'userEmail' => $_SESSION['user_email'] ?? '',
            'userInitials' => $_SESSION['user_initials'] ?? '',
            'unreadNotificationCount' => 2,
            'unreadCount' => 2,
            'announcements' => $announcements
        ];

        $this->view('announcements/index', $data);
    }
}
