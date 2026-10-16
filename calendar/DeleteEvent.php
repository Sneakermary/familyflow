<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Event.php';
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
    && ($event->shared_with === null || $event->shared_with == $_SESSION['uid'] || $event->owner_id == $_SESSION['uid']);

if (!$darfLoeschen) {
    header("Location: /calendar/Calendar.php");
    exit();
}

$dayTimestamp = strtotime($event->start_at);
$year = date('Y', $dayTimestamp);
$month = (int) date('n', $dayTimestamp);
$day = (int) date('j', $dayTimestamp);

$eventClass->deleteEvent($eventId);

header("Location: /calendar/Day.php?year=$year&month=$month&day=$day");
exit();
