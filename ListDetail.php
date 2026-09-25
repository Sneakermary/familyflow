<?php
require_once __DIR__ . '/src/classes/Database.php';
require_once __DIR__ . '/src/classes/FamilyList.php';

require_once __DIR__ . '/src/components/guard.php';

$listId = $_GET['id'] ?? null;

if ($listId === null) {
    header("Location: Lists.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$lists = new FamilyList($pdo);

// Sicherheitscheck: hat die eingeloggte Person ueberhaupt Zugriff auf diese Liste?
if (!$lists->hasAccess($listId, $_SESSION['uid'])) {
    header("Location: Lists.php");
    exit();
}

// Neuen Eintrag hinzufuegen (POST-Verarbeitung vor jedem HTML)
if (isset($_POST['addItemBtn'])) {
    $content = trim($_POST['content'] ?? '');
    if ($content !== '') {
        $lists->addItem($listId, $content);
    }
    header("Location: ListDetail.php?id=" . $listId);
    exit();
}

// Eintrag abhaken/wieder oeffnen
if (isset($_GET['toggle'])) {
    $lists->toggleItem($_GET['toggle']);
    header("Location: ListDetail.php?id=" . $listId);
    exit();
}

$list = $lists->getListById($listId);
$items = $lists->getItems($listId);

include_once __DIR__ . '/src/components/head.php';
include_once __DIR__ . '/src/components/navbar.php';
?>

<h2><?= htmlspecialchars($list->name) ?></h2>

<?php foreach ($items as $item): ?>
    <div class="member">
        <input type="checkbox" onclick="window.location.href='ListDetail.php?id=<?= $listId ?>&toggle=<?= $item->id ?>'" <?= $item->done ? 'checked' : '' ?>>
        <span><?= htmlspecialchars($item->content) ?></span>
    </div>
<?php endforeach; ?>

<div class="contentcontainer">
    <form action="" method="POST">
        <input type="text" name="content" placeholder="Neuer Eintrag" required>
        <button type="submit" name="addItemBtn">Hinzufügen</button>
    </form>
</div>

<?php include_once __DIR__ . '/src/components/footer.php'; ?>
