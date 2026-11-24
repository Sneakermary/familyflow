<?php
// Ziel des Links aus der "Passwort vergessen"-Mail: /user/ResetPassword.php?token=...
require_once __DIR__ . '/../src/components/session.php';
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/UserToken.php';
require_once __DIR__ . '/../src/classes/Validator.php';
require_once __DIR__ . '/../src/functions.php';

$db = new Database;
$pdo = $db->connect();
$tokens = new UserToken($pdo);

// Token kommt beim Aufruf aus dem Link (GET), beim Absenden aus dem versteckten Feld (POST)
$token = $_POST['token'] ?? $_GET['token'] ?? '';
$userId = $tokens->findValidUserId($token, 'reset');

if ($userId === null) {
    $_SESSION['errors'] = ['reset' => 'Dieser Link ist ungültig oder abgelaufen. <a href="/user/ForgotPassword.php">Neuen Link anfordern</a>'];
    header("Location: /user/UserLogin.php");
    exit();
}

$errors = [];

if (isset($_POST['resetBtn'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /user/ResetPassword.php?token=" . urlencode($token));
        exit();
    }

    $password = $_POST['pw'] ?? '';
    $pwConfirm = $_POST['pwConfirm'] ?? '';
    $validator = new Validator;

    if (!$validator->minlength($password, 8)) {
        $errors[] = 'Passwort muss mindestens 8 Zeichen lang sein.';
    } elseif (!$validator->matches($password, $pwConfirm)) {
        $errors[] = 'Die Passwörter stimmen nicht überein!';
    }

    if (empty($errors)) {
        $user = new User($pdo);
        $user->updatePassword($userId, password_hash($password, PASSWORD_DEFAULT));
        // Wer den Link aus der Mail benutzt hat, hat damit auch bewiesen, dass die Adresse ihm gehoert
        $user->markEmailVerified($userId);
        // Link ist nur einmal gueltig
        $tokens->deleteForUser($userId, 'reset');

        $_SESSION['info'] = 'Dein Passwort wurde geändert. Du kannst dich jetzt mit dem neuen Passwort einloggen.';
        header("Location: /user/UserLogin.php");
        exit();
    }
}

include_once __DIR__ . '/../src/components/head.php';
?>

<div class="contentcontainer">
    <form action="/user/ResetPassword.php" class="frontform" method="POST">
        <h3>Neues Passwort festlegen</h3>
        <?php foreach ($errors as $error): ?>
            <div class="flash flashError"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
        <input type="password" name="pw" placeholder="Neues Passwort" minlength="8" required>
        <input type="password" name="pwConfirm" placeholder="Neues Passwort bestätigen" required>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <?= csrfField() ?>
        <button type="submit" name="resetBtn">Passwort speichern</button>
    </form>
    <p><a href="/user/UserLogin.php">&larr; Zurück zum Login</a></p>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
