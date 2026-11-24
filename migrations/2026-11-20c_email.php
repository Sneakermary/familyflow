<?php
// ---------------------------------------------------------------
// Datenbank-Update: E-Mail bestaetigen + Passwort zuruecksetzen
//   users.email_verified_at  (alle BESTEHENDEN User gelten als bestaetigt)
//   neue Tabelle user_tokens (Einmal-Links, nur als Hash gespeichert)
// Kann gefahrlos mehrfach ausgefuehrt werden.
//
// Ausfuehren (nur auf der Kommandozeile):
//   php migrations/2026-11-20c_email.php
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

function tableExists($pdo, $table) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->execute([$table]);
    return $stmt->fetchColumn() > 0;
}

function columnExists($pdo, $table, $column) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    return $stmt->fetchColumn() > 0;
}

echo "Datenbank: $dbname\n\n";

echo "users:\n";
if (columnExists($pdo, 'users', 'email_verified_at')) {
    echo "  ok      users.email_verified_at ist schon da\n";
} else {
    $pdo->exec("ALTER TABLE `users` ADD COLUMN `email_verified_at` DATETIME DEFAULT NULL AFTER `email`");
    // Wer schon registriert ist, soll sich weiter einloggen koennen
    $count = $pdo->exec("UPDATE `users` SET `email_verified_at` = NOW()");
    echo "  NEU     users.email_verified_at angelegt, $count bestehende User als bestätigt markiert\n";
}

echo "\nuser_tokens:\n";
if (tableExists($pdo, 'user_tokens')) {
    echo "  ok      Tabelle user_tokens ist schon da\n";
} else {
    $pdo->exec("
        CREATE TABLE `user_tokens` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `user_id` int NOT NULL,
          `type` enum('verify','reset') NOT NULL,
          `token_hash` char(64) NOT NULL,
          `expires_at` datetime NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_token_hash` (`token_hash`),
          KEY `fk_user_tokens_user` (`user_id`),
          CONSTRAINT `fk_user_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "  NEU     Tabelle user_tokens angelegt\n";
}

echo "\nFertig.\n";
