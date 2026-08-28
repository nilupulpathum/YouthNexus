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
            'user' => [
                'first_name' => 'Jamie',
                'last_name' => 'Dela Cruz',
                'email' => 'jamie@example.test',
                'phone' => '+63 912 345 6789',
                'address' => '123 Youth Hub, Metro Manila',
                'notifications' => [
                    'email_enabled' => true,
                    'announcements_email' => true
                ]
            ]
        ];

        $this->view('settings/index', $data);
    }
}
