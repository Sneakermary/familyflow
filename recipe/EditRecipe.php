<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Recipe.php';
require_once __DIR__ . '/../src/classes/RecipeFolder.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Validator.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$recipeId = $_GET['id'] ?? null;

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

$darfSehen = $r->shared_with === null || $r->shared_with == $_SESSION['uid'] || $r->owner_id == $_SESSION['uid'];

if (!$darfSehen) {
    header("Location: /recipe/Recipes.php");
    exit();
}

$errors = $_SESSION['errors'] ?? [];

if (isset($_POST['editRecipeBtn'])) {
    $title = trim($_POST['title'] ?? '');
    $mode = $_POST['mode'] ?? 'photo';
    $folderId = $_POST['folder_id'] !== '' ? (int) $_POST['folder_id'] : null;
    $sharedWith = $_POST['shared_with'] ?? '';

    $validator = new Validator;

    if (!$validator->required($title)) {
        $errors['title'] = 'Rezeptname ist erforderlich';
    }

    $photoFilename = null;
    $ingredients = null;
    $steps = [];

    if ($mode === 'photo') {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['photo']['tmp_name']);

            $maxSize = 5 * 1024 * 1024; // 5 MB

            if (!isset($allowedTypes[$mimeType])) {
                $errors['photo'] = 'Nur JPG, PNG oder WebP erlaubt';
            } elseif ($_FILES['photo']['size'] > $maxSize) {
                $errors['photo'] = 'Foto ist zu groß (max. 5 MB)';
            } else {
                $extension = $allowedTypes[$mimeType];
                $photoFilename = uniqid('recipe_', true) . '.' . $extension;
                $targetPath = __DIR__ . '/../uploads/recipes/' . $photoFilename;
                move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath);
            }
        } else {
            // kein neues Foto hochgeladen -> das alte behalten
            $photoFilename = $r->photo;
        }
    } else {
        $ingredients = trim($_POST['ingredients'] ?? '');
        $rawSteps = $_POST['steps'] ?? [];
        foreach ($rawSteps as $step) {
            $step = trim($step);
            if ($step !== '') {
                $steps[] = $step;
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: /recipe/EditRecipe.php?id=" . $recipeId);
        exit();
    }

    $sharedWithId = $sharedWith !== '' ? (int) $sharedWith : null;

    $recipe->updateRecipe($recipeId, $folderId, $title, $ingredients, $photoFilename, $sharedWithId);

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

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="/recipe/RecipeDetail.php?id=<?= $recipeId ?>" class="backLink">&larr;</a>

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

        <div>
            <label><input type="radio" name="mode" value="photo" id="modePhoto" <?= $r->photo ? 'checked' : '' ?>> Mit Foto</label>
            <label><input type="radio" name="mode" value="written" id="modeWritten" <?= !$r->photo ? 'checked' : '' ?>> Geschrieben</label>
        </div>

        <div id="photoFields" class="<?= $r->photo ? '' : 'hidden' ?>">
            <?php if ($r->photo): ?>
                <img src="/uploads/recipes/<?= htmlspecialchars($r->photo) ?>" alt="Aktuelles Foto" class="recipePhotoPreview">
                <p>Neues Foto hochladen, um das aktuelle zu ersetzen (optional):</p>
            <?php endif; ?>
            <input type="file" name="photo" accept="image/*">
        </div>

        <div id="writtenFields" class="<?= $r->photo ? 'hidden' : '' ?>">
            <textarea name="ingredients" placeholder="Zutaten"><?= htmlspecialchars($r->ingredients ?? '') ?></textarea>

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
        </div>

        <label>Teilen mit:
            <select name="shared_with">
                <option value="">Alle</option>
                <option value="<?= $_SESSION['uid'] ?>" <?= $r->shared_with == $_SESSION['uid'] ? 'selected' : '' ?>>Nur ich (privat)</option>
                <?php foreach ($members as $member): ?>
                    <?php if ($member->id != $_SESSION['uid']): ?>
                        <option value="<?= $member->id ?>" <?= $r->shared_with == $member->id ? 'selected' : '' ?>><?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </label>

        <button type="submit" name="editRecipeBtn">Speichern</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
