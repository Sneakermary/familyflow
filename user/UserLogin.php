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

    require_once __DIR__ . '/../src/classes/LoginThrottle.php';

    $email = trim($_POST['email'] ?? '');
    $pw = $_POST['pw'] ?? '';

    $db = new Database;
    $pdo = $db->connect();

    // Login-Sperre nach zu vielen Fehlversuchen (Regeln siehe LoginThrottle.php).
    // Waehrend der Sperre wird das Passwort gar nicht erst geprueft.
    $throttle = new LoginThrottle($pdo);
    $ip = LoginThrottle::clientIp();
    $lockedSeconds = $throttle->secondsLocked($email, $ip);
    if ($lockedSeconds > 0) {
        $_SESSION['errors'] = ['login' => lockMessage($lockedSeconds)];
        $_SESSION['oldEmail'] = $email;
        header("Location: /user/UserLogin.php");
        exit();
    }

    $user = new User($pdo);

    $found = $user->findByEmail($email);

    if ($found && password_verify($pw, $found->pw_hash)) {
        // Richtiges Passwort: bisherige Fehlversuche vergessen
        $throttle->clear($email, $ip);

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
       // Fehlversuch merken - auch fuer Adressen ohne Konto, sonst wuerde die Sperre verraten,
       // welche Adressen registriert sind. Ist die Grenze jetzt erreicht, gleich die Sperre melden.
       $throttle->recordFailure($email, $ip);
       $lockedSeconds = $throttle->secondsLocked($email, $ip);
       $_SESSION['errors'] = ['login' => $lockedSeconds > 0
           ? lockMessage($lockedSeconds)
           : 'E-Mail oder Passwort ist falsch!'];
       $_SESSION['oldEmail'] = $email;
       header("Location: /user/UserLogin.php");
       exit();
    }

}

$oldEmail = $_SESSION['oldEmail'] ?? '';
unset($_SESSION['oldEmail']);

$authTitle = 'Login';
$authBadge = 'Schön, dass du da bist!';
include_once __DIR__ . '/../src/components/auth_head.php';

unset($_SESSION['errors']);

// Hinweise nach Registrieren / Bestaetigen / Passwort zuruecksetzen
$info = $_SESSION['info'] ?? null;
unset($_SESSION['info']);

?>
        <h1>Willkommen <em>zurück</em>.</h1>
        <p class="auth-intro">Melde dich an und schau, was bei euch zu Hause heute ansteht.</p>

    <form action="" method="POST">
        <!-- Meldungen als farbige Box direkt im Formular, damit man sie am Handy nicht uebersieht.
             Texte stammen nur aus unserem eigenen Code (teils mit Link), deshalb ohne htmlspecialchars. -->
        <?php if ($info): ?>
            <div class="flash flashInfo"><?= $info ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <div class="flash flashError"><?= $error ?></div>
        <?php endforeach; ?>

        <input type="email" name="email" placeholder="E-Mail" autocomplete="email" value="<?= htmlspecialchars($oldEmail) ?>" required>
        <input type="password" name="pw" placeholder="Passwort" autocomplete="current-password" required>
        <a href="/user/ForgotPassword.php" class="auth-forgot">Passwort vergessen?</a>
        <?= csrfField() ?>
        <button type="submit" name="loginBtn">Einloggen</button>
        <p class="formHint">Neu registriert? Bitte bestätige zuerst deine E-Mail-Adresse über den Link in der Mail, die wir dir geschickt haben – danach kannst du dich einloggen.</p>
    </form>

    <p class="auth-switch">Noch kein Konto? <a href="/user/UserRegister.php">Jetzt kostenlos registrieren</a></p>

<?php include_once __DIR__ . '/../src/components/auth_footer.php'; ?>
