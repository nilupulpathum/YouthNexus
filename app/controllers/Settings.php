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
            'userName' => trim((string) ($_SESSION['user_name'] ?? '')) !== '' ? trim((string) $_SESSION['user_name']) : 'YouthNexus User',
            'userEmail' => $_SESSION['user_email'] ?? '',
            'userInitials' => $_SESSION['user_initials'] ?? 'YN',
            'unreadNotificationCount' => 2,
            'user' => $this->profileFormValues(),
        ];

        $this->view('settings/index', $data);
    }

    /**
     * Edit Profile form values from the viewer's own live User row, never
     * another person's. Saving stays presentation-only (see settings.js).
     */
    private function profileFormValues(): array {
        $row = $this->model('UserModel')->findByUserId((int) ($_SESSION['user_id'] ?? 0));
        $sessionName = trim((string) ($_SESSION['user_name'] ?? ''));
        $nameParts = preg_split('/\s+/u', $sessionName !== '' ? $sessionName : 'YouthNexus User', 2);
        return [
            'first_name' => $row->first_name ?? ($nameParts[0] ?? ''),
            'last_name' => $row->last_name ?? ($nameParts[1] ?? ''),
            'email' => $row->email ?? ($_SESSION['user_email'] ?? ''),
            'phone' => $row->phone_number ?? '',
            'address' => $row->address ?? '',
            'notifications' => [
                'email_enabled' => true,
                'announcements_email' => true
            ],
        ];
    }
}
