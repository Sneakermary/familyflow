<?php
require_once __DIR__ . '/src/classes/Database.php';
require_once __DIR__ . '/src/classes/User.php';
require_once __DIR__ . '/src/classes/Family.php';
require_once __DIR__ . '/src/classes/Validator.php';

session_start();
$errors = $_SESSION['errors'] ?? [];

if (!isset($_SESSION['uid'])) {
    header("Location: UserLogin.php");
    exit();
}

if (isset($_SESSION['fam_id'])) {
    header("Location: Dashboard.php");
    exit();
}
?>

<h3>Erstelle eine Familie</h3>

<div class="CreateFamilyContainer">
    <form action="" method="POST">
        <input type="text" name="familyname" placeholder="Familie" required>
        <button type="submit" name="familynameBtn">Familie erstellen</button>
    </form>
</div>

<?php

foreach ($errors as $error) {
    echo '<p>' . $error . '</p>';
}
unset($_SESSION['errors']);

if (isset($_POST['familynameBtn'])) {
    $familyname = trim($_POST['familyname'] ?? '');

    $errors = [];
    $validator = new Validator;

    if (!$validator->required($familyname)) {
        $errors['familyname'] = 'Familienname ist erforderlich!';
    }
}
?>