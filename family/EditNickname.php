<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Nickname.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$targetId = $_GET['id'] ?? null;

if ($targetId === null) {
    header("Location: /family/MyFamily.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$user = new User($pdo);
$nicknameClass = new Nickname($pdo);

// Sicherheitscheck: gehört die Zielperson überhaupt zur eigenen Familie?
$members = $user->findUserByFamilyId($_SESSION['fam_id']);
$targetUser = null;
foreach ($members as $member) {
    if ($member->id == $targetId) {
        $targetUser = $member;
        break;
    }
}

if ($targetUser === null) {
    header("Location: /family/MyFamily.php");
    exit();
}

if (isset($_POST['saveNicknameBtn'])) {
    $newNickname = trim($_POST['nickname'] ?? '');

    if ($newNickname === '') {
        $nicknameClass->deleteNickname($_SESSION['uid'], $targetId);
    } else {
        $nicknameClass->setNickname($_SESSION['uid'], $targetId, $newNickname);
    }
    header("Location: /family/MyFamily.php");
    exit();
}

$currentNickname = $nicknameClass->getNickname($_SESSION['uid'], $targetId);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="/family/MyFamily.php" class="backLink">&larr;</a>

<h2>Spitzname für <?= htmlspecialchars($targetUser->firstname) ?> <?= htmlspecialchars($targetUser->lastname) ?></h2>

<div class="contentcontainer">
    <form action="" method="POST">
        <input type="text" name="nickname" placeholder="Spitzname" value="<?= htmlspecialchars($currentNickname ?? '') ?>">
        <button type="submit" name="saveNicknameBtn">Speichern</button>
    </form>
    <p>Leer lassen und speichern, um den Spitznamen wieder zu entfernen.</p>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>