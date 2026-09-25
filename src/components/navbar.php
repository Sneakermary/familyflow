<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../functions.php';

$db = new Database;
$pdo = $db->connect();
$user = new User($pdo);
$currentUser = $user->findById($_SESSION['uid']);

    // hier werden die Initialen vom user geholt und groß geschrieben
$initials = initials($currentUser->firstname, $currentUser->lastname);

?>
<div class="navbar">
    <div class="navbarLogo">
        <h3>FamilyFlow</h3>
    </div>
    <span id="menuBtn" class="avatar"><?= htmlspecialchars($initials) ?></span>
    <div id="menuList" class="hidden">
        <a href="SelectFamily.php">Familie wechseln</a>
        <a href="CreateFamily.php">Familie erstellen</a>
        <a href="AddMember.php">Mitglied hinzufügen</a>
        <a href="UserLogout.php">Logout</a>
    </div>
</div>