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

<a href="#" class="backLink" onclick="history.back(); return false;">&larr;</a>

<div class="listHeader">
    <h2>Rezepte</h2>
    <div class="listHeaderActions">
        <span id="recipeSearchBtn" class="searchIconBtn" aria-label="Rezepte durchsuchen">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </span>
        <input type="text" id="recipeSearchInput" class="hidden" placeholder="Rezept suchen...">
        <span id="listMenuBtn" class="dots">⋮</span>
    </div>
    <div id="listMenuList" class="hidden">
        <a href="/recipe/CreateRecipe.php">Rezept erstellen</a>
        <a href="/recipe/CreateFolder.php">Ordner erstellen</a>
        <a href="/recipe/AllRecipes.php">Alle Rezepte anzeigen</a>
    </div>
</div>

<div class="tiles" id="recipeTiles">
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

    <?php foreach ($recipesWithoutFolder as $r): ?>
        <div class="tile" data-href="/recipe/RecipeDetail.php?id=<?= $r->id ?>">
            <div class="tile_icon">🍽️</div>
            <div class="tile_title"><?= htmlspecialchars($r->title) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>