<?php
// ---------------------------------------------------------------
// Datenbank-Update: Mehrfachauswahl bei Rezepten ("Teilen mit") und
// Aemtli ("Zustaendig" bei fester Zuteilung).
//   recipes.shared_with      -> neue Tabelle recipe_shared_with
//   chores.assigned_user_id  -> neue Tabelle chore_assignees
// Bestehende Zuordnungen werden uebernommen, danach die alten Spalten geloescht.
// Kann gefahrlos mehrfach ausgefuehrt werden.
//
// Ausfuehren (nur auf der Kommandozeile):
//   php migrations/2026-11-20b_mehrfachauswahl.php
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

function createTableIfMissing($pdo, $table, $sql) {
    if (tableExists($pdo, $table)) {
        echo "  ok      Tabelle $table ist schon da\n";
        return;
    }
    $pdo->exec($sql);
    echo "  NEU     Tabelle $table angelegt\n";
}

// Alte Spalte samt Fremdschluessel (Name je nach DB verschieden) und Index loeschen
function dropColumnWithForeignKey($pdo, $table, $column) {
    $stmt = $pdo->prepare("
        SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $stmt->execute([$table, $column]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $fkName) {
        $pdo->exec("ALTER TABLE `$table` DROP FOREIGN KEY `$fkName`");
    }
    $pdo->exec("ALTER TABLE `$table` DROP COLUMN `$column`");
    echo "  NEU     alte Spalte $table.$column entfernt\n";
}

echo "Datenbank: $dbname\n\n";

// --- Rezepte: Teilen mit mehreren Personen -------------------------
echo "recipe_shared_with:\n";
createTableIfMissing($pdo, 'recipe_shared_with', "
    CREATE TABLE `recipe_shared_with` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `recipe_id` int NOT NULL,
      `user_id` int NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_recipe_user` (`recipe_id`,`user_id`),
      KEY `fk_recipe_shared_user` (`user_id`),
      CONSTRAINT `fk_recipe_shared_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE,
      CONSTRAINT `fk_recipe_shared_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
");

if (columnExists($pdo, 'recipes', 'shared_with')) {
    // Frueher: shared_with = eine Person (oder man selbst = privat).
    // Neu: Ersteller:in + diese Person eintragen -> nur die beiden sehen es.
    $copied = $pdo->exec("
        INSERT IGNORE INTO recipe_shared_with (recipe_id, user_id)
        SELECT id, shared_with FROM recipes WHERE shared_with IS NOT NULL
        UNION
        SELECT id, owner_id FROM recipes WHERE shared_with IS NOT NULL
    ");
    echo "  NEU     $copied Freigaben aus recipes.shared_with übernommen\n";
    dropColumnWithForeignKey($pdo, 'recipes', 'shared_with');
} else {
    echo "  ok      alte Spalte recipes.shared_with ist schon weg\n";
}

// --- Aemtli: mehrere feste Zustaendige -----------------------------
echo "\nchore_assignees:\n";
createTableIfMissing($pdo, 'chore_assignees', "
    CREATE TABLE `chore_assignees` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `chore_id` int unsigned NOT NULL,
      `user_id` int NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_chore_assignee` (`chore_id`,`user_id`),
      KEY `fk_chore_assignees_user` (`user_id`),
      CONSTRAINT `fk_chore_assignees_chore` FOREIGN KEY (`chore_id`) REFERENCES `chores` (`id`) ON DELETE CASCADE,
      CONSTRAINT `fk_chore_assignees_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
");

if (columnExists($pdo, 'chores', 'assigned_user_id')) {
    $copied = $pdo->exec("
        INSERT IGNORE INTO chore_assignees (chore_id, user_id)
        SELECT id, assigned_user_id FROM chores WHERE assigned_user_id IS NOT NULL AND rotation = 'none'
    ");
    echo "  NEU     $copied Zuständige aus chores.assigned_user_id übernommen\n";
    dropColumnWithForeignKey($pdo, 'chores', 'assigned_user_id');
} else {
    echo "  ok      alte Spalte chores.assigned_user_id ist schon weg\n";
}

// --- Termine: "privat" reparieren ------------------------------------
// Fehler bis heute: "Für alle sichtbar" aus + niemand angekreuzt wurde als
// leere Liste gespeichert (= fuer alle sichtbar). Laesst sich nachtraeglich nicht
// mehr von echten "fuer alle"-Terminen unterscheiden, deshalb hier nichts zu tun -
// ab jetzt wird die erstellende Person immer mit eingetragen.

echo "\nFertig.\n";
