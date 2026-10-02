<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Family.php';
require_once __DIR__ . '/../src/classes/FamilyInvite.php';
require_once __DIR__ . '/../src/classes/Notification.php';
require_once __DIR__ . '/../src/classes/Validator.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$errors = $_SESSION['errors'] ?? [];

if (isset($_POST['inviteBtn'])) {
    $email = trim($_POST['email'] ?? '');
    $selectedFamId = $_POST['fam_id'] ?? '';

    $validator = new Validator;

    if (!$validator->required($email)) {
        $errors['email'] = 'Email ist erforderlich';
    }
    if (!$validator->required($selectedFamId)) {
        $errors['fam_id'] = 'Familie ist erforderlich';
    }

    $db = new Database;
    $pdo = $db->connect();
    $user = new User($pdo);
    $family = new Family($pdo);
    $invite = new FamilyInvite($pdo);

    $targetUser = null;

    if (empty($errors)) {
        // Sicherheitscheck: gehoert die ausgewaehlte Familie ueberhaupt zur eigenen Person?
        if (!$family->isMember($_SESSION['uid'], $selectedFamId)) {
            $errors['fam_id'] = 'Ungültige Familie';
        } else {
            $targetUser = $user->findByEmail($email);

            if (!$targetUser) {
                $errors['email'] = 'Diese Email ist nicht registriert';
            } elseif ($targetUser->id == $_SESSION['uid']) {
                $errors['email'] = 'Du kannst dich nicht selbst einladen';
            } elseif ($family->isMember($targetUser->id, $selectedFamId)) {
                $errors['email'] = 'Diese Person ist schon Mitglied';
            } elseif ($invite->hasPendingInvite($selectedFamId, $targetUser->id)) {
                $errors['email'] = 'Diese Person wurde bereits eingeladen';
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: /family/InviteMember.php");
        exit();
    }

    $inviteId = $invite->createInvite($selectedFamId, $_SESSION['uid'], $targetUser->id);

    $familyData = $family->findById($selectedFamId);
    $fromUser = $user->findById($_SESSION['uid']);

    $notificationClass = new Notification($pdo);
    $notificationClass->notify(
        $targetUser->id,
        $fromUser->firstname . ' ' . $fromUser->lastname . ' lädt dich in die Familie "' . $familyData->name . '" ein',
        '/family/AcceptInvite.php?id=' . $inviteId
    );

    header("Location: /family/MyFamily.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$family = new Family($pdo);
$myFamilies = $family->findFamiliesByUserId($_SESSION['uid']);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="/family/MyFamily.php" class="backLink">&larr;</a>

<h2>Mitglied einladen</h2>

<?php foreach ($errors as $error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>
<?php unset($_SESSION['errors']); ?>

<div class="contentcontainer">
    <form action="" method="POST">
        <input type="email" name="email" placeholder="Email der Person" required>

        <label>Familie:
            <select name="fam_id">
                <?php foreach ($myFamilies as $fam): ?>
                    <option value="<?= $fam->id ?>" <?= $fam->id == $_SESSION['fam_id'] ? 'selected' : '' ?>><?= htmlspecialchars($fam->name) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <button type="submit" name="inviteBtn">Einladen</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
