<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Event.php';
require_once __DIR__ . '/../src/functions.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$year = (int) ($_GET['year'] ?? date('Y'));
$month = (int) ($_GET['month'] ?? date('n'));
$day = (int) ($_GET['day'] ?? date('j'));

$dayTimestamp = mktime(0, 0, 0, $month, $day, $year);
$weekdayNumber = date('N', $dayTimestamp); // 1=Montag...7=Sonntag

$monthNames = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
$weekdayNames = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];

$db = new Database;
$pdo = $db->connect();
$eventClass = new Event($pdo);
$events = $eventClass->getEventsForUserOnDay($_SESSION['uid'], $_SESSION['fam_id'], $year, $month, $day);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';

?>


<a href="/calendar/Calendar.php?year=<?= $year ?>&month=<?= $month ?>" class="backLink">&larr;</a>

<h2><?= $weekdayNames[$weekdayNumber - 1] ?> – <?= $day ?>. <?= $monthNames[$month - 1] ?> <?= $year ?></h2>

<?php if (empty($events)): ?>
    <p>Keine Termine an diesem Tag.</p>
<?php else: ?>
    <?php foreach ($events as $event): ?>
        <div class="eventTile">
            <div class="tile_title"><?= htmlspecialchars($event->title) ?></div>
            <div class="tile_subtext">
                <?= date('H:i', strtotime($event->start_at)) ?> – <?= date('H:i', strtotime($event->end_at)) ?>
                <?php if ($event->location): ?><br><?= htmlspecialchars($event->location) ?><?php endif; ?>
            </div>
            <div class="eventMenu hidden">
                <a href="/calendar/EditEvent.php?id=<?= $event->id ?>">Bearbeiten</a>
                <a href="/calendar/DeleteEvent.php?id=<?= $event->id ?>&csrf_token=<?= urlencode(csrfToken()) ?>" onclick="return confirm('Termin wirklich löschen?')">Löschen</a>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<p><a href="/calendar/CreateEvent.php?date=<?= sprintf('%04d-%02d-%02d', $year, $month, $day) ?>">+ Neuer Termin</a></p>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>