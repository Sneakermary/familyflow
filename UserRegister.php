<?php
require_once __DIR__ . '/src/classes/Database.php';
require_once __DIR__ . '/src/classes/User.php';
session_start();

$errors = $_SESSION['errors'] ?? [];
?>

<div class="contentcontainer">
    <form action="" class="frontform" method="POST" novalidate>
        <h3>Registrieren</h3>

        <input type="text" name="firstname" placeholder="Vorname" required>
        <input type="text" name="lastname" placeholder="Nachname" required>
        <input type="email" name="email" placeholder="E-Mail" required>
        <input type="password" name="pw" placeholder="Passwort" minlength="8">
        <input type="password" name="pwConfirm" placeholder="Passwort bestätigen" required>
        <button type="submit" name="registerBtn">Registrieren</button>
    </form>
</div>


<?php

foreach($errors as $error){
    echo '<p>' . $error . '</p>';
}
unset($_SESSION['errors']);

if (isset($_POST['registerBtn'])) {
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
        header("Location: UserRegister.php");
        exit();
    }

    $pw_hash = password_hash($password, PASSWORD_DEFAULT);

    $user->createUser($firstname, $lastname, $email, $pw_hash);

    header("Location: UserLogin.php");
    exit();
}
?>