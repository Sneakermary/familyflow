<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Chore.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Validator.php';
require_once __DIR__ . '/../src/functions.php';
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
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /chore/EditChore.php?id=" . $choreId);
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
        header("Location: /chore/EditChore.php?id=" . $choreId);
        exit();
    }

    // Nur echte Familienmitglieder uebernehmen (Formularwerte kann man im Browser veraendern)
    $members = (new User($pdo))->findUserByFamilyId($_SESSION['fam_id']);
    $rotationUsers = filterFamilyMemberIds($rotationUsers, $members);
    $assignedUsers = filterFamilyMemberIds($_POST['assignedUsers'] ?? [], $members);
    $accessUsers = filterFamilyMemberIds($_POST['accessUsers'] ?? [], $members);

    $rotationStart = $rotation === 'none' ? null : ($_POST['rotation_start'] ?? date('Y-m-d'));

    $choreClass->updateChore($choreId, $name, $visibility, $rotation, $rotationStart);

    // Immer beide neu setzen (eins davon leer), damit alte Eintraege verschwinden, wenn
    // zwischen Rotation und "Fest zugeteilt" gewechselt wird
    $choreClass->setAssignees($choreId, $rotation === 'none' ? $assignedUsers : []);
    $choreClass->setRotationUserIds($choreId, $rotation !== 'none' ? $rotationUsers : []);

    $choreClass->setAccess($choreId, $visibility === 'selected' ? $accessUsers : []);

    header("Location: /chore/Chores.php");
    exit();
}

if (isset($_POST['deleteChoreBtn'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /chore/EditChore.php?id=" . $choreId);
        exit();
    }

    $choreClass->deleteChore($choreId);
    header("Location: /chore/Chores.php");
    exit();
}

$user = new User($pdo);
$members = $user->findUserByFamilyId($_SESSION['fam_id']);
$currentRotationUsers = $choreClass->getRotationUserIds($choreId);
$currentAssignees = $choreClass->getAssigneeIds($choreId);
$currentAccessUsers = $choreClass->getAccessUserIds($choreId);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="#" class="backLink" onclick="history.back(); return false;">&larr;</a>

<h2>Aufgabe bearbeiten</h2>

<?php foreach ($errors as $error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>
<?php unset($_SESSION['errors']); ?>

<div class="contentcontainer">
    <form action="" method="POST">
        <input type="text" name="name" value="<?= htmlspecialchars($chore->name) ?>" required>

        <label>Wiederholen:
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
            <p>Zuständig (eine oder mehrere Personen):</p>
            <?php foreach ($members as $member): ?>
                <label>
                    <input type="checkbox" name="assignedUsers[]" value="<?= $member->id ?>" <?= in_array($member->id, $currentAssignees) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?>
                </label>
            <?php endforeach; ?>
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
                <option value="assignee" <?= $chore->visibility === 'assignee' ? 'selected' : '' ?>>Nur die Zuständigen</option>
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

        <?= csrfField() ?>
        <button type="submit" name="saveChoreBtn">Speichern</button>
    </form>

    <form action="" method="POST" onsubmit="return confirm('Aufgabe wirklich löschen?')">
        <?= csrfField() ?>
        <button type="submit" name="deleteChoreBtn">Aufgabe löschen</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
