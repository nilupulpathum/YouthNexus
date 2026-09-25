<?php

class Zonalannouncements extends Controller {

    private function requireZonalRole() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        $roles = ['ZonalCoordinator', 'ZonalSecretary', 'ZonalTreasurer', 'zonalcoordinator', 'zonalsecretary', 'zonaltreasurer'];
        if (!in_array($_SESSION['user_role'] ?? '', $roles, true)) {
            $this->redirect('home');
        }
        if ((int) ($_SESSION['zonal_id'] ?? 0) < 1) {
            http_response_code(403);
            exit('Your user account is not assigned to a zone.');
        }
    }

    public function index() {
        $this->requireZonalRole();

        $role = $_SESSION['user_role'] ?? '';
        $memberName = trim((string) ($_SESSION['user_name'] ?? '')) ?: 'YouthNexus User';
        $announcements = [
            ['id' => 1, 'title' => 'Zone Youth Leadership Forum registration', 'summary' => 'Gampaha Zone will hold its Youth Leadership Forum on October 10. Division secretaries should share the programme with their clubs and submit delegate lists before the closing date.', 'scope' => 'Zonal', 'date' => 'Sep 20, 2026', 'age' => 'Today', 'is_new' => true, 'is_unread' => true, 'attachment' => 'Leadership_Forum_Programme.pdf', 'attachment_size' => '245 KB'],
            ['id' => 2, 'title' => 'Digital Skills Workshop participation update', 'summary' => 'The Digital Skills Workshop is open to clubs in Ja-Ela Division. Club secretaries can coordinate participants through their divisional office.', 'scope' => 'Zonal', 'date' => 'Sep 18, 2026', 'age' => '2 days ago', 'is_new' => true, 'is_unread' => true, 'attachment' => '', 'attachment_size' => ''],
            ['id' => 3, 'title' => 'National volunteer-hour submission guidance', 'summary' => 'National guidance for volunteer-hour submissions remains available for all clubs in Gampaha Zone. Submit records through the established divisional process.', 'scope' => 'National', 'date' => 'Sep 8, 2026', 'age' => '12 days ago', 'is_new' => false, 'is_unread' => false, 'attachment' => 'Volunteer_Policy_v2.pdf', 'attachment_size' => '1.2 MB'],
        ];

        $data = [
            'title' => 'Zone Announcements — YouthNexus Pulse',
            'pageTitle' => 'Zone Announcements',
            'pageDescription' => 'Announcements for Gampaha Zone, its divisions, and their clubs.',
            'currentRoute' => 'zonalannouncements',
            'userRole' => $role,
            'userName' => $memberName,
            'userEmail' => $_SESSION['user_email'] ?? '',
            'userInitials' => $_SESSION['user_initials'] ?? '',
            'unreadNotificationCount' => 2,
            'unreadCount' => 2,
            'announcements' => $announcements,
            'can_publish' => in_array($role, ['ZonalSecretary', 'zonalsecretary'], true),
        ];

        $this->view('zonalannouncements/index', $data);
    }
}
