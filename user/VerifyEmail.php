<?php
// Ziel des Links aus der Bestaetigungsmail: /user/VerifyEmail.php?token=...
session_start();
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/UserToken.php';
require_once __DIR__ . '/../src/functions.php';

$db = new Database;
$pdo = $db->connect();
$tokens = new UserToken($pdo);

$userId = $tokens->findValidUserId($_GET['token'] ?? '', 'verify');

if ($userId === null) {
    $_SESSION['errors'] = ['verify' => 'Dieser Bestätigungslink ist ungültig oder abgelaufen. <a href="/user/ResendVerification.php">Neuen Link anfordern</a>'];
} else {
    (new User($pdo))->markEmailVerified($userId);
    // Link ist nur einmal gueltig
    $tokens->deleteForUser($userId, 'verify');
    $_SESSION['info'] = 'Danke! Deine E-Mail-Adresse ist bestätigt. Du kannst dich jetzt einloggen.';
}

header("Location: /user/UserLogin.php");
exit();
