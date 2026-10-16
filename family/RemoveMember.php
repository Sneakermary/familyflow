<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Family.php';
require_once __DIR__ . '/../src/functions.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$targetId = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($targetId === null || !verifyCsrfToken($_GET['csrf_token'] ?? '') || $targetId == $_SESSION['uid']) {
    header("Location: /family/MyFamily.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$family = new Family($pdo);

// Sicherheitscheck: ich selbst und die Zielperson muessen beide Mitglied dieser Familie sein
if (!$family->isMember($_SESSION['uid'], $_SESSION['fam_id']) || !$family->isMember($targetId, $_SESSION['fam_id'])) {
    header("Location: /family/MyFamily.php");
    exit();
}

$family->removeMember($targetId, $_SESSION['fam_id']);

header("Location: /family/MyFamily.php");
exit();
