<?php

class Divisionaltreasurer extends Controller {
    public function index() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }

        if (($_SESSION['user_role'] ?? '') !== 'DivisionalTreasurer') {
            $this->redirect('home');
        }

        $this->redirect('divisionalledger');
    }
}
