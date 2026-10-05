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
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        <?php if ($unreadCount > 0): ?>
            <span class="bellBadge"><?= $unreadCount ?></span>
        <?php endif; ?>
    </a>
    <span id="menuBtn" class="avatar"><?= htmlspecialchars($initials) ?></span>
    <div id="menuList" class="hidden">
        <a href="/family/SelectFamily.php">Familie wechseln</a>
        <a href="/family/CreateFamily.php">Familie erstellen</a>
        <a href="/family/InviteMember.php">Mitglied einladen</a>
        <a href="/user/Theme.php">Layout ändern</a>
        <a href="/user/UserLogout.php">Logout</a>
    </div>
</div>