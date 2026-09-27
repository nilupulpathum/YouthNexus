<?php


class Home extends Controller {

    public function index($a='', $b='', $c='') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $data = [
            'isLoggedIn' => isset($_SESSION['user_id']),
            'userName'   => $_SESSION['user_name'] ?? '',
            'userRole'   => $_SESSION['user_role'] ?? '',
        ];

        $this->view('home', $data);
    }
}
