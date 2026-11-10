<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Family.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Validator.php';
require_once __DIR__ . '/../src/functions.php';

require_once __DIR__ . '/../src/components/guard.php';

$famId = $_GET['id'] ?? null;

if ($famId === null) {
    header("Location: /family/SelectFamily.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$family = new Family($pdo);

// Sicherheitscheck: gehoert die eingeloggte Person ueberhaupt zu dieser Familie?
$myMembership = $family->isMember($_SESSION['uid'], $famId);
if (!$myMembership) {
    header("Location: /family/SelectFamily.php");
    exit();
}
$isAdmin = $myMembership->role === 'admin';

$errors = $_SESSION['errors'] ?? [];

// Nur die Familienerstellerin/der Familienersteller (admin) darf den Namen aendern
if (isset($_POST['saveFamilyBtn']) && $isAdmin) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /family/EditFamily.php?id=" . $famId);
        exit();
    }

    $name = trim($_POST['name'] ?? '');
    $validator = new Validator;

    if (!$validator->required($name)) {
        $errors['name'] = 'Name ist erforderlich';
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: /family/EditFamily.php?id=" . $famId);
        exit();
    }

    $family->renameFamily($famId, $name);
    header("Location: /family/SelectFamily.php");
    exit();
}

// Nur die Familienerstellerin/der Familienersteller (admin) darf die ganze Familie loeschen
if (isset($_POST['deleteFamilyBtn']) && $isAdmin) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /family/EditFamily.php?id=" . $famId);
        exit();
    }

    $family->deleteFamily($famId);

    // war das gerade die aktive Familie? dann aus der Session entfernen
    if (isset($_SESSION['fam_id']) && $_SESSION['fam_id'] == $famId) {
        unset($_SESSION['fam_id']);
    }

    header("Location: /family/SelectFamily.php");
    exit();
}

// Jede Person darf die Familie verlassen. Ist sie Admin, muss sie vorher eine andere
// Person aus der Familie zum neuen Admin bestimmen, sonst stuende die Familie ohne da.
if (isset($_POST['leaveFamilyBtn'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /family/EditFamily.php?id=" . $famId);
        exit();
    }

    if ($isAdmin) {
        $newAdminId = isset($_POST['newAdminId']) ? (int) $_POST['newAdminId'] : null;
        $newAdminMembership = $newAdminId ? $family->isMember($newAdminId, $famId) : null;

        // Sicherheitscheck: die ausgewaehlte Person muss wirklich (und nicht man selbst) Mitglied dieser Familie sein
        if (!$newAdminMembership || $newAdminId == $_SESSION['uid']) {
            header("Location: /family/EditFamily.php?id=" . $famId);
            exit();
        }

        $family->promoteToAdmin($newAdminId, $famId);
    }

    $family->removeMember($_SESSION['uid'], $famId);

    if (isset($_SESSION['fam_id']) && $_SESSION['fam_id'] == $famId) {
        unset($_SESSION['fam_id']);
    }

    header("Location: /family/SelectFamily.php");
    exit();
}

$familyData = $family->findById($famId);
$user = new User($pdo);
$otherMembers = [];
if ($isAdmin) {
    foreach ($user->findUserByFamilyId($famId) as $member) {
        if ($member->id != $_SESSION['uid']) {
            $otherMembers[] = $member;
        }
    }
}

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="#" class="backLink" onclick="history.back(); return false;">&larr;</a>

<h2>Familie bearbeiten</h2>

<?php foreach ($errors as $error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>
<?php unset($_SESSION['errors']); ?>

<div class="contentcontainer">
    <?php if ($isAdmin): ?>
        <form action="" method="POST">
            <input type="text" name="name" value="<?= htmlspecialchars($familyData->name) ?>" required>
            <?= csrfField() ?>
            <button type="submit" name="saveFamilyBtn">Speichern</button>
        </form>

        <form action="" method="POST" onsubmit="return confirm('Familie wirklich löschen? Alle Listen, Termine, Rezepte und Mitgliedschaften dieser Familie werden unwiderruflich gelöscht!')">
            <?= csrfField() ?>
            <button type="submit" name="deleteFamilyBtn">Familie löschen</button>
        </form>

        <?php if (count($otherMembers) > 0): ?>
            <form action="" method="POST" onsubmit="return confirm('Familie wirklich verlassen? Die ausgewählte Person übernimmt deine Admin-Rolle.')">
                <label>Neuer Admin:
                    <select name="newAdminId" required>
                        <?php foreach ($otherMembers as $member): ?>
                            <option value="<?= $member->id ?>"><?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?= csrfField() ?>
                <button type="submit" name="leaveFamilyBtn">Familie verlassen</button>
            </form>
        <?php else: ?>
            <p>Du bist das einzige Mitglied dieser Familie. Zum Verlassen müsste erst jemand anderes beitreten, sonst bleibt nur "Familie löschen".</p>
        <?php endif; ?>
    <?php else: ?>
        <form action="" method="POST" onsubmit="return confirm('Diese Familie wirklich verlassen?')">
            <?= csrfField() ?>
            <button type="submit" name="leaveFamilyBtn">Familie verlassen</button>
        </form>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
