<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/Family.php';
require_once __DIR__ . '/../classes/Chat.php';
require_once __DIR__ . '/../functions.php';

$db = new Database;
$pdo = $db->connect();
$user = new User($pdo);
$currentUser = $user->findById($_SESSION['uid']);

$notificationClass = new Notification($pdo);
$unreadCount = $notificationClass->getUnreadCountForUser($_SESSION['uid']);

// Ungelesene Chat-Nachrichten (Zaehler an der Sprechblase)
$chatUnread = (new Chat($pdo))->unreadTotal($_SESSION['uid']);

$familyClass = new Family($pdo);
$currentFamily = isset($_SESSION['fam_id']) ? $familyClass->findById($_SESSION['fam_id']) : null;
$myFamilies = $familyClass->findFamiliesByUserId($_SESSION['uid']);

?>
<div class="navbar">
    <div class="navbarLogo">
        <a href="/Dashboard.php">
            <h3>FamilyFlow</h3>
        </a>
    </div>
    <?php if ($currentFamily): ?>
        <div class="familySwitch">
            <span id="familySwitchBtn" class="familyName">
                <?= htmlspecialchars($currentFamily->name) ?>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="familySwitchCaret">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </span>
            <div id="familySwitchList" class="hidden">
                <?php foreach ($myFamilies as $fam): ?>
                    <a href="/family/SelectFamily.php?fam_id=<?= $fam->id ?>">
                        <?= htmlspecialchars($fam->name) ?><?= $fam->id == $_SESSION['fam_id'] ? ' ✓' : '' ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    <!-- Chat: Sprechblase im gleichen Stil wie die Glocke, Zaehler = ungelesene Nachrichten -->
    <a href="/chat/Chat.php" class="bellLink chatLink" aria-label="Chat">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <!-- gespiegelt (translate + scale(-1,1)), damit die Spitze nach unten rechts zeigt -->
            <path transform="translate(24 0) scale(-1 1)" d="M21 11.5a8.4 8.4 0 0 1-9 8.4 9 9 0 0 1-3.8-.8L3 21l1.9-5.2A8.4 8.4 0 1 1 21 11.5z" />
        </svg>
        <?php if ($chatUnread > 0): ?>
            <span class="bellBadge"><?= $chatUnread ?></span>
        <?php endif; ?>
    </a>
    <a href="/Notifications.php" class="bellLink" aria-label="Benachrichtigungen">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
        </svg>
        <?php if ($unreadCount > 0): ?>
            <span class="bellBadge"><?= $unreadCount ?></span>
        <?php endif; ?>
    </a>
    <?= avatarHtml($currentUser, 'id="menuBtn" class="avatar"') ?>
    <div id="menuList" class="hidden">
        <a href="/user/Profile.php">Mein Profil</a>
        <a href="/family/SelectFamily.php">Familie wechseln</a>
        <a href="/family/CreateFamily.php">Familie erstellen</a>
        <a href="/family/InviteMember.php">Mitglied einladen</a>
        <a href="/user/Theme.php">Layout ändern</a>
        <a href="/user/UserLogout.php">Logout</a>
    </div>
</div>