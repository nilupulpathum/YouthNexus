<?php

class Model {

    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // Run any query with optional bound parameters
    protected function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Return multiple rows
    protected function resultSet($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    // Return a single row
    protected function single($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    // Return row count
    protected function rowCount($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }
}