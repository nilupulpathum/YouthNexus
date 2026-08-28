<?php

class Help extends Controller {

    public function index() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }

        $faqs = [
            [
                'question' => 'How do I submit my volunteer hours?',
                'answer' => 'Navigate to the "Volunteer Hours" section, fill in the activity details, upload evidence, and click "Send for Verification".'
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

        $data = [
            'title' => 'Help — YouthNexus Pulse',
            'pageTitle' => 'Help Center',
            'pageDescription' => 'Get support and find answers to common questions.',
            'currentRoute' => 'help',
            'faqs' => $faqs
        ];

        $this->view('help/index', $data);
    }
}
