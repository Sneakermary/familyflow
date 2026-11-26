<?php
// ---------------------------------------------------------------
// Datenbank-Update: Tabelle login_attempts (Login-Sperre nach Fehlversuchen)
// Gespeichert werden nur FEHLGESCHLAGENE Logins, E-Mail und IP nur als Hash
// (Datensparsamkeit, DSGVO). Eintraege werden nach 24 Stunden geloescht.
// Kann gefahrlos mehrfach ausgefuehrt werden.
//
// Ausfuehren (nur auf der Kommandozeile):
//   php migrations/2026-11-26_login_attempts.php
// ---------------------------------------------------------------

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Nur auf der Kommandozeile ausfuehrbar.');
}

$config = include __DIR__ . '/../src/config/dbconfig.php';
// Optional andere Datenbank (zum Testen): php ... <dbname>
$dbname = $argv[1] ?? $config['dbname'];

$pdo = new PDO("mysql:host={$config['dbhost']};dbname={$dbname};charset=utf8mb4", $config['dbuser'], $config['dbpw']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Datenbank: $dbname\n\nlogin_attempts:\n";

$stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'login_attempts'");
$stmt->execute();

if ($stmt->fetchColumn() > 0) {
    echo "  ok      Tabelle login_attempts ist schon da\n";
} else {
    $pdo->exec("CREATE TABLE `login_attempts` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `email_hash` CHAR(64) NOT NULL,
        `ip_hash` CHAR(64) NOT NULL,
        `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_email_time` (`email_hash`, `attempted_at`),
        KEY `idx_ip_time` (`ip_hash`, `attempted_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "  NEU     Tabelle login_attempts angelegt\n";
}

echo "\nFertig.\n";
