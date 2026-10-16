<?php

session_start();
require_once __DIR__ . '/../src/functions.php';
$errors = $_SESSION['errors'] ?? [];


if (isset($_POST['loginBtn'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /user/UserLogin.php");
        exit();
    }

    require_once __DIR__ . '/../src/classes/Database.php';
    require_once __DIR__ . '/../src/classes/User.php';

    $email = trim($_POST['email'] ?? '');
    $pw = $_POST['pw'] ?? '';

    $db = new Database;
    $pdo = $db->connect();

    $user = new User($pdo);

    $found = $user->findByEmail($email);

    if ($found && password_verify($pw, $found->pw_hash)) {
        // Neue Session-Id nach dem Login, damit eine evtl. vorher bekannte Session-Id
        // (z.B. aus einer URL oder einem offenen WLAN) danach nicht mehr gueltig ist.
        session_regenerate_id(true);
        $_SESSION['uid'] = $found->id;
        header("Location: /family/SelectFamily.php");
        exit();
    }
    else {
       $_SESSION['errors'] = ['login' => 'E-Mail oder Passwort ist falsch!'];
       $_SESSION['oldEmail'] = $email;
       header("Location: /user/UserLogin.php");
       exit();
    }

}

$oldEmail = $_SESSION['oldEmail'] ?? '';
unset($_SESSION['oldEmail']);

include_once __DIR__ . '/../src/components/head.php';

foreach($errors as $error) {
    echo '<p>' . $error . '</p>';
}
unset($_SESSION['errors']);

?>
<div class="contentcontainer">
    <form action="" class="frontform" method="POST">
        <h3>Login</h3>

        <input type="email" name="email" placeholder="E-Mail" value="<?= htmlspecialchars($oldEmail) ?>" required>
        <input type="password" name="pw" placeholder="Passwort" required>
        <?= csrfField() ?>
        <button type="submit" name="loginBtn">Login</button>
    </form>
    <p>Noch kein Konto? <a href="/user/UserRegister.php">Hier registrieren</a></p>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
