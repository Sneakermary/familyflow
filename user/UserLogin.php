<?php

require_once __DIR__ . '/../src/components/session.php';
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
        // E-Mail noch nicht bestaetigt -> nicht einloggen. Die Meldung kommt erst NACH der
        // Passwortpruefung, damit niemand so herausfinden kann, welche Adressen registriert sind.
        require_once __DIR__ . '/../src/classes/Mailer.php';
        if ($found->email_verified_at === null && (new Mailer())->requiresVerification()) {
            $_SESSION['errors'] = ['login' => 'Bitte bestätige zuerst deine E-Mail-Adresse über den Link in der Mail, die wir dir geschickt haben. <a href="/user/ResendVerification.php">Bestätigungsmail erneut senden</a>'];
            $_SESSION['oldEmail'] = $email;
            header("Location: /user/UserLogin.php");
            exit();
        }

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

unset($_SESSION['errors']);

// Hinweise nach Registrieren / Bestaetigen / Passwort zuruecksetzen
$info = $_SESSION['info'] ?? null;
unset($_SESSION['info']);

?>
<div class="contentcontainer">
    <form action="" class="frontform" method="POST">
        <h3>Login</h3>

        <!-- Meldungen als farbige Box direkt im Formular, damit man sie am Handy nicht uebersieht.
             Texte stammen nur aus unserem eigenen Code (teils mit Link), deshalb ohne htmlspecialchars. -->
        <?php if ($info): ?>
            <div class="flash flashInfo"><?= $info ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <div class="flash flashError"><?= $error ?></div>
        <?php endforeach; ?>

        <input type="email" name="email" placeholder="E-Mail" value="<?= htmlspecialchars($oldEmail) ?>" required>
        <input type="password" name="pw" placeholder="Passwort" autocomplete="off" required>
        <?= csrfField() ?>
        <button type="submit" name="loginBtn">Login</button>
        <p class="formHint">Neu registriert? Bitte bestätige zuerst deine E-Mail-Adresse über den Link in der Mail, die wir dir geschickt haben – danach kannst du dich einloggen.</p>
    </form>
    <p><a href="/user/ForgotPassword.php">Passwort vergessen?</a></p>
    <p><a href="/index.php">&larr; Zur Startseite</a></p>
    <p>Noch kein Konto? <a href="/user/UserRegister.php">Hier registrieren</a></p>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
