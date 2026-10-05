<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Event.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$year = (int) ($_GET['year'] ?? date('Y'));
$month = (int) ($_GET['month'] ?? date('n'));

$firstDayTimestamp = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDayTimestamp);
$startWeekday = date('N', $firstDayTimestamp); // 1=Montag....7=Sonntag

//vorheriger/nächster Monat - mktime rechnet Jahreswechsel autom mit
$prevTimestamp = mktime(0, 0, 0, $month -1, 1, $year);
$nextTimestamp = mktime(0, 0, 0, $month +1, 1, $year);
$prevYear = date('Y', $prevTimestamp);
$prevMonth = date('n', $prevTimestamp);
$nextYear = date('Y', $nextTimestamp);
$nextMonth = date('n', $nextTimestamp);

$monthNames = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
$weekdays = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

$db = new Database;
$pdo = $db->connect();
$eventClass = new Event($pdo);
$events = $eventClass->getEventsForUserInMonth($_SESSION['uid'], $_SESSION['fam_id'], $year, $month);


//Termine nach Tag gruppieren, damit man sie beim Rendern schnell findet
$eventsByDay = [];
foreach ($events as $event) {
    $day = (int) date('j', strtotime($event->start_at));
    $eventsByDay[$day][] = $event;
}

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="/Dashboard.php" class="backLink">&larr;</a>

<div class="calendarHeader">
    <a href="?year=<?= $prevYear ?>&month=<?= $prevMonth ?>">&larr;</a>
    <h2><?= $monthNames[$month - 1] ?> <a href="/calendar/Year.php?year=<?= $year ?>"><?= $year ?></a></h2>
    <a href="?year=<?= $nextYear ?>&month=<?= $nextMonth ?>">&rarr;</a>
</div>

<div class="calendarGrid">
    <?php foreach($weekdays as $wd): ?>
        <div class="calendarWeekday"><?= $wd ?></div>
    <?php endforeach; ?>

    <?php for ($i = 1; $i < $startWeekday; $i++): ?>
        <div class="calendarDay empty"></div>
    <?php endfor; ?>

    <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
        <a href="/calendar/Day.php?year=<?= $year ?>&month=<?= $month ?>&day=<?= $day ?>" class="calendarDay">
            <span class="calendarDayNumber"><?= $day ?></span>
            <?php if (!empty($eventsByDay[$day])): ?>
                <?php foreach ($eventsByDay[$day] as $event): ?>
                    <span class="calendarEventBar"><?= htmlspecialchars($event->title) ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </a>
    <?php endfor; ?>
</div>

<p><a href="/calendar/CreateEvent.php">+ Neuer Termin</a></p>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
