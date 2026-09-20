<?php


class Database {
    public function connect() {
       $config = include __DIR__ . '/../config/bdconfig.php';

        try {
            $dsn = "mysql:host=" .$config['dbhost'].";dbname=" .$config['dbname'];
            $pdo = new PDO($dsn, $config['dbuser'], $config['dbpw']);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        }
        catch(PDOException $e) {
            die("Error gefunden: ".$e->getMessage());
        }
    }
}

?>