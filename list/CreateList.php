<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/FamilyList.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Validator.php';
require_once __DIR__ . '/../src/classes/Notification.php';
require_once __DIR__ . '/../src/components/guard.php';


if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$errors = $_SESSION['errors'] ?? [];

if (isset($_POST['createListBtn'])) {
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

    $listId = $lists->createList($_SESSION['fam_id'], $_SESSION['uid'], $name);
    $lists->addAccess($listId, $_SESSION['uid']);


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

    header("Location: /list/Lists.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$user = new User($pdo);
$members = $user->findUserByFamilyId($_SESSION['fam_id']);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';

?>

<a href="/list/Lists.php" class="backLink">&larr;</a>

<h2>Neue Liste</h2>

<?php foreach ($errors as $error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>
<?php unset($_SESSION['errors']); ?>

<div class="contentcontainer">
    <form action="" method="POST">
        <input type="text" name="name" placeholder="Listenname" required>
        <p>Mit wem teilen? (nichts ankreuzen = privat, nur du) </p>
        <?php foreach ($members as $member): ?>
            <?php if ($member->id != $_SESSION['uid']): ?>
                <label>
                    <input type="checkbox" name="sharedWith[]" value="<?= $member->id ?>">
                    <?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?>
                </label>
            <?php endif; ?>
        <?php endforeach; ?>

        <button type="submit" name="createListBtn">Liste erstellen</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>