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

if (!$lists->hasAccess($listId, $_SESSION['uid'])) {
    header("Location: /list/Lists.php");
    exit();
}

if (isset($_POST['renameBtn'])) {
    $newName = trim($_POST['name'] ?? '');
    if ($newName !== '') {
        $lists->renameList($listId, $newName);
    }
    header("Location: /list/ListDetail.php?id=" . $listId);
    exit();
}

$list = $lists->getListById($listId);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<h2>Liste umbenennen</h2>

<div class="contentcontainer">
    <form action="" method="POST">
        <input type="text" name="name" value="<?= htmlspecialchars($list->name) ?>" required>
        <button type="submit" name="renameBtn">Speichern</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>