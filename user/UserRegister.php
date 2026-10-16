<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/functions.php';
session_start();

$errors = $_SESSION['errors'] ?? [];

// POST-Verarbeitung ZUERST (kann noch redirecten), erst danach kommt HTML
if (isset($_POST['registerBtn'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /user/UserRegister.php");
        exit();
    }

    require_once __DIR__. '/../src/classes/Validator.php';
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
        // Passwoerter bewusst nicht gespeichert, nur die unkritischen Felder
        $_SESSION['oldValues'] = ['firstname' => $firstname, 'lastname' => $lastname, 'email' => $email];
        header("Location: /user/UserRegister.php");
        exit();
    }

    $pw_hash = password_hash($password, PASSWORD_DEFAULT);

    $user->createUser($firstname, $lastname, $email, $pw_hash);

    header("Location: /user/UserLogin.php");
    exit();
}

// Ab hier nur noch Anzeige, keine Redirects mehr moeglich
include_once __DIR__ . '/../src/components/head.php';

foreach($errors as $error){
    echo '<p>' . $error . '</p>';
}
unset($_SESSION['errors']);

$oldValues = $_SESSION['oldValues'] ?? [];
unset($_SESSION['oldValues']);
?>

<div class="contentcontainer">
    <form action="" class="frontform" method="POST">
        <h3>Registrieren</h3>

        <input type="text" name="firstname" placeholder="Vorname" value="<?= htmlspecialchars($oldValues['firstname'] ?? '') ?>" required>
        <input type="text" name="lastname" placeholder="Nachname" value="<?= htmlspecialchars($oldValues['lastname'] ?? '') ?>" required>
        <input type="email" name="email" placeholder="E-Mail" value="<?= htmlspecialchars($oldValues['email'] ?? '') ?>" required>
        <input type="password" name="pw" placeholder="Passwort" minlength="8" required>
        <input type="password" name="pwConfirm" placeholder="Passwort bestätigen" required>
        <?= csrfField() ?>
        <button type="submit" name="registerBtn">Registrieren</button>
    </form>
    <p>Schon ein Konto? <a href="/user/UserLogin.php">Hier einloggen</a></p>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>