<?php

class App {

    private $controller = 'Home';
    private $method     = 'index';

    private function splitURL() {
        $url = $_GET['url'] ?? 'home';
        // Sanitise: strip tags, trim slashes, remove any ../ traversal
        $url = filter_var(trim($url, '/'), FILTER_SANITIZE_URL);
        return explode('/', $url);
    }

    public function loadController() {

        $URL = $this->splitURL();

        // Segment 1 → Controller filename  (e.g. 'auth' → Auth.php)
        $controllerName = ucfirst(strtolower($URL[0]));
        $filename = "../app/controllers/" . $controllerName . ".php";

        if (file_exists($filename)) {
            require $filename;
            $this->controller = $controllerName;
        } else {
            require "../app/controllers/_404.php";
            $this->controller = '_404';
        }

        // Segment 2 → Method  (e.g. 'signin')
        if (isset($URL[1]) && !empty($URL[1])) {
            $methodName = strtolower($URL[1]);
            if (method_exists($this->controller, $methodName)) {
                $this->method = $methodName;
            } else {
                // Fix #9: Method not found — show 404 instead of silently calling index()
                require "../app/controllers/_404.php";
                $this->controller = '_404';
                $this->method     = 'index';
            }
        }

        $controller = new $this->controller;

        // TEMPORARY LOCAL PATCH — flagged to team, not yet fixed upstream on
        // feat-signin. Without this, any URL segment beyond controller/method
        // (e.g. the :id in clubregistration/review/5) is silently dropped,
        // so every method expecting a parameter always receives null.
        $params = array_slice($URL, 2);
        call_user_func_array([$controller, $this->method], $params);
    }
}