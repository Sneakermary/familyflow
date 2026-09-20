<?php
session_start();
$errors = $_SESSION['errors'] ?? [];
?>

<div class="contentcontainer">
    <form action="" class="frontform" method="POST">
        <h3>Login</h3>

        <input type="email" name="email" placeholder="E-Mail" required>
        <input type="password" name="pw" placeholder="Passwort" required>
        <button type="submit" name="loginBtn">Login</button>
    </form>
    <p>Noch kein Konto? <a href="UserRegister.php">Hier registrieren</a></p>
</div>


<?php 

foreach($errors as $error) {
    echo '<p>' . $error . '</p>';
}
unset($_SESSION['errors']);

if (isset($_POST['loginBtn'])) {
    require_once __DIR__ . '/src/classes/Database.php';
    require_once __DIR__ . '/src/classes/User.php';

    $email = trim($_POST['email'] ?? '');
    $pw = $_POST['pw'] ?? '';

    $db = new Database;
    $pdo = $db->connect();

    $user = new User($pdo);

    $found = $user->findByEmail($email);

    if ($found && password_verify($pw, $found->pw_hash)) {
        $_SESSION['uid'] = $found->id;
        $_SESSION['fam_id'] = $found->fam_id;
        header("Location: Dashboard.php");
        exit();
    }
    else {
       $_SESSION['errors'] = ['login' => 'E-Mail oder Passwort ist falsch!'];
       header("Location: UserLogin.php");
       exit();
    }
    
}
?>