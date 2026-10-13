<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Chore.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Validator.php';
require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$choreId = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($choreId === null) {
    header("Location: /chore/Chores.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$choreClass = new Chore($pdo);

$chore = $choreClass->getChoreById($choreId);

// Sicherheitscheck: gehoert das Aemtli zur aktuellen Familie und darf ich es ueberhaupt sehen?
if (!$chore || $chore->fam_id != $_SESSION['fam_id'] || !$choreClass->isVisibleToUser($chore, $_SESSION['uid'])) {
    header("Location: /chore/Chores.php");
    exit();
}

$validRotations = ['none', 'daily', 'weekly', 'monthly', 'half_yearly', 'yearly'];
$validVisibilities = ['owner', 'selected', 'assignee', 'all'];

$errors = $_SESSION['errors'] ?? [];

if (isset($_POST['saveChoreBtn'])) {
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
        header("Location: /chore/EditChore.php?id=" . $choreId);
        exit();
    }

    if ($rotation === 'none') {
        $assignedUserId = $_POST['assigned_user_id'] !== '' ? (int) $_POST['assigned_user_id'] : null;
        $rotationStart = null;
    } else {
        $assignedUserId = null;
        $rotationStart = $_POST['rotation_start'] ?? date('Y-m-d');
    }

    $choreClass->updateChore($choreId, $name, $visibility, $assignedUserId, $rotation, $rotationStart);

    // Immer neu setzen (auch leer), damit alte Eintraege verschwinden, wenn z.B.
    // von Rotation auf "Fest zugeteilt" gewechselt wird
    $choreClass->setRotationUserIds($choreId, $rotation !== 'none' ? $rotationUsers : []);

    $accessUsers = $_POST['accessUsers'] ?? [];
    $choreClass->setAccess($choreId, $visibility === 'selected' ? $accessUsers : []);

    header("Location: /chore/Chores.php");
    exit();
}

if (isset($_POST['deleteChoreBtn'])) {
    $choreClass->deleteChore($choreId);
    header("Location: /chore/Chores.php");
    exit();
}

$user = new User($pdo);
$members = $user->findUserByFamilyId($_SESSION['fam_id']);
$currentRotationUsers = $choreClass->getRotationUserIds($choreId);
$currentAccessUsers = $choreClass->getAccessUserIds($choreId);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="/chore/Chores.php" class="backLink">&larr;</a>

<h2>Ämtli bearbeiten</h2>

<?php foreach ($errors as $error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>
<?php unset($_SESSION['errors']); ?>

<div class="contentcontainer">
    <form action="" method="POST">
        <input type="text" name="name" value="<?= htmlspecialchars($chore->name) ?>" required>

        <label>Zuständig:
            <select name="rotation" id="rotationSelect">
                <option value="none" <?= $chore->rotation === 'none' ? 'selected' : '' ?>>Fest zugeteilt</option>
                <option value="daily" <?= $chore->rotation === 'daily' ? 'selected' : '' ?>>Täglich rotierend</option>
                <option value="weekly" <?= $chore->rotation === 'weekly' ? 'selected' : '' ?>>Wöchentlich rotierend</option>
                <option value="monthly" <?= $chore->rotation === 'monthly' ? 'selected' : '' ?>>Monatlich rotierend</option>
                <option value="half_yearly" <?= $chore->rotation === 'half_yearly' ? 'selected' : '' ?>>Halbjährlich rotierend</option>
                <option value="yearly" <?= $chore->rotation === 'yearly' ? 'selected' : '' ?>>Jährlich rotierend</option>
            </select>
        </label>

        <div id="assignedField">
            <label>Person:
                <select name="assigned_user_id">
                    <option value="">Noch niemand</option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?= $member->id ?>" <?= $chore->assigned_user_id == $member->id ? 'selected' : '' ?>><?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div id="rotationFields" class="hidden">
            <label>Start der Rotation: <input type="date" name="rotation_start" value="<?= htmlspecialchars($chore->rotation_start ?? date('Y-m-d')) ?>"></label>
            <p>Teilnehmer der Rotation:</p>
            <?php foreach ($members as $member): ?>
                <label>
                    <input type="checkbox" name="rotationUsers[]" value="<?= $member->id ?>" <?= in_array($member->id, $currentRotationUsers) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?>
                </label>
            <?php endforeach; ?>
        </div>

        <label>Sichtbarkeit:
            <select name="visibility" id="visibilitySelect">
                <option value="owner" <?= $chore->visibility === 'owner' ? 'selected' : '' ?>>Nur ich</option>
                <option value="selected" <?= $chore->visibility === 'selected' ? 'selected' : '' ?>>Bestimmte Personen</option>
                <option value="assignee" <?= $chore->visibility === 'assignee' ? 'selected' : '' ?>>Nur die zuständige Person</option>
                <option value="all" <?= $chore->visibility === 'all' ? 'selected' : '' ?>>Alle</option>
            </select>
        </label>

        <div id="accessFields" class="hidden">
            <?php foreach ($members as $member): ?>
                <label>
                    <input type="checkbox" name="accessUsers[]" value="<?= $member->id ?>" <?= in_array($member->id, $currentAccessUsers) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?>
                </label>
            <?php endforeach; ?>
        </div>

        <button type="submit" name="saveChoreBtn">Speichern</button>
    </form>

    <form action="" method="POST" onsubmit="return confirm('Ämtli wirklich löschen?')">
        <button type="submit" name="deleteChoreBtn">Ämtli löschen</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
