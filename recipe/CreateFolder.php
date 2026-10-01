<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/RecipeFolder.php';
require_once __DIR__ . '/../src/classes/Validator.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$errors = $_SESSION['errors'] ?? [];

if (isset($_POST['createFolderBtn'])) {
    $name = trim($_POST['name'] ?? '');
    $validator = new Validator;

    if (!$validator->required($name)) {
        $errors['name'] = 'Name ist erforderlich';
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: /recipe/CreateFolder.php");
        exit();
    }

    $db = new Database;
    $pdo = $db->connect();
    $folderClass = new RecipeFolder($pdo);
    $folderClass->createFolder($_SESSION['fam_id'], $name);

    header("Location: /recipe/Recipes.php");
    exit();
}

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="/recipe/Recipes.php" class="backLink">&larr;</a>

<h2>Neuer Ordner</h2>

<?php foreach ($errors as $error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>
<?php unset($_SESSION['errors']); ?>

<div class="contentcontainer">
    <form action="" method="POST">
        <input type="text" name="name" placeholder="Ordnername (z.B. Süßes, Asia, Backen)" required>
        <button type="submit" name="createFolderBtn">Ordner erstellen</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
