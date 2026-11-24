<?php
// ---------------------------------------------------------------
// Datenbank-Update: Feedback-Feld "Allgemein" (feedback.allgemein)
// Kann gefahrlos mehrfach ausgefuehrt werden.
//
// Ausfuehren (nur auf der Kommandozeile):
//   php migrations/2026-11-24_feedback_allgemein.php
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

echo "Datenbank: $dbname\n\nfeedback:\n";

$stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'allgemein'");
$stmt->execute();

if ($stmt->fetchColumn() > 0) {
    echo "  ok      feedback.allgemein ist schon da\n";
} else {
    $pdo->exec("ALTER TABLE `feedback` ADD COLUMN `allgemein` TEXT DEFAULT NULL AFTER `layout`");
    echo "  NEU     feedback.allgemein angelegt\n";
}

echo "\nFertig.\n";
