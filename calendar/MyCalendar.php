<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Event.php';
require_once __DIR__ . '/../src/classes/Family.php';
require_once __DIR__ . '/../src/classes/CalendarSettings.php';
require_once __DIR__ . '/../src/functions.php';

require_once __DIR__ . '/../src/components/guard.php';

$db = new Database;
$pdo = $db->connect();
$eventClass = new Event($pdo);
$familyClass = new Family($pdo);
$settingsClass = new CalendarSettings($pdo);

$myFamilies = $familyClass->findFamiliesByUserId($_SESSION['uid']);

// Default-Farbpalette, falls fuer eine Familie noch keine eigene Farbe gewaehlt wurde
$defaultPalette = ['#6b7280', '#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6'];

$savedSettings = $settingsClass->getSettingsForUser($_SESSION['uid']);
$familySettings = [];
foreach ($myFamilies as $index => $fam) {
    $familySettings[$fam->id] = $savedSettings[$fam->id] ?? [
        'visible' => true,
        'color' => $defaultPalette[$index % count($defaultPalette)],
    ];
}

// Einstellungen speichern: welche Familien sichtbar sind + ihre Farbe
if (isset($_POST['saveCalendarSettingsBtn'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /calendar/MyCalendar.php");
        exit();
    }

    $newSettings = [];
    foreach ($myFamilies as $fam) {
        $color = $_POST['color'][$fam->id] ?? $familySettings[$fam->id]['color'];
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = $familySettings[$fam->id]['color'];
        }
        $newSettings[$fam->id] = [
            'visible' => isset($_POST['visible'][$fam->id]),
            'color' => $color,
        ];
    }
    $settingsClass->saveSettings($_SESSION['uid'], $newSettings);
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query(['year' => $_GET['year'] ?? date('Y'), 'month' => $_GET['month'] ?? date('n')]));
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

// Familienuebergreifend: Termine aus allen Familien der Person, nach den Sichtbarkeits-Einstellungen gefiltert
$events = $eventClass->getEventsForUserAcrossFamiliesInMonth($_SESSION['uid'], $year, $month);
$events = array_filter($events, fn($event) => $familySettings[$event->fam_id]['visible'] ?? true);

// Termine nach Tag gruppieren, gleiches Prinzip wie Calendar.php
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
    <h2>Mein Kalender</h2>
    <?php if ($isCurrentMonth): ?>
        <a href="/calendar/MyDay.php?year=<?= date('Y') ?>&month=<?= date('n') ?>&day=<?= date('j') ?>" class="todayBtn">Heute</a>
    <?php else: ?>
        <a href="?year=<?= date('Y') ?>&month=<?= date('n') ?>" class="todayBtn">Heute</a>
    <?php endif; ?>
    <span id="listMenuBtn" class="dots">⋮</span>
    <div id="listMenuList" class="hidden">
        <a href="/calendar/Calendar.php">Zum Familien-Kalender</a>
        <form action="" method="POST" class="calendarFamilyForm">
            <?php foreach ($myFamilies as $fam): ?>
                <label class="calendarFamilyRow">
                    <input type="checkbox" name="visible[<?= $fam->id ?>]" value="1" <?= $familySettings[$fam->id]['visible'] ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($fam->name) ?></span>
                    <input type="color" name="color[<?= $fam->id ?>]" value="<?= htmlspecialchars($familySettings[$fam->id]['color']) ?>">
                </label>
            <?php endforeach; ?>
            <?= csrfField() ?>
            <button type="submit" name="saveCalendarSettingsBtn">Speichern</button>
        </form>
    </div>
</div>

<div class="calendarHeader">
    <a href="?year=<?= $prevYear ?>&month=<?= $prevMonth ?>">&larr;</a>
    <h3><?= $monthNames[$month - 1] ?> <?= $year ?></h3>
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
                <a href="/calendar/MyDay.php?year=<?= $year ?>&month=<?= $month ?>&day=<?= $day ?>" class="calendarDay<?= ($isCurrentMonth && $day == $today) ? ' calendarDayToday' : '' ?>">
                    <span class="calendarDayNumber"><?= $day ?></span>
                    <?php if (!empty($eventsByDay[$day])): ?>
                        <?php foreach ($eventsByDay[$day] as $event): ?>
                            <span class="calendarEventBar" style="background: <?= htmlspecialchars($familySettings[$event->fam_id]['color'] ?? '#6b7280') ?>"><?= htmlspecialchars($event->title) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
