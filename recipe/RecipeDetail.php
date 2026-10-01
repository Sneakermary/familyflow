<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Recipe.php';

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

// Sicherheitscheck 1: existiert das Rezept ueberhaupt, gehoert es zur eigenen Familie?
if (!$r || $r->fam_id != $_SESSION['fam_id']) {
    header("Location: /recipe/Recipes.php");
    exit();
}

// Sicherheitscheck 2: darf diese Person es ueberhaupt sehen? (gleiche Logik wie beim Kalender)
$darfSehen = $r->shared_with === null || $r->shared_with == $_SESSION['uid'] || $r->owner_id == $_SESSION['uid'];

if (!$darfSehen) {
    header("Location: /recipe/Recipes.php");
    exit();
}

$steps = $recipe->getStepsForRecipe($recipeId);
$backUrl = $r->folder_id ? "/recipe/Folder.php?id=" . $r->folder_id : "/recipe/Recipes.php";

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="<?= $backUrl ?>" class="backLink">&larr;</a>

<h2><?= htmlspecialchars($r->title) ?></h2>

<p><a href="/recipe/EditRecipe.php?id=<?= $r->id ?>">Bearbeiten</a></p>

<div class="contentcontainer">
    <?php if ($r->photo): ?>
        <img src="/uploads/recipes/<?= htmlspecialchars($r->photo) ?>" alt="<?= htmlspecialchars($r->title) ?>" class="recipePhoto">
    <?php endif; ?>

    <?php if ($r->ingredients): ?>
        <h3>Zutaten</h3>
        <p><?= nl2br(htmlspecialchars($r->ingredients)) ?></p>
    <?php endif; ?>

    <?php if (!empty($steps)): ?>
        <h3>Zubereitung</h3>
        <ol>
            <?php foreach ($steps as $step): ?>
                <li><?= htmlspecialchars($step->instruction) ?></li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
