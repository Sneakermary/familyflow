<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Recipe.php';
require_once __DIR__ . '/../src/classes/RecipeFolder.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Validator.php';
require_once __DIR__ . '/../src/functions.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$recipeId = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($recipeId === null) {
    header("Location: /recipe/Recipes.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$recipe = new Recipe($pdo);

$r = $recipe->getRecipeById($recipeId);

if (!$r || $r->fam_id != $_SESSION['fam_id']) {
    header("Location: /recipe/Recipes.php");
    exit();
}

if (!$recipe->isVisibleToUser($recipeId, $_SESSION['uid'])) {
    header("Location: /recipe/Recipes.php");
    exit();
}

$errors = $_SESSION['errors'] ?? [];

if (isset($_POST['editRecipeBtn'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /recipe/EditRecipe.php?id=" . $recipeId);
        exit();
    }

    $title = trim($_POST['title'] ?? '');
    $folderId = $_POST['folder_id'] !== '' ? (int) $_POST['folder_id'] : null;
    $sharedAll = isset($_POST['shared_all']);
    $removePhoto = isset($_POST['removePhoto']);

    $validator = new Validator;

    if (!$validator->required($title)) {
        $errors['title'] = 'Rezeptname ist erforderlich';
    }

    // Foto: neues hochladen, behalten, oder entfernen - unabhaengig von Zutaten/Schritten
    $photoError = null;
    $newPhoto = handleImageUpload('photo', 'recipes', 'recipe', 1600, $photoError);
    if ($photoError !== null) {
        $errors['photo'] = $photoError;
    }

    $photoFilename = $r->photo;
    if ($newPhoto !== null) {
        $photoFilename = $newPhoto;
    } elseif ($removePhoto) {
        $photoFilename = null;
    }


    // Zutaten/Schritte: immer aus dem Formular lesen, unabhaengig vom Foto
    $ingredients = trim($_POST['ingredients'] ?? '');
    $ingredients = $ingredients !== '' ? $ingredients : null;

    $steps = [];
    $rawSteps = $_POST['steps'] ?? [];
    foreach ($rawSteps as $step) {
        $step = trim($step);
        if ($step !== '') {
            $steps[] = $step;
        }
    }

    if (!empty($errors)) {
        // Bei Fehlern wurde evtl. schon ein neues Foto gespeichert -> wieder weg damit
        deleteUploadedImage('recipes', $newPhoto);
        $_SESSION['errors'] = $errors;
        header("Location: /recipe/EditRecipe.php?id=" . $recipeId);
        exit();
    }

    $user = new User($pdo);
    $members = $user->findUserByFamilyId($_SESSION['fam_id']);
    // Leeres Array = fuer alle sichtbar, sonst die angekreuzten Personen + man selbst
    $sharedWithIds = sharedWithFromForm($sharedAll, $_POST['shared_with'] ?? [], $members, $_SESSION['uid']);

    $recipe->updateRecipe($recipeId, $folderId, $title, $ingredients, $photoFilename);
    $recipe->setSharedWith($recipeId, $sharedWithIds);

    // Altes Foto loeschen, wenn es ersetzt oder entfernt wurde
    if ($photoFilename !== $r->photo) {
        deleteUploadedImage('recipes', $r->photo);
    }

    $recipe->deleteStepsForRecipe($recipeId);
    foreach ($steps as $i => $step) {
        $recipe->addStep($recipeId, $i + 1, $step);
    }

    header("Location: /recipe/RecipeDetail.php?id=" . $recipeId);
    exit();
}

$folderClass = new RecipeFolder($pdo);
$folders = $folderClass->getFoldersForFamily($_SESSION['fam_id']);
$user = new User($pdo);
$members = $user->findUserByFamilyId($_SESSION['fam_id']);
$steps = $recipe->getStepsForRecipe($recipeId);
$currentSharedWith = $recipe->getSharedWithIds($recipeId);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="#" class="backLink" onclick="history.back(); return false;">&larr;</a>

<h2>Rezept bearbeiten</h2>

<?php foreach ($errors as $error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>
<?php unset($_SESSION['errors']); ?>

<div class="contentcontainer">
    <form action="" method="POST" enctype="multipart/form-data">
        <input type="text" name="title" placeholder="Rezeptname" value="<?= htmlspecialchars($r->title) ?>" required>

        <label>Ordner:
            <select name="folder_id">
                <option value="">Kein Ordner</option>
                <?php foreach ($folders as $f): ?>
                    <option value="<?= $f->id ?>" <?= $r->folder_id == $f->id ? 'selected' : '' ?>><?= htmlspecialchars($f->name) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <?php if ($r->photo): ?>
            <img src="/uploads/recipes/<?= htmlspecialchars($r->photo) ?>" alt="Aktuelles Foto" class="recipePhotoPreview">
            <label><input type="checkbox" name="removePhoto"> Foto entfernen</label>
        <?php endif; ?>
        <label>Foto <?= $r->photo ? 'ersetzen' : 'hinzufügen' ?> (optional):
            <input type="file" name="photo" accept="image/*">
        </label>

        <textarea name="ingredients" placeholder="Zutaten (optional)"><?= htmlspecialchars($r->ingredients ?? '') ?></textarea>

        <div id="stepsContainer">
            <?php if (empty($steps)): ?>
                <input type="text" name="steps[]" placeholder="Schritt 1">
            <?php else: ?>
                <?php foreach ($steps as $i => $step): ?>
                    <input type="text" name="steps[]" placeholder="Schritt <?= $i + 1 ?>" value="<?= htmlspecialchars($step->instruction) ?>">
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button type="button" id="addStepBtn">+ Schritt hinzufügen</button>

        <!-- Gleiches Muster wie bei Terminen: niemand angekreuzt + "Für alle" aus = privat -->
        <label>
            <input type="checkbox" name="shared_all" id="sharedAllCheckbox" <?= empty($currentSharedWith) ? 'checked' : '' ?>>
            Für alle sichtbar
        </label>
        <div id="sharedWithFields" class="<?= empty($currentSharedWith) ? 'hidden' : '' ?>">
            <p>Wer soll es sehen können?</p>
            <?php foreach ($members as $member): ?>
                <?php if ($member->id != $_SESSION['uid']): ?>
                    <label>
                        <input type="checkbox" name="shared_with[]" value="<?= $member->id ?>" <?= in_array($member->id, $currentSharedWith) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?= csrfField() ?>
        <button type="submit" name="editRecipeBtn">Speichern</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
