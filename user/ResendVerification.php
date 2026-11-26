<?php
// Bestaetigungsmail erneut anfordern (z.B. wenn die erste Mail im Spam gelandet oder abgelaufen ist)
require_once __DIR__ . '/../src/components/session.php';
require_once __DIR__ . '/../src/functions.php';

if (isset($_POST['resendBtn'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /user/ResendVerification.php");
        exit();
    }

    require_once __DIR__ . '/../src/classes/Database.php';
    require_once __DIR__ . '/../src/classes/User.php';
    require_once __DIR__ . '/../src/classes/UserToken.php';

    $email = trim($_POST['email'] ?? '');
    $db = new Database;
    $pdo = $db->connect();
    $found = (new User($pdo))->findByEmail($email);

    // Nur an noch nicht bestaetigte Konten, und hoechstens alle 2 Minuten (Schutz gegen Mail-Spam)
    if ($found && $found->email_verified_at === null
        && !(new UserToken($pdo))->wasCreatedRecently($found->id, 'verify', 120)) {
        sendVerificationMail($pdo, $found);
    }

    // Immer dieselbe Meldung - egal ob es die Adresse gibt. Sonst koennte jeder ausprobieren,
    // welche E-Mail-Adressen bei FamilyFlow registriert sind.
    $_SESSION['info'] = 'Falls zu dieser Adresse ein noch nicht bestätigtes Konto existiert, haben wir dir einen neuen Bestätigungslink geschickt.';
    header("Location: /user/UserLogin.php");
    exit();
}

$authTitle = 'Bestätigungsmail';
$authBadge = 'Fast geschafft!';
include_once __DIR__ . '/../src/components/auth_head.php';
?>

        <h1>Mail <em>nochmal</em> senden.</h1>
        <p class="auth-intro">Gib die E-Mail-Adresse ein, mit der du dich registriert hast. Schau auch im Spam-Ordner nach.</p>

    <form action="" method="POST">
        <input type="email" name="email" placeholder="E-Mail" autocomplete="email" required>
        <?= csrfField() ?>
        <button type="submit" name="resendBtn">Link senden</button>
    </form>

    <p class="auth-switch"><a href="/user/UserLogin.php">Zurück zum Login</a></p>

<?php include_once __DIR__ . '/../src/components/auth_footer.php'; ?>
