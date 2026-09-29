<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/FamilyList.php';
require_once __DIR__ . '/../src/classes/User.php';

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

$list = $lists->getListById($listId);
$user = new User($pdo);
$members = $user->findUserByFamilyId($list->fam_id);

if (isset($_POST['saveAccessBtn'])) {
    $checked = $_POST['access'] ?? [];
    foreach ($members as $member) {
        if ($member->id == $list->owner_id) {
            continue;
        }
        if (in_array($member->id, $checked)) {
            if (!$lists->hasAccess($listId, $member->id)) {
                $lists->addAccess($listId, $member->id);
            }
        } else {
            if ($lists->hasAccess($listId, $member->id)) {
                $lists->removeAccess($listId, $member->id);
            }
        }
    }
    header("Location: /list/ListDetail.php?id=" . $listId);
    exit();
}

$currentAccess = $lists->getAccessUserIds($listId);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<h2>Zugriff verwalten</h2>

<div class="contentcontainer">
    <form action="" method="POST">
        <?php foreach($members as $member): ?>
            <?php if($member->id != $list->owner_id): ?>
                <label>
                    <input type="checkbox" name="access[]" value="<?= $member->id ?>" <?= in_array($member->id, $currentAccess) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?>
                </label>
            <?php endif; ?>
            <?php endforeach; ?>

            <button type="submit" name="saveAccessBtn">Speichern</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>