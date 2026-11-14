<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Event.php';
require_once __DIR__ . '/../src/classes/Family.php';

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

$isCurrentMonth = ($year == date('Y') && $month == date('n'));
$today = (int) date('j');

// Tage des Monats als flache Liste (null = leeres Feld vor Monatsbeginn), dann in
// 7er-Wochen aufgeteilt, damit sich pro Zeile die Kalenderwoche berechnen laesst.
$cells = [];
for ($i = 1; $i < $startWeekday; $i++) {
    $cells[] = null;
}
for ($day = 1; $day <= $daysInMonth; $day++) {
    $cells[] = $day;
}
$weeks = array_chunk($cells, 7);

$db = new Database;
$pdo = $db->connect();
$eventClass = new Event($pdo);
$familyClass = new Family($pdo);
$events = $eventClass->getEventsForUserInMonth($_SESSION['uid'], $_SESSION['fam_id'], $year, $month);
$familyData = $familyClass->findById($_SESSION['fam_id']);


// Termine nach Tag gruppieren, damit man sie beim Rendern schnell findet.
// Mehrtaegige Termine werden auf jeden Tag eingetragen, den sie abdecken - faengt der
// Termin vor diesem Monat an oder endet er danach, wird auf den sichtbaren Bereich begrenzt.
$monthKey = sprintf('%04d-%02d', $year, $month);
$eventsByDay = [];
foreach ($events as $event) {
    $startDay = (date('Y-m', strtotime($event->start_at)) === $monthKey) ? (int) date('j', strtotime($event->start_at)) : 1;
    $endDay = (date('Y-m', strtotime($event->end_at)) === $monthKey) ? (int) date('j', strtotime($event->end_at)) : $daysInMonth;

    for ($d = $startDay; $d <= $endDay; $d++) {
        $eventsByDay[$d][] = $event;
    }
}

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="#" class="backLink" onclick="history.back(); return false;">&larr;</a>

<div class="listHeader">
    <h2><?= htmlspecialchars($familyData->name) ?> Kalender</h2>
    <span id="listMenuBtn" class="dots">⋮</span>
    <div id="listMenuList" class="hidden">
        <a href="/calendar/CreateEvent.php">Termin erstellen</a>
        <a href="/calendar/Year.php?year=<?= $year ?>">Jahresansicht</a>
        <a href="/calendar/MyCalendar.php">Mein Kalender</a>
    </div>
</div>

<div class="calendarHeader">
    <a href="?year=<?= $prevYear ?>&month=<?= $prevMonth ?>">&larr;</a>
    <div class="calendarHeaderCenter">
        <h3><?= $monthNames[$month - 1] ?> <a href="/calendar/Year.php?year=<?= $year ?>"><?= $year ?></a></h3>
        <?php if ($isCurrentMonth): ?>
            <a href="/calendar/Day.php?year=<?= date('Y') ?>&month=<?= date('n') ?>&day=<?= date('j') ?>" class="todayBtn">Heute</a>
        <?php else: ?>
            <a href="?year=<?= date('Y') ?>&month=<?= date('n') ?>" class="todayBtn">Heute</a>
        <?php endif; ?>
    </div>
    <a href="?year=<?= $nextYear ?>&month=<?= $nextMonth ?>">&rarr;</a>
</div>

<div class="calendarGrid">
    <div class="calendarWeekday calendarWeekLabel">KW</div>
    <?php foreach($weekdays as $wd): ?>
        <div class="calendarWeekday"><?= $wd ?></div>
    <?php endforeach; ?>

    <?php foreach ($weeks as $week): ?>
        <?php
            $firstRealDay = null;
            foreach ($week as $d) {
                if ($d !== null) {
                    $firstRealDay = $d;
                    break;
                }
            }
            $weekNumber = $firstRealDay ? date('W', mktime(0, 0, 0, $month, $firstRealDay, $year)) : '';
        ?>
        <div class="calendarWeekNumber"><?= $weekNumber ?></div>
        <?php foreach ($week as $day): ?>
            <?php if ($day === null): ?>
                <div class="calendarDay empty"></div>
            <?php else: ?>
                <a href="/calendar/Day.php?year=<?= $year ?>&month=<?= $month ?>&day=<?= $day ?>" class="calendarDay<?= ($isCurrentMonth && $day == $today) ? ' calendarDayToday' : '' ?>">
                    <span class="calendarDayNumber"><?= $day ?></span>
                    <?php if (!empty($eventsByDay[$day])): ?>
                        <?php foreach ($eventsByDay[$day] as $event): ?>
                            <span class="calendarEventBar"><?= htmlspecialchars($event->title) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
