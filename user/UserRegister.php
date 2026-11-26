<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/components/session.php';

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

    if(!empty($errors)){
        $_SESSION['errors'] = $errors;
        // Passwoerter bewusst nicht gespeichert, nur die unkritischen Felder
        $_SESSION['oldValues'] = ['firstname' => $firstname, 'lastname' => $lastname, 'email' => $email];
        header("Location: /user/UserRegister.php");
        exit();
    }

    // Gibt es die Adresse schon? Die Seite zeigt in JEDEM Fall dieselbe Meldung,
    // damit niemand herausfinden kann, welche E-Mail-Adressen bei FamilyFlow registriert sind.
    // Was wirklich los ist, erfaehrt nur die Besitzerin der Adresse per Mail.
    $existing = $user->findByEmail($email);

    if ($existing) {
        require_once __DIR__ . '/../src/classes/UserToken.php';
        $tokens = new UserToken($pdo);
        $sent = true;

        if ($existing->email_verified_at === null) {
            // Konto noch nicht bestaetigt -> Bestaetigungsmail nochmal (hoechstens alle 2 Minuten)
            if (!$tokens->wasCreatedRecently($existing->id, 'verify', 120)) {
                $sent = sendVerificationMail($pdo, $existing);
            }
        } elseif (!$tokens->wasCreatedRecently($existing->id, 'reset', 120)) {
            // Konto bestaetigt -> Hinweis "Du hast schon ein Konto" (hoechstens alle 2 Minuten)
            $sent = sendAlreadyRegisteredMail($pdo, $existing);
        }
    } else {
        $pw_hash = password_hash($password, PASSWORD_DEFAULT);

        $newUserId = $user->createUser($firstname, $lastname, $email, $pw_hash);
        $newUser = $user->findById($newUserId);

        // Info an die Betreiberin (Adresse steht in mailconfig.php, leer = keine Mail)
        sendNewUserNotification($newUser);

        // Bestaetigungsmail mit Link schicken
        $sent = sendVerificationMail($pdo, $newUser);
    }

    if ($sent) {
        $_SESSION['info'] = 'Fast geschafft! Wir haben dir eine E-Mail an ' . htmlspecialchars($email) . ' geschickt. Bitte klicke auf den Link darin, um deine Adresse zu bestätigen.';
    } else {
        $_SESSION['info'] = 'Dein Konto wurde angelegt, aber die Bestätigungsmail konnte gerade nicht verschickt werden. Bitte versuche es später über "Bestätigungsmail erneut senden".';
    }

    header("Location: /user/UserLogin.php");
    exit();
}

// Ab hier nur noch Anzeige, keine Redirects mehr moeglich
$authTitle = 'Registrieren';
$authBadge = 'Hallo, liebe Familie!';
include_once __DIR__ . '/../src/components/auth_head.php';

unset($_SESSION['errors']);

$oldValues = $_SESSION['oldValues'] ?? [];
unset($_SESSION['oldValues']);
?>

        <h1>Konto <em>anlegen</em>.</h1>
        <p class="auth-intro">Ein paar Angaben, dann könnt ihr gemeinsam loslegen – kostenlos.</p>

    <form action="" method="POST">
        <?php foreach ($errors as $error): ?>
            <div class="flash flashError"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <div class="auth-row">
            <input type="text" name="firstname" placeholder="Vorname" autocomplete="given-name" value="<?= htmlspecialchars($oldValues['firstname'] ?? '') ?>" required>
            <input type="text" name="lastname" placeholder="Nachname" autocomplete="family-name" value="<?= htmlspecialchars($oldValues['lastname'] ?? '') ?>" required>
        </div>
        <input type="email" name="email" placeholder="E-Mail" autocomplete="email" value="<?= htmlspecialchars($oldValues['email'] ?? '') ?>" required>
        <input type="password" name="pw" placeholder="Passwort (mind. 8 Zeichen)" autocomplete="new-password" minlength="8" required>
        <input type="password" name="pwConfirm" placeholder="Passwort bestätigen" autocomplete="new-password" required>
        <?= csrfField() ?>
        <button type="submit" name="registerBtn">Registrieren</button>
        <p class="formHint">Nach dem Registrieren bekommst du eine E-Mail mit einem Bestätigungslink. Erst nach dem Klick darauf kannst du dich einloggen.</p>
    </form>

    <p class="auth-switch">Schon ein Konto? <a href="/user/UserLogin.php">Hier einloggen</a></p>

<?php include_once __DIR__ . '/../src/components/auth_footer.php'; ?>