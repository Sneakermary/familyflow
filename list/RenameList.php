<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/FamilyList.php';

require_once __DIR__ . '/../src/components/guard.php';

$listId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$newName = trim($_GET['name'] ?? '');

if ($listId === null || $newName === '') {
    header("Location: /list/Lists.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$lists = new FamilyList($pdo);

if (!$lists->hasAccess($listId, $_SESSION['uid'])) {
    header("Location: /list/Lists.php");
    exit();
}

$lists->renameList($listId, $newName);
header("Location: /list/ListDetail.php?id=" . $listId);
exit();