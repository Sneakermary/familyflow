<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Family.php';
require_once __DIR__ . '/../src/classes/FamilyInvite.php';
require_once __DIR__ . '/../src/classes/Notification.php';

require_once __DIR__ . '/../src/components/guard.php';

$inviteId = $_GET['id'] ?? null;

if ($inviteId === null) {
    header("Location: /Dashboard.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$inviteClass = new FamilyInvite($pdo);
$family = new Family($pdo);
$user = new User($pdo);

$invite = $inviteClass->getInviteById($inviteId);

// Sicherheitscheck: existiert die Einladung, ist sie fuer mich, und noch offen?
if (!$invite || $invite->to_user_id != $_SESSION['uid'] || $invite->status !== 'pending') {
    header("Location: /Dashboard.php");
    exit();
}

$notificationClass = new Notification($pdo);
$toUser = $user->findById($_SESSION['uid']);

if (isset($_POST['acceptBtn'])) {
    $family->addMember($_SESSION['uid'], $invite->fam_id);
    $inviteClass->acceptInvite($inviteId);
    $_SESSION['fam_id'] = $invite->fam_id;

    $notificationClass->notify(
        $invite->from_user_id,
        $toUser->firstname . ' ' . $toUser->lastname . ' hat deine Einladung angenommen',
        '/family/MyFamily.php'
    );

    header("Location: /Dashboard.php");
    exit();
}

if (isset($_POST['declineBtn'])) {
    $inviteClass->declineInvite($inviteId);

    $notificationClass->notify(
        $invite->from_user_id,
        $toUser->firstname . ' ' . $toUser->lastname . ' hat deine Einladung abgelehnt'
    );

    header("Location: /Dashboard.php");
    exit();
}

$familyData = $family->findById($invite->fam_id);
$fromUser = $user->findById($invite->from_user_id);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="/Dashboard.php" class="backLink">&larr;</a>

<h2>Einladung</h2>

<div class="contentcontainer">
    <p><?= htmlspecialchars($fromUser->firstname) ?> <?= htmlspecialchars($fromUser->lastname) ?> hat dich zur Familie "<?= htmlspecialchars($familyData->name) ?>" eingeladen.</p>

    <form action="" method="POST">
        <button type="submit" name="acceptBtn">Annehmen</button>
        <button type="submit" name="declineBtn">Ablehnen</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
