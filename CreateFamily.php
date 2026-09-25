<?php
require_once __DIR__ . '/src/classes/FamilyList.php';
require_once __DIR__ . '/src/classes/Database.php';
require_once __DIR__ . '/src/classes/Family.php';
require_once __DIR__ . '/src/classes/Validator.php';
// guard.php: session_start() + Login-Pflicht (kein HTML)
require_once __DIR__ . '/src/components/guard.php';

$errors = $_SESSION['errors'] ?? [];

// POST-Verarbeitung ZUERST (kann noch redirecten), erst danach kommt HTML
if (isset($_POST['familynameBtn'])) {
    $familyname = trim($_POST['familyname'] ?? '');

    $errors = [];
    $validator = new Validator;

    if (!$validator->required($familyname)) {
        $errors['familyname'] = 'Familienname ist erforderlich!';
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: CreateFamily.php");
        exit();
    }

    $db = new Database;
    $pdo = $db->connect();

    $family = new Family($pdo);

    // Familie anlegen; das Ergebnis (neue Familien-Id) wird in $famId gespeichert
    $famId = $family->createFamily($familyname);

    // addMember traegt die Person in die Zwischentabelle family_members ein. Reihenfolge: erst der User, dann die Familie
    $family->addMember($_SESSION['uid'], $famId);
    $lists = new FamilyList($pdo);

    $einkaufenId = $lists->createList($famId, $_SESSION['uid'], 'Einkaufen');
    $lists->addAccess($einkaufenId, $_SESSION['uid']);

    $todoId = $lists->createList($famId, $_SESSION['uid'], 'To-Dos');
    $lists->addAccess($todoId, $_SESSION['uid']);


    // Ab jetzt ist die neue Familie die aktuell gewählte Familie
    $_SESSION['fam_id'] = $famId;

    header("Location: Dashboard.php");
    exit();
}

// Ab hier nur noch Anzeige, keine Redirects mehr moeglich
include_once __DIR__ . '/src/components/head.php';
include_once __DIR__ . '/src/components/navbar.php';
?>

<h3>Erstelle eine Familie</h3>

<?php
foreach ($errors as $error) {
    echo '<p>' . $error . '</p>';
}
unset($_SESSION['errors']);
?>

<div class="CreateFamilyContainer">
    <form action="" method="POST">
        <input type="text" name="familyname" placeholder="Familie" required>
        <button type="submit" name="familynameBtn">Familie erstellen</button>
    </form>
</div>

<?php include_once __DIR__ . '/src/components/footer.php'; ?>