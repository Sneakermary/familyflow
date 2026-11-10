<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/FamilyList.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Validator.php';
require_once __DIR__ . '/../src/classes/Notification.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/components/guard.php';

// Aus "Meine Listen" angelegt: die Liste gehoert dann zu gar keiner Familie (fam_id NULL),
// rein privat, kein "Teilen mit"
$isPersonal = isset($_GET['personal']) || isset($_POST['personal']);

if (!$isPersonal && !isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$errors = $_SESSION['errors'] ?? [];

if (isset($_POST['createListBtn'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /list/CreateList.php");
        exit();
    }

    $name = trim($_POST['name'] ?? '');
    $validator = new Validator;

    if (!$validator->required($name)) {
        $errors['name'] = 'Name ist erforderlich';
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: /list/CreateList.php");
        exit();
    }

    $db = new Database;
    $pdo = $db->connect();
    $lists = new FamilyList($pdo);
    $user = new User($pdo);

    $listId = $lists->createList($isPersonal ? null : $_SESSION['fam_id'], $_SESSION['uid'], $name);
    $lists->addAccess($listId, $_SESSION['uid']);

    // Teilen mit anderen gibt es nur innerhalb einer Familie - eine private Liste
    // (familienlos) kann niemand sonst sehen
    if (!$isPersonal) {
        $sharedWith = $_POST['sharedWith'] ?? [];
        foreach ($sharedWith as $memberid) {
            $lists->addAccess($listId, $memberid);
        }

        // Benachrichtigung an alle, mit denen die Liste geteilt wurde
        $notificationClass = new Notification($pdo);
        $fromUser = $user->findById($_SESSION['uid']);
        $listLink = '/list/ListDetail.php?id=' . $listId;

        foreach ($sharedWith as $memberid) {
            $notificationClass->notify(
                $memberid,
                $fromUser->firstname . ' ' . $fromUser->lastname . ' hat eine Liste mit dir geteilt: ' . $name,
                $listLink
            );
        }
    }

    header("Location: /list/ListDetail.php?id=" . $listId);
    exit();
}

$db = new Database;
$pdo = $db->connect();
$user = new User($pdo);
$members = $isPersonal ? [] : $user->findUserByFamilyId($_SESSION['fam_id']);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';

?>

<a href="#" class="backLink" onclick="history.back(); return false;">&larr;</a>

<h2>Neue Liste</h2>

<?php foreach ($errors as $error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>
<?php unset($_SESSION['errors']); ?>

<div class="contentcontainer">
    <form action="" method="POST">
        <input type="text" name="name" placeholder="Listenname" required>

        <?php if ($isPersonal): ?>
            <input type="hidden" name="personal" value="1">
            <p>Diese Liste gehört zu keiner Familie und ist rein privat.</p>
        <?php else: ?>
            <p>Mit wem teilen? (nichts ankreuzen = privat, nur du) </p>
            <?php foreach ($members as $member): ?>
                <?php if ($member->id != $_SESSION['uid']): ?>
                    <label>
                        <input type="checkbox" name="sharedWith[]" value="<?= $member->id ?>">
                        <?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <?= csrfField() ?>
        <button type="submit" name="createListBtn">Liste erstellen</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>