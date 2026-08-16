<?php

class Legal extends Controller {

    public function terms() {
        $this->view('legal/terms');
    }

    public function privacy() {
        $this->view('legal/privacy');
    }

    public function index() {
        $this->terms();
    }
}
