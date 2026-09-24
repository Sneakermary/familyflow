<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';

$db = new Database;
$pdo = $db->connect();
$user = new User($pdo);
$currentUser = $user->findById($_SESSION['uid']);

    // hier werden die Initialen vom user geholt und groß geschrieben
$initials = mb_strtoupper(mb_substr($currentUser->firstname, 0, 1) 
            . mb_substr($currentUser->lastname, 0, 1));
?>

<div class="navbar">
    <div class="navbarLogo">
        <span class="avatar"><?= htmlspecialchars($initials) ?></span>
        <h3>FamilyFlow</h3>
    </div>
    <button id="menuBtn">Menü</button>
    <div id="menuList" class="hidden">
        <a href="SelectFamily.php">Familie wechseln</a>
        <a href="CreateFamily.php">Familie erstellen</a>
        <a href="AddMember.php">Mitglied hinzufügen</a>
        <a href="UserLogout.php">Logout</a>
    </div>
</div>