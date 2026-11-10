<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Recipe.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$recipe = new Recipe($pdo);

// Alphabetisch sortiert (ORDER BY title ASC steckt schon in getRecipesForUser)
$recipes = $recipe->getRecipesForUser($_SESSION['uid'], $_SESSION['fam_id']);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="#" class="backLink" onclick="history.back(); return false;">&larr;</a>

<h2>Alle Rezepte</h2>

<div class="tiles">
<?php foreach ($recipes as $r): ?>
    <div class="tile" data-href="/recipe/RecipeDetail.php?id=<?= $r->id ?>">
        <div class="tile_icon">🍽️</div>
        <div class="tile_title"><?= htmlspecialchars($r->title) ?></div>
    </div>
<?php endforeach; ?>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
