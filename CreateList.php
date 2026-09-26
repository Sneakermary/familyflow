<?php
require_once __DIR__ . '/src/classes/Database.php';
require_once __DIR__ . '/src/classes/FamilyList.php';
require_once __DIR__ . '/src/classes/User.php';
require_once __DIR__ . '/src/classes/Validator.php';
require_once __DIR__ . '/src/classes/Database.php';

if(isset($_SESSION['fam_id'])) {
    header("Location: CreateFamily.php");
    exit();
}

$errors = $_SESSION['errors'] ?? [];

if(isset($_POST['createListBtn'])) {
    $name = trim($_POST['name'] ?? '');
    $validator = new Validator;

    if(!$validator->required($name)) {
        $errors['name'] = 'Name ist erforderlich';
    }

    if(!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: CreateList.php");
        exit();
    }

    $db = new Database;
    $pdo = $db->connect();
    $lists = new FamilyList($pdo);

    $listId = $lists->createList($_SESSION['fam_Id'], $_SESSION['uid'], $name);
    $lists->addAccess($listId, $_SESSION['uid']);


    $sharedWith = $_POST['sharedWith'] ?? [];
    foreach ($sharedWith as $memberid) {
        $lists->addAccess($listId, $memberid);
    }

    header("Location: Lists.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$user = new User($pdo);
$member = $user->findUserByFamilyId($_SESSION['fam_id']);

include_once __DIR__ . '/src/components/head.php';
include_once __DIR__ . '/src/components/navbar.php';

?>

<h2>Neue Liste</h2>

<?php foreach($errors as $error): ?>
    <p><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>
    <?php unset($_SESSION['errors']); ?>

    <div class="contentcontainer">
        <form action="" method="POST">
            <input type="text" name="name" placeholder="Listenname" required>
            <p>Mit wem teilen? (nichts ankreuzen = privat, nur du) </p>
            <?php foreach($members as $member): ?>
                <?php if($member->id != $_SESSION['uid']): ?>
                    <label>
                        <input type="checkbox" name="sharedWith[]" value="<?= $member->id ?>">
                        <?=  htmlspecialchars($member->firstname) ?> <?=  htmlspecialchars($member->lastname) ?>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>

            <button taypr="submit" name="createListBtn">Liste erstellen</button>
        </form>
    </div>

    <?php include_once __DIR__ . '/src/components/footer.php'; ?>