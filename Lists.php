<?php
require_once __DIR__ . '/src/classes/Database.php';
require_once __DIR__ . '/src/classes/FamilyList.php';

require_once __DIR__ . '/src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: CreateFamily.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$lists = new FamilyList($pdo);

$myLists = $lists->getListsForUser($_SESSION['uid']);

include_once __DIR__ . '/src/components/head.php';
include_once __DIR__ . '/src/components/navbar.php';
?>

<h2>Listen</h2>

<div class="tiles">
<?php foreach ($myLists as $list): ?>
    <div class="tile" data-href="ListDetail.php?id=<?= $list->id ?>">
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

<?php include_once __DIR__ . '/src/components/footer.php'; ?>
