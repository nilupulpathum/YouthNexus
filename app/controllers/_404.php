<?php


class _404 extends Controller {

    public function index(){
        echo "This is 404 controller!";
        $this->view('404');
    }
}
