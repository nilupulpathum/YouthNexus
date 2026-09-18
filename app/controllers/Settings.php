<?php

class Settings extends Controller {

    public function index() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }

        $data = [
            'title' => 'Settings — YouthNexus Pulse',
            'pageTitle' => 'Settings',
            'pageDescription' => 'Manage your profile, security, and notification preferences.',
            'currentRoute' => 'settings',
            'userRole' => $_SESSION['user_role'] ?? 'ClubMember',
            'userName' => trim((string) ($_SESSION['user_name'] ?? '')) ?: 'Nuwan Bandara',
            'userEmail' => $_SESSION['user_email'] ?? 'nuwan@example.test',
            'userInitials' => $_SESSION['user_initials'] ?? 'NB',
            'unreadNotificationCount' => 2,
            'user' => [
                'first_name' => 'Nuwan',
                'last_name' => 'Bandara',
                'email' => 'nuwan@example.test',
                'phone' => '+94 77 556 6778',
                'address' => '123 Youth Centre Road, Gampaha',
                'notifications' => [
                    'email_enabled' => true,
                    'announcements_email' => true
                ]
            ]
        ];

        $this->view('settings/index', $data);
    }
}
