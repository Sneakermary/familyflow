<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Recipe.php';
require_once __DIR__ . '/../src/classes/RecipeFolder.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$folderId = $_GET['id'] ?? null;

if ($folderId === null) {
    header("Location: /recipe/Recipes.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$folderClass = new RecipeFolder($pdo);
$recipe = new Recipe($pdo);

$folder = $folderClass->getFolderById($folderId);

// Sicherheitscheck: gehoert der Ordner ueberhaupt zur eigenen Familie?
if (!$folder || $folder->fam_id != $_SESSION['fam_id']) {
    header("Location: /recipe/Recipes.php");
    exit();
}

$recipes = $recipe->getRecipesForUserInFolder($_SESSION['uid'], $_SESSION['fam_id'], $folderId);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="/recipe/Recipes.php" class="backLink">&larr;</a>

<h2><?= htmlspecialchars($folder->name) ?></h2>

<div class="tiles">
<?php foreach ($recipes as $r): ?>
    <div class="tile" data-href="/recipe/RecipeDetail.php?id=<?= $r->id ?>">
        <div class="tile_icon">🍽️</div>
        <div class="tile_title"><?= htmlspecialchars($r->title) ?></div>
    </div>
<?php endforeach; ?>
</div>

<p><a href="/recipe/CreateRecipe.php">+ Neues Rezept</a></p>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
