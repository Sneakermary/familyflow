<?php
require_once __DIR__ .'/src/classes/Database.php';
require_once __DIR__ .'/src/classes/User.php';
require_once __DIR__ .'/src/classes/Family.php';

// guard.php: session_start() + Login-Pflicht (kein HTML)
require_once __DIR__ . '/src/components/guard.php';

// Zweiter Check nur hier noetig (nicht alle Seiten brauchen ihn), auch noch vor jedem HTML
if(!isset($_SESSION['fam_id'])) {
    header("Location: CreateFamily.php");
    exit();
}

$errors = $_SESSION['errors'] ?? [];

// POST-Verarbeitung ZUERST (kann noch redirecten), erst danach kommt HTML
if (isset($_POST['addMemberBtn'])) {
    require_once __DIR__. '/src/classes/Validator.php';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['pw'] ?? '';
    $pwConfirm = $_POST['pwConfirm'] ?? '';
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');

    $errors = [];
    $validator = new Validator;

    if(!$validator->required($email)) {
        $errors['email'] = 'E-Mail ist erforderlich!';
    }
    elseif(!$validator->email($email)){
        $errors['email'] = 'Bitte gib eine gültige E-Mail ein!';
    }

    if(!$validator->required($password)){
        $errors['password'] = 'Passwort ist erforderlich!';
    } 
    elseif(!$validator->minlength($password, 8)){
        $errors['password'] = 'Passwort muss mindestens 8 Zeichen lang sein.';
    }

    if(!$validator->required($pwConfirm)){
        $errors['pwConfirm'] = 'Bitte Passwort wiederholen!';
    }
    elseif(!$validator->matches($password, $pwConfirm)) {
        $errors['pwConfirm'] = 'Die Passwörter stimmen nicht überein!';
    }

    if(!$validator->required($firstname)){
        $errors['firstname'] = 'Vorname ist erforderlich!';
    }
    
    if(!$validator->required($lastname)){
        $errors['lastname'] = 'Nachname ist erforderlich!';
    }

    $db = new Database;
    $pdo = $db->connect();

    $user = new User($pdo);
    if(empty($errors) && $user->findByEmail($email)){
        $errors['email'] = 'Diese E-Mail ist bereits registriert!';
    }

    if(!empty($errors)){
        $_SESSION['errors'] = $errors;
        header("Location: AddMember.php");
        exit();
    }

    $pw_hash = password_hash($password, PASSWORD_DEFAULT);


    // Neue User-Id auffangen (createUser gibt sie jetzt zurück)
    $newUserId = $user->createUser($firstname, $lastname, $email, $pw_hash);

    // Neuen User als Mitglied der aktuellen Familie eintragen
    $family = new Family($pdo);
    $family->addMember($newUserId, $_SESSION['fam_id']);

    header("Location: Dashboard.php");
    exit();
}

// Ab hier nur noch Anzeige, keine Redirects mehr moeglich
include_once __DIR__ . '/src/components/head.php';
include_once __DIR__ . '/src/components/navbar.php';
?>

<div class="contentcontainer">
    <form action="" class="frontform" method="POST">
        <h3>Familien Mitglied hinzufügen</h3>

        <input type="text" name="firstname" placeholder="Vorname" required>
        <input type="text" name="lastname" placeholder="Nachname" required>
        <input type="email" name="email" placeholder="E-Mail" required>
        <input type="password" name="pw" placeholder="Passwort" minlength="8" required>
        <input type="password" name="pwConfirm" placeholder="Passwort bestätigen" required>
        <button type="submit" name="addMemberBtn">Hinzufügen</button>
    </form>
</div>

<?php
foreach($errors as $error){
    echo '<p>' . $error . '</p>';
}
unset($_SESSION['errors']);
?>

<?php include_once __DIR__ . '/src/components/footer.php'; ?>