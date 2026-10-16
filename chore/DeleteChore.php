<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Chore.php';
require_once __DIR__ . '/../src/functions.php';

require_once __DIR__ . '/../src/components/guard.php';

$choreId = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($choreId === null || !verifyCsrfToken($_GET['csrf_token'] ?? '')) {
    header("Location: /chore/Chores.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$choreClass = new Chore($pdo);

$chore = $choreClass->getChoreById($choreId);

// Sicherheitscheck: gehoert das Ämtli zur aktuellen Familie und darf ich es ueberhaupt sehen?
if (!$chore || !isset($_SESSION['fam_id']) || $chore->fam_id != $_SESSION['fam_id'] || !$choreClass->isVisibleToUser($chore, $_SESSION['uid'])) {
    header("Location: /chore/Chores.php");
    exit();
}

$choreClass->deleteChore($choreId);

header("Location: /chore/Chores.php");
exit();
