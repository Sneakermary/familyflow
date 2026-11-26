<?php
// Ziel des Links aus der Bestaetigungsmail: /user/VerifyEmail.php?token=...
require_once __DIR__ . '/../src/components/session.php';
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
    header("Location: /user/UserLogin.php");
    exit();
}

(new User($pdo))->markEmailVerified($userId);
// Link ist nur einmal gueltig
$tokens->deleteForUser($userId, 'verify');

// Direkt einloggen: Wer den geheimen Link aus der Mail hat, hat bewiesen, dass ihm die
// Adresse gehoert (Link ist zufaellig, nur 24 h und nur einmal gueltig).
// Gleiches Vorgehen wie beim normalen Login: neue Session-Id, alte Familie vergessen.
session_regenerate_id(true);
unset($_SESSION['fam_id']);
$_SESSION['uid'] = $userId;
$_SESSION['info'] = 'Willkommen bei FamilyFlow! Deine E-Mail-Adresse ist bestätigt. Richte jetzt kurz dein Profil ein.';

// Neue Mitglieder zuerst zum Profil-Einrichten, danach geht es weiter zur Familie
header("Location: /user/EditProfile.php?setup=1");
exit();
