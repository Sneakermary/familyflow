<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/FamilyList.php';

require_once __DIR__ . '/../src/components/guard.php';

$db = new Database;
$pdo = $db->connect();
$lists = new FamilyList($pdo);

// Familienuebergreifend: zeigt Listen aus allen Familien, nicht nur der gerade aktiven
$myLists = $lists->getListsOwnedByUser($_SESSION['uid']);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="#" class="backLink" onclick="history.back(); return false;">&larr;</a>

<div class="listHeader">
    <h2>Meine Listen</h2>
    <span id="listMenuBtn" class="dots">⋮</span>
    <div id="listMenuList" class="hidden">
        <a href="/list/CreateList.php?personal=1">Neue Liste erstellen</a>
        <a href="/list/Lists.php">Zu den Familien-Listen</a>
    </div>
</div>

<div class="tiles">
<?php foreach ($myLists as $list): ?>
    <div class="tile" data-href="/list/ListDetail.php?id=<?= $list->id ?>">
        <div class="tile_icon">📋</div>
        <div class="tile_title"><?= htmlspecialchars($list->name) ?></div>
        <div class="tile_subtext">
            <?php if ($list->family_name): ?>
                <?= htmlspecialchars($list->family_name) ?>
            <?php else: ?>
                Privat
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="privateLockIcon">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
