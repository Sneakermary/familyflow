<?php
// "Passwort vergessen?" - schickt einen Link zum Zuruecksetzen per Mail
require_once __DIR__ . '/../src/components/session.php';
require_once __DIR__ . '/../src/functions.php';

if (isset($_POST['forgotBtn'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /user/ForgotPassword.php");
        exit();
    }

    require_once __DIR__ . '/../src/classes/Database.php';
    require_once __DIR__ . '/../src/classes/User.php';
    require_once __DIR__ . '/../src/classes/UserToken.php';

    $email = trim($_POST['email'] ?? '');
    $db = new Database;
    $pdo = $db->connect();
    $found = (new User($pdo))->findByEmail($email);

    // Hoechstens alle 2 Minuten eine Mail (Schutz gegen Mail-Spam an fremde Adressen)
    if ($found && !(new UserToken($pdo))->wasCreatedRecently($found->id, 'reset', 120)) {
        sendPasswordResetMail($pdo, $found);
    }

    // Immer dieselbe Meldung - egal ob es die Adresse gibt. Sonst koennte jeder ausprobieren,
    // welche E-Mail-Adressen bei FamilyFlow registriert sind.
    $_SESSION['info'] = 'Falls zu dieser Adresse ein Konto existiert, haben wir dir eine E-Mail mit einem Link zum Zurücksetzen geschickt. Der Link ist 1 Stunde gültig.';
    header("Location: /user/UserLogin.php");
    exit();
}

$authTitle = 'Passwort vergessen';
$authBadge = 'Kein Problem!';
include_once __DIR__ . '/../src/components/auth_head.php';
?>

        <h1>Passwort <em>vergessen</em>?</h1>
        <p class="auth-intro">Gib deine E-Mail-Adresse ein. Wir schicken dir einen Link, mit dem du ein neues Passwort festlegen kannst.</p>

    <form action="" method="POST">
        <input type="email" name="email" placeholder="E-Mail" autocomplete="email" required>
        <?= csrfField() ?>
        <button type="submit" name="forgotBtn">Link senden</button>
    </form>

    <p class="auth-switch">Doch wieder eingefallen? <a href="/user/UserLogin.php">Zurück zum Login</a></p>

<?php include_once __DIR__ . '/../src/components/auth_footer.php'; ?>
