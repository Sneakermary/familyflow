<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Family.php';
require_once __DIR__ . '/../src/classes/Validator.php';

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
if (!$family->isMember($_SESSION['uid'], $famId)) {
    header("Location: /family/SelectFamily.php");
    exit();
}

$errors = $_SESSION['errors'] ?? [];

if (isset($_POST['saveFamilyBtn'])) {
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

if (isset($_POST['deleteFamilyBtn'])) {
    $family->deleteFamily($famId);

    // war das gerade die aktive Familie? dann aus der Session entfernen
    if (isset($_SESSION['fam_id']) && $_SESSION['fam_id'] == $famId) {
        unset($_SESSION['fam_id']);
    }

    header("Location: /family/SelectFamily.php");
    exit();
}

$familyData = $family->findById($famId);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="/family/SelectFamily.php" class="backLink">&larr;</a>

<h2>Familie bearbeiten</h2>

<?php foreach ($errors as $error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>
<?php unset($_SESSION['errors']); ?>

<div class="contentcontainer">
    <form action="" method="POST">
        <input type="text" name="name" value="<?= htmlspecialchars($familyData->name) ?>" required>
        <button type="submit" name="saveFamilyBtn">Speichern</button>
    </form>

    <form action="" method="POST" onsubmit="return confirm('Familie wirklich löschen? Alle Listen, Termine, Rezepte und Mitgliedschaften dieser Familie werden unwiderruflich gelöscht!')">
        <button type="submit" name="deleteFamilyBtn">Familie löschen</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
