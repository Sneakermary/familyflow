<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../functions.php';

$db = new Database;
$pdo = $db->connect();
$user = new User($pdo);
$currentUser = $user->findById($_SESSION['uid']);

// hier werden die Initialen vom user geholt und groß geschrieben
$initials = initials($currentUser->firstname, $currentUser->lastname);

$notificationClass = new Notification($pdo);
$unreadCount = $notificationClass->getUnreadCountForUser($_SESSION['uid']);

?>
<div class="navbar">
    <div class="navbarLogo">
        <a href="/family/SelectFamily.php">
            <h3>FamilyFlow</h3>
        </a>
    </div>
    <a href="/Notifications.php" class="bellLink">
        🔔
        <?php if ($unreadCount > 0): ?>
            <span class="bellBadge"><?= $unreadCount ?></span>
        <?php endif; ?>
    </a>
    <span id="menuBtn" class="avatar"><?= htmlspecialchars($initials) ?></span>
    <div id="menuList" class="hidden">
        <a href="/family/SelectFamily.php">Familie wechseln</a>
        <a href="/family/CreateFamily.php">Familie erstellen</a>
        <a href="/family/InviteMember.php">Mitglied einladen</a>
        <a href="/user/UserLogout.php">Logout</a>
    </div>
</div>