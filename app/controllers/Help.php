<?php

class Help extends Controller {

    public function index() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }

        $faqs = [
            [
                'question' => 'Where can I see my volunteer hours?',
                'answer' => 'Your verified volunteer hours are shown on your Social CV, alongside your events, skills, and certificates.'
            ],
            [
                'question' => 'What is the Social CV?',
                'answer' => 'The Social CV is your verified youth development profile that showcases your skills, events attended, and leadership positions.'
            ],
            [
                'question' => 'How can I join an event?',
                'answer' => 'Go to the "Events" page, find an event you are interested in, and click "Participate".'
            ]
        ];

        $headerNotif = ZoneOverview::headerNotifications($this);

        $data = [
            'title' => 'Help — YouthNexus Pulse',
            'pageTitle' => 'Help Center',
            'pageDescription' => 'Get support and find answers to common questions.',
            'currentRoute' => 'help',
            'userRole' => $_SESSION['user_role'] ?? 'ClubMember',
            'userName' => trim((string) ($_SESSION['user_name'] ?? '')) ?: 'YouthNexus User',
            'userEmail' => $_SESSION['user_email'] ?? '',
            'userInitials' => $_SESSION['user_initials'] ?? '',
            'unreadNotificationCount' => $headerNotif['count'],
            'headerNotifications' => $headerNotif['items'],
            'faqs' => $faqs
        ];

        $this->view('help/index', $data);
    }
}
