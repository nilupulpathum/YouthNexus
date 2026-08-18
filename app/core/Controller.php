<?php

class Controller {

    /**
     * Load a view file, optionally passing data to it.
     *
     * @param string $name  View name, supports subdirectories e.g. 'auth/signin'
     * @param array  $data  Associative array of variables to extract into view scope
     */
    public function view($name, $data = []) {
        // Make all $data keys available as local variables inside the view
        if (!empty($data)) {
            extract($data);
        }

        $filename = "../app/views/" . $name . ".view.php";

        if (file_exists($filename)) {
            require $filename;
        } else {
            require "../app/views/404.view.php";
        }
    }

    /**
     * Load a model class by name.
     *
     * @param string $model  Model class name e.g. 'UserModel'
     * @return object        Instance of the model
     */
    public function model($model) {
        $filename = "../app/models/" . $model . ".php";
        if (file_exists($filename)) {
            require_once $filename;
            return new $model();
        }
        die("Model '{$model}' not found.");
    }

    /**
     * Redirect to a URL relative to ROOT.
     *
     * @param string $path  e.g. 'auth/signin'
     */
    public function redirect($path) {
        header('Location: ' . ROOT . '/' . ltrim($path, '/'));
        exit();
    }
}