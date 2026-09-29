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

// Sicherheitscheck: hat die eingeloggte Person ueberhaupt Zugriff auf diese Liste?
if (!$lists->hasAccess($listId, $_SESSION['uid'])) {
    header("Location: /list/Lists.php");
    exit();
}

// Neuen Eintrag hinzufuegen (POST-Verarbeitung vor jedem HTML)
if (isset($_POST['addItemBtn'])) {
    $content = trim($_POST['content'] ?? '');
    if ($content !== '') {
        $lists->addItem($listId, $content);
    }
    header("Location: /list/ListDetail.php?id=" . $listId);
    exit();
}

// Eintrag abhaken/wieder oeffnen
if (isset($_GET['toggle'])) {
    $lists->toggleItem($_GET['toggle']);
    header("Location: /list/ListDetail.php?id=" . $listId);
    exit();
}

$list = $lists->getListById($listId);
$items = $lists->getItems($listId);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="/list/Lists.php" class="backLink">&larr;</a>

<div class="listHeader">
    <h2><?= htmlspecialchars($list->name) ?></h2>
    <span id="listMenuBtn" class="dots">⋮</span>
    <div id="listMenuList" class="hidden">
        <a href="#" onclick="var name = prompt('Neuer Name:'); if(name) {window.location.href='/list/RenameList.php?id=<?= $listId ?>&name=' + encodeURIComponent(name); } return false;">Umbenennen</a>
        <a href="/list/DeleteList.php?id=<?= $listId ?>" onclick="return confirm('Liste wirklich löschen?')">Liste Löschen</a>
        <a href="/list/ManageAccess.php?id=<?= $listId ?>">Zugriff verwalten</a>
        <a href="#" id="hideDoneBtn">Erledigte ausblenden</a>
    </div>
</div>

<?php foreach ($items as $item): ?>
    <div class="member <?= $item->done ? 'done' : '' ?>">
        <input type="checkbox" onclick="window.location.href='/list/ListDetail.php?id=<?= $listId ?>&toggle=<?= $item->id ?>'" <?= $item->done ? 'checked' : '' ?>>
        <span><?= htmlspecialchars($item->content) ?></span>
    </div>
<?php endforeach; ?>

<div class="contentcontainer">
    <form action="" method="POST">
        <input type="text" name="content" placeholder="Neuer Eintrag" required>
        <button type="submit" name="addItemBtn">Hinzufügen</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
