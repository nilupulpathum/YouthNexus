<?php


class Home extends Controller {

    public function index($a='', $b='', $c=''){
        echo "This is home controller!";
        $this->view('home');
    }
}
