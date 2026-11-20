<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Event.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Notification.php';
require_once __DIR__ . '/../src/functions.php';

require_once __DIR__ . '/../src/components/guard.php';

$eventId = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($eventId === null || !verifyCsrfToken($_GET['csrf_token'] ?? '')) {
    header("Location: /calendar/Calendar.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$eventClass = new Event($pdo);

$event = $eventClass->getEventById($eventId);

// Sicherheitscheck: gehoert der Termin zur aktuellen Familie, und darf ich ihn ueberhaupt sehen?
$darfLoeschen = isset($_SESSION['fam_id']) && $event && $event->fam_id == $_SESSION['fam_id']
    && $eventClass->isVisibleToUser($eventId, $_SESSION['uid']);

if (!$darfLoeschen) {
    header("Location: /calendar/Calendar.php");
    exit();
}

$dayTimestamp = strtotime($event->start_at);
$year = date('Y', $dayTimestamp);
$month = (int) date('n', $dayTimestamp);
$day = (int) date('j', $dayTimestamp);

// Benachrichtigung an alle, die den Termin sehen durften (ausser der loeschenden Person selbst)
$user = new User($pdo);
$notificationClass = new Notification($pdo);
$fromUser = $user->findById($_SESSION['uid']);
$deleteMessage = $fromUser->firstname . ' ' . $fromUser->lastname . ' hat den Termin "' . $event->title . '" am ' . date('d.m.Y', $dayTimestamp) . ' gelöscht';

$currentSharedWith = $eventClass->getSharedWithIds($eventId);
$notifyIds = empty($currentSharedWith)
    ? array_column($user->findUserByFamilyId($event->fam_id), 'id')
    : $currentSharedWith;

foreach ($notifyIds as $notifyId) {
    if ($notifyId != $_SESSION['uid']) {
        $notificationClass->notify($notifyId, $deleteMessage);
    }
}

$eventClass->deleteEvent($eventId);

header("Location: /calendar/Day.php?year=$year&month=$month&day=$day");
exit();
