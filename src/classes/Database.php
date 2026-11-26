<?php


class Database {
    public function connect() {
       $config = include __DIR__ . '/../config/dbconfig.php';

        try {
            $dsn = "mysql:host=" .$config['dbhost'].";dbname=" .$config['dbname'];
            $pdo = new PDO($dsn, $config['dbuser'], $config['dbpw']);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        }
        catch(PDOException $e) {
            // Technische Details (Servername, Benutzer, ...) NUR ins Server-Log, nie in den Browser.
            // Am Pi nachlesen: sudo tail /var/log/apache2/error.log
            error_log('FamilyFlow: Datenbank nicht erreichbar - ' . $e->getMessage());

            // Besucher bekommen eine freundliche Seite (HTTP 503 = voruebergehend nicht verfuegbar)
            include __DIR__ . '/../components/db_error.php';
            exit();
        }
    }
}

?>