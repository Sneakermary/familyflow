<?php
require_once __DIR__ . '/src/classes/Database.php';
require_once __DIR__ . '/src/classes/Notification.php';

require_once __DIR__ . '/src/components/guard.php';

$db = new Database;
$pdo = $db->connect();
$notificationClass = new Notification($pdo);

$notifications = $notificationClass->getNotificationsForUser($_SESSION['uid']);
$notificationClass->markAllAsReadForUser($_SESSION['uid']);

include_once __DIR__ . '/src/components/head.php';
include_once __DIR__ . '/src/components/navbar.php';
?>

<a href="/Dashboard.php" class="backLink">&larr;</a>

<h2>Benachrichtigungen</h2>

<div class="contentcontainer">
    <?php if (empty($notifications)): ?>
        <p>Keine Benachrichtigungen.</p>
    <?php else: ?>
        <?php foreach ($notifications as $n): ?>
            <div class="notification <?= $n->is_read ? '' : 'unread' ?>">
                <?php if ($n->link): ?>
                    <a href="<?= htmlspecialchars($n->link) ?>"><?= htmlspecialchars($n->message) ?></a>
                <?php else: ?>
                    <span><?= htmlspecialchars($n->message) ?></span>
                <?php endif; ?>
                <span class="notificationDate"><?= date('d.m.Y H:i', strtotime($n->created_at)) ?></span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/src/components/footer.php'; ?>
