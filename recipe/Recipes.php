<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Recipe.php';
require_once __DIR__ . '/../src/classes/RecipeFolder.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$folderClass = new RecipeFolder($pdo);
$recipe = new Recipe($pdo);

$folders = $folderClass->getFoldersForFamily($_SESSION['fam_id']);
$recipesWithoutFolder = $recipe->getRecipesForUserWithoutFolder($_SESSION['uid'], $_SESSION['fam_id']);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="/Dashboard.php" class="backLink">&larr;</a>

<h2>Rezepte</h2>

<div class="tiles">
<?php foreach ($folders as $folder): ?>
    <div class="tile" data-href="/recipe/Folder.php?id=<?= $folder->id ?>">
        <div class="tile_icon">📁</div>
        <div class="tile_title"><?= htmlspecialchars($folder->name) ?></div>
        <div class="tile_subtext">
            <?php
                $folderRecipes = $recipe->getRecipesForUserInFolder($_SESSION['uid'], $_SESSION['fam_id'], $folder->id);
                echo count($folderRecipes) > 0 ? count($folderRecipes) . ' Rezepte' : 'Leer';
            ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<p><a href="/recipe/CreateFolder.php">+ Neuer Ordner</a></p>

<h3>Ohne Ordner</h3>

<div class="tiles">
<?php foreach ($recipesWithoutFolder as $r): ?>
    <div class="tile" data-href="/recipe/RecipeDetail.php?id=<?= $r->id ?>">
        <div class="tile_icon">🍽️</div>
        <div class="tile_title"><?= htmlspecialchars($r->title) ?></div>
    </div>
<?php endforeach; ?>
</div>

<p><a href="/recipe/CreateRecipe.php">+ Neues Rezept</a></p>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
