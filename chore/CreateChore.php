<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Chore.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Validator.php';
require_once __DIR__ . '/../src/classes/Notification.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$validRotations = ['none', 'daily', 'weekly', 'monthly', 'half_yearly', 'yearly'];
$validVisibilities = ['owner', 'selected', 'assignee', 'all'];

$errors = $_SESSION['errors'] ?? [];

if (isset($_POST['createChoreBtn'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /chore/CreateChore.php");
        exit();
    }

    $name = trim($_POST['name'] ?? '');
    $rotation = $_POST['rotation'] ?? 'none';
    $visibility = $_POST['visibility'] ?? 'owner';
    $validator = new Validator;

    if (!$validator->required($name)) {
        $errors['name'] = 'Name ist erforderlich';
    }
    if (!in_array($rotation, $validRotations)) {
        $errors['rotation'] = 'Ungueltige Rotation';
    }
    if (!in_array($visibility, $validVisibilities)) {
        $errors['visibility'] = 'Ungueltige Sichtbarkeit';
    }

    $rotationUsers = $_POST['rotationUsers'] ?? [];
    if ($rotation !== 'none' && count($rotationUsers) === 0) {
        $errors['rotationUsers'] = 'Mindestens eine Person fuer die Rotation auswaehlen';
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: /chore/CreateChore.php");
        exit();
    }

    $db = new Database;
    $pdo = $db->connect();
    $choreClass = new Chore($pdo);
    $user = new User($pdo);

    if ($rotation === 'none') {
        $assignedUserId = $_POST['assigned_user_id'] !== '' ? (int) $_POST['assigned_user_id'] : null;
        $rotationStart = null;
    } else {
        $assignedUserId = null;
        $rotationStart = $_POST['rotation_start'] ?? date('Y-m-d');
    }

    $choreId = $choreClass->createChore($_SESSION['fam_id'], $_SESSION['uid'], $name, $visibility, $assignedUserId, $rotation, $rotationStart);

    if ($rotation !== 'none') {
        $choreClass->setRotationUserIds($choreId, $rotationUsers);
    }

    $accessUsers = $_POST['accessUsers'] ?? [];
    if ($visibility === 'selected') {
        $choreClass->setAccess($choreId, $accessUsers);
    }

    // Benachrichtigung je nach Sichtbarkeit
    $notificationClass = new Notification($pdo);
    $fromUser = $user->findById($_SESSION['uid']);
    $choreLink = '/chore/Chores.php';
    $message = $fromUser->firstname . ' ' . $fromUser->lastname . ' hat ein Ämtli erstellt: ' . $name;

    if ($visibility === 'all') {
        $members = $user->findUserByFamilyId($_SESSION['fam_id']);
        foreach ($members as $member) {
            if ($member->id != $_SESSION['uid']) {
                $notificationClass->notify($member->id, $message, $choreLink);
            }
        }
    } elseif ($visibility === 'selected') {
        foreach ($accessUsers as $memberId) {
            if ($memberId != $_SESSION['uid']) {
                $notificationClass->notify($memberId, $message, $choreLink);
            }
        }
    } elseif ($visibility === 'assignee') {
        $chore = $choreClass->getChoreById($choreId);
        $currentAssignee = $choreClass->getCurrentAssigneeId($chore);
        if ($currentAssignee && $currentAssignee != $_SESSION['uid']) {
            $notificationClass->notify($currentAssignee, $message, $choreLink);
        }
    }

    header("Location: /chore/Chores.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$user = new User($pdo);
$members = $user->findUserByFamilyId($_SESSION['fam_id']);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="/chore/Chores.php" class="backLink">&larr;</a>

<h2>Neues Ämtli</h2>

<?php foreach ($errors as $error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>
<?php unset($_SESSION['errors']); ?>

<div class="contentcontainer">
    <form action="" method="POST">
        <input type="text" name="name" placeholder="z. B. Müll rausbringen" required>

        <label>Zuständig:
            <select name="rotation" id="rotationSelect">
                <option value="none">Fest zugeteilt</option>
                <option value="daily">Täglich rotierend</option>
                <option value="weekly">Wöchentlich rotierend</option>
                <option value="monthly">Monatlich rotierend</option>
                <option value="half_yearly">Halbjährlich rotierend</option>
                <option value="yearly">Jährlich rotierend</option>
            </select>
        </label>

        <div id="assignedField">
            <label>Person:
                <select name="assigned_user_id">
                    <option value="">Noch niemand</option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?= $member->id ?>"><?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div id="rotationFields" class="hidden">
            <label>Start der Rotation: <input type="date" name="rotation_start" value="<?= date('Y-m-d') ?>"></label>
            <p>Teilnehmer der Rotation:</p>
            <?php foreach ($members as $member): ?>
                <label>
                    <input type="checkbox" name="rotationUsers[]" value="<?= $member->id ?>">
                    <?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?>
                </label>
            <?php endforeach; ?>
        </div>

        <label>Sichtbarkeit:
            <select name="visibility" id="visibilitySelect">
                <option value="owner">Nur ich</option>
                <option value="selected">Bestimmte Personen</option>
                <option value="assignee">Nur die zuständige Person</option>
                <option value="all">Alle</option>
            </select>
        </label>

        <div id="accessFields" class="hidden">
            <?php foreach ($members as $member): ?>
                <label>
                    <input type="checkbox" name="accessUsers[]" value="<?= $member->id ?>">
                    <?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?>
                </label>
            <?php endforeach; ?>
        </div>

        <?= csrfField() ?>
        <button type="submit" name="createChoreBtn">Ämtli erstellen</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
