<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Recipe.php';
require_once __DIR__ . '/../src/classes/RecipeFolder.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Validator.php';
require_once __DIR__ . '/../src/classes/Notification.php';
require_once __DIR__ . '/../src/functions.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$errors = $_SESSION['errors'] ?? [];

if (isset($_POST['createRecipeBtn'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /recipe/CreateRecipe.php");
        exit();
    }

    $title = trim($_POST['title'] ?? '');
    $folderId = $_POST['folder_id'] !== '' ? (int) $_POST['folder_id'] : null;
    $sharedAll = isset($_POST['shared_all']);

    $validator = new Validator;

    if (!$validator->required($title)) {
        $errors['title'] = 'Rezeptname ist erforderlich';
    }

    // Foto und Zutaten/Schritte sind unabhaengig voneinander, beides ist optional
    $photoError = null;
    $photoFilename = handleImageUpload('photo', 'recipes', 'recipe', 1600, $photoError);
    if ($photoError !== null) {
        $errors['photo'] = $photoError;
    }


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
        // Bei Fehlern wurde evtl. schon ein Foto gespeichert -> wieder weg damit
        deleteUploadedImage('recipes', $photoFilename);
        $_SESSION['errors'] = $errors;
        header("Location: /recipe/CreateRecipe.php");
        exit();
    }


    $db = new Database;
    $pdo = $db->connect();
    $recipe = new Recipe($pdo);
    $user = new User($pdo);

    $members = $user->findUserByFamilyId($_SESSION['fam_id']);
    // Leeres Array = fuer alle sichtbar, sonst die angekreuzten Personen + man selbst
    $sharedWithIds = sharedWithFromForm($sharedAll, $_POST['shared_with'] ?? [], $members, $_SESSION['uid']);

    $recipeId = $recipe->createRecipe($_SESSION['fam_id'], $_SESSION['uid'], $folderId, $title, $ingredients, $photoFilename);
    $recipe->setSharedWith($recipeId, $sharedWithIds);

    foreach ($steps as $i => $step) {
        $recipe->addStep($recipeId, $i + 1, $step);
    }

    // Benachrichtigung an alle, die das Rezept sehen duerfen (ausser einem selbst)
    $notificationClass = new Notification($pdo);
    $fromUser = $user->findById($_SESSION['uid']);
    $recipeLink = '/recipe/RecipeDetail.php?id=' . $recipeId;

    $notifyIds = empty($sharedWithIds) ? array_column($members, 'id') : $sharedWithIds;
    foreach ($notifyIds as $notifyId) {
        if ($notifyId != $_SESSION['uid']) {
            $notificationClass->notify(
                $notifyId,
                $fromUser->firstname . ' ' . $fromUser->lastname . ' hat ein Rezept erstellt: ' . $title,
                $recipeLink
            );
        }
    }

    header("Location: /recipe/RecipeDetail.php?id=" . $recipeId);
    exit();
}

$db = new Database;
$pdo = $db->connect();
$folderClass = new RecipeFolder($pdo);
$folders = $folderClass->getFoldersForFamily($_SESSION['fam_id']);
$user = new User($pdo);
$members = $user->findUserByFamilyId($_SESSION['fam_id']);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="#" class="backLink" onclick="history.back(); return false;">&larr;</a>

<h2>Neues Rezept</h2>

<?php foreach ($errors as $error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>
<?php unset($_SESSION['errors']); ?>

<div class="contentcontainer">
    <form action="" method="POST" enctype="multipart/form-data">
        <input type="text" name="title" placeholder="Rezeptname" required>

        <label>Ordner:
            <select name="folder_id">
                <option value="">Kein Ordner</option>
                <?php foreach ($folders as $f): ?>
                    <option value="<?= $f->id ?>"><?= htmlspecialchars($f->name) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Foto hinzufügen (optional):
            <input type="file" name="photo" accept="image/*">
        </label>

        <textarea name="ingredients" placeholder="Zutaten (optional)"></textarea>

        <div id="stepsContainer">
            <input type="text" name="steps[]" placeholder="Schritt 1">
        </div>
        <button type="button" id="addStepBtn">+ Schritt hinzufügen</button>

        <!-- Gleiches Muster wie bei Terminen: niemand angekreuzt + "Für alle" aus = privat -->
        <label>
            <input type="checkbox" name="shared_all" id="sharedAllCheckbox" checked>
            Für alle sichtbar
        </label>
        <div id="sharedWithFields" class="hidden">
            <p>Wer soll es sehen können?</p>
            <?php foreach ($members as $member): ?>
                <?php if ($member->id != $_SESSION['uid']): ?>
                    <label>
                        <input type="checkbox" name="shared_with[]" value="<?= $member->id ?>">
                        <?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?= csrfField() ?>
        <button type="submit" name="createRecipeBtn">Rezept erstellen</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
