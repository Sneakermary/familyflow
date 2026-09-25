<?php
require_once __DIR__ . '/src/classes/Database.php';
require_once __DIR__ . '/src/classes/User.php';
session_start();

$errors = $_SESSION['errors'] ?? [];

// POST-Verarbeitung ZUERST (kann noch redirecten), erst danach kommt HTML
if (isset($_POST['registerBtn'])) {
    require_once __DIR__. '/src/classes/Validator.php';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['pw'] ?? '';
    $pwConfirm = $_POST['pwConfirm'] ?? '';
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');

    $validator = new Validator;
    $errors = $validator->validateRegistrationFields($firstname, $lastname, $email, $password, $pwConfirm);


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

// Ab hier nur noch Anzeige, keine Redirects mehr moeglich
include_once __DIR__ . '/src/components/head.php';

foreach($errors as $error){
    echo '<p>' . $error . '</p>';
}
unset($_SESSION['errors']);
?>

<div class="contentcontainer">
    <form action="" class="frontform" method="POST">
        <h3>Registrieren</h3>

        <input type="text" name="firstname" placeholder="Vorname" required>
        <input type="text" name="lastname" placeholder="Nachname" required>
        <input type="email" name="email" placeholder="E-Mail" required>
        <input type="password" name="pw" placeholder="Passwort" minlength="8" required>
        <input type="password" name="pwConfirm" placeholder="Passwort bestätigen" required>
        <button type="submit" name="registerBtn">Registrieren</button>
    </form>
</div>

<?php include_once __DIR__ . '/src/components/footer.php'; ?>