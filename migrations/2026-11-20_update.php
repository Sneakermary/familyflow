<?php
// ---------------------------------------------------------------
// Datenbank-Update fuer alle Aenderungen bis 2026-11-20.
// Kann gefahrlos mehrfach ausgefuehrt werden: Es wird nur angelegt,
// was noch fehlt (bestehende Daten bleiben erhalten).
//
// Ausfuehren (nur auf der Kommandozeile, nicht im Browser):
//   php migrations/2026-11-20_update.php
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
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

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

function addColumnIfMissing($pdo, $table, $column, $definition) {
    if (columnExists($pdo, $table, $column)) {
        echo "  ok      $table.$column ist schon da\n";
        return;
    }
    $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    echo "  NEU     $table.$column angelegt\n";
}

function createTableIfMissing($pdo, $table, $sql) {
    if (tableExists($pdo, $table)) {
        echo "  ok      Tabelle $table ist schon da\n";
        return;
    }
    $pdo->exec($sql);
    echo "  NEU     Tabelle $table angelegt\n";
}

echo "Datenbank: $dbname\n\n";

// --- users ---------------------------------------------------------
echo "users:\n";
addColumnIfMissing($pdo, 'users', 'avatar', "VARCHAR(255) DEFAULT NULL");
addColumnIfMissing($pdo, 'users', 'birthdate', "DATE DEFAULT NULL AFTER `email`");
addColumnIfMissing($pdo, 'users', 'theme', "VARCHAR(30) NOT NULL DEFAULT 'standard'");
addColumnIfMissing($pdo, 'users', 'calendar_color', "VARCHAR(7) DEFAULT NULL");

// --- profile_notes -------------------------------------------------
echo "\nprofile_notes:\n";
createTableIfMissing($pdo, 'profile_notes', "
    CREATE TABLE `profile_notes` (
      `id` int NOT NULL AUTO_INCREMENT,
      `owner_id` int NOT NULL,
      `target_id` int NOT NULL,
      `label` varchar(255) NOT NULL,
      `value` text NOT NULL,
      `is_private` tinyint(1) NOT NULL DEFAULT '1',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `fk_profile_notes_target` (`target_id`),
      KEY `fk_profile_notes_owner` (`owner_id`),
      CONSTRAINT `fk_profile_notes_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
      CONSTRAINT `fk_profile_notes_target` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
");
addColumnIfMissing($pdo, 'profile_notes', 'is_private', "TINYINT(1) NOT NULL DEFAULT '1' AFTER `value`");

// --- calendar_family_settings ("Mein Kalender") --------------------
echo "\ncalendar_family_settings:\n";
createTableIfMissing($pdo, 'calendar_family_settings', "
    CREATE TABLE `calendar_family_settings` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `user_id` int NOT NULL,
      `fam_id` int unsigned NOT NULL,
      `visible` tinyint(1) NOT NULL DEFAULT '1',
      `color` varchar(7) NOT NULL DEFAULT '#6b7280',
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_user_fam` (`user_id`,`fam_id`),
      KEY `fam_id` (`fam_id`),
      CONSTRAINT `calendar_family_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
      CONSTRAINT `calendar_family_settings_ibfk_2` FOREIGN KEY (`fam_id`) REFERENCES `families` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
");

// --- feedback ------------------------------------------------------
echo "\nfeedback:\n";
createTableIfMissing($pdo, 'feedback', "
    CREATE TABLE `feedback` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `user_id` int NOT NULL,
      `dashboard` text,
      `listen` text,
      `kalender` text,
      `meine_familie` text,
      `rezepte` text,
      `aufgaben` text,
      `layout` text,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `user_id` (`user_id`),
      CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
");

// --- Termine: "Teilen mit" fuer mehrere Personen --------------------
echo "\nevent_shared_with:\n";
createTableIfMissing($pdo, 'event_shared_with', "
    CREATE TABLE `event_shared_with` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `event_id` int NOT NULL,
      `user_id` int NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_event_user` (`event_id`,`user_id`),
      KEY `fk_shared_user` (`user_id`),
      CONSTRAINT `fk_shared_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
      CONSTRAINT `fk_shared_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
");

// Alte Spalte events.shared_with: Daten in die neue Tabelle uebernehmen, dann loeschen
if (columnExists($pdo, 'events', 'shared_with')) {
    $copied = $pdo->exec("
        INSERT IGNORE INTO event_shared_with (event_id, user_id)
        SELECT id, shared_with FROM events WHERE shared_with IS NOT NULL
    ");
    echo "  NEU     $copied geteilte Termine aus events.shared_with übernommen\n";

    // Fremdschluessel auf der alten Spalte zuerst entfernen (Name ist je nach DB verschieden)
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events'
          AND COLUMN_NAME = 'shared_with' AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $fkName) {
        $pdo->exec("ALTER TABLE `events` DROP FOREIGN KEY `$fkName`");
    }

    $pdo->exec("ALTER TABLE `events` DROP COLUMN `shared_with`");
    echo "  NEU     alte Spalte events.shared_with entfernt\n";
} else {
    echo "  ok      alte Spalte events.shared_with ist schon weg\n";
}

echo "\nFertig.\n";
