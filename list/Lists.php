<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/FamilyList.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$lists = new FamilyList($pdo);

$myLists = $lists->getListsForUser($_SESSION['uid'], $_SESSION['fam_id']);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="#" class="backLink" onclick="history.back(); return false;">&larr;</a>

<div class="listHeader">
    <h2>Listen</h2>
    <span id="listMenuBtn" class="dots">⋮</span>
    <div id="listMenuList" class="hidden">
        <a href="/list/CreateList.php">Liste erstellen</a>
        <a href="/list/MyLists.php">Meine Listen</a>
    </div>
</div>

<div class="tiles">
<?php foreach ($myLists as $list): ?>
    <div class="tile" data-href="/list/ListDetail.php?id=<?= $list->id ?>">
        <div class="tile_icon">📋</div>
        <div class="tile_title"><?= htmlspecialchars($list->name) ?></div>
        <div class="tile_subtext">
            <?php
                $items = $lists->getItems($list->id);
                echo count($items) > 0 ? count($items) . ' Einträge' : 'Leer';
            ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
