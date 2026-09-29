<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/FamilyList.php';

require_once __DIR__ . '/../src/components/guard.php';

$listId = $_GET['id'] ?? null;

if ($listId === null) {
    header("Location: /list/Lists.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$lists = new FamilyList($pdo);

// Sicherheitscheck: hat die eingeloggte Person Zugriff auf diese Liste?
if (!$lists->hasAccess($listId, $_SESSION['uid'])) {
    header("Location: /list/Lists.php");
    exit();
}

$lists->deleteList($listId);
header("Location: /list/Lists.php");
exit();
