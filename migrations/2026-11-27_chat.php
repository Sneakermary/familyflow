<?php
// ---------------------------------------------------------------
// Datenbank-Update: Chat (familienuebergreifend)
//   conversations         ein Chat: 'direct' (2 Personen) oder 'group' (mit Namen)
//   conversation_members  wer ist in welchem Chat + bis wohin gelesen (fuer den Zaehler)
//   messages              die einzelnen Nachrichten
// Wird ein Konto geloescht, gehen seine Nachrichten und Mitgliedschaften mit (CASCADE).
// Kann gefahrlos mehrfach ausgefuehrt werden.
//
// Ausfuehren (nur auf der Kommandozeile):
//   php migrations/2026-11-27_chat.php
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

echo "Datenbank: $dbname\n\n";

function tableExists(PDO $pdo, $table) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->execute([$table]);
    return $stmt->fetchColumn() > 0;
}

$tables = [
    'conversations' => "CREATE TABLE `conversations` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `type` ENUM('direct','group') NOT NULL,
        `name` VARCHAR(100) DEFAULT NULL,
        `created_by` INT DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        CONSTRAINT `fk_conv_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'conversation_members' => "CREATE TABLE `conversation_members` (
        `conversation_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `last_read_message_id` INT NOT NULL DEFAULT 0,
        `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`conversation_id`, `user_id`),
        KEY `idx_member_user` (`user_id`),
        CONSTRAINT `fk_cm_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_cm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'messages' => "CREATE TABLE `messages` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `conversation_id` INT NOT NULL,
        `sender_id` INT NOT NULL,
        `body` TEXT NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_msg_conv` (`conversation_id`, `id`),
        CONSTRAINT `fk_msg_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($tables as $name => $sql) {
    if (tableExists($pdo, $name)) {
        echo "  ok      Tabelle $name ist schon da\n";
    } else {
        $pdo->exec($sql);
        echo "  NEU     Tabelle $name angelegt\n";
    }
}

echo "\nFertig.\n";
