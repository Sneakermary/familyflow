<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Event.php';
require_once __DIR__ . '/../src/classes/Family.php';
require_once __DIR__ . '/../src/classes/CalendarSettings.php';

require_once __DIR__ . '/../src/components/guard.php';

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
$familyClass = new Family($pdo);
$settingsClass = new CalendarSettings($pdo);

// Gleiche Farb-/Sichtbarkeits-Einstellungen wie auf MyCalendar.php, damit beides zusammenpasst
$myFamilies = $familyClass->findFamiliesByUserId($_SESSION['uid']);
$defaultPalette = ['#6b7280', '#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6'];
$savedSettings = $settingsClass->getSettingsForUser($_SESSION['uid']);
$familySettings = [];
foreach ($myFamilies as $index => $fam) {
    $familySettings[$fam->id] = $savedSettings[$fam->id] ?? [
        'visible' => true,
        'color' => $defaultPalette[$index % count($defaultPalette)],
    ];
}

$events = $eventClass->getEventsForUserAcrossFamiliesOnDay($_SESSION['uid'], $year, $month, $day);
$events = array_filter($events, fn($event) => $familySettings[$event->fam_id]['visible'] ?? true);
$events = array_merge($events, $eventClass->getBirthdaysForUserAcrossFamiliesOnDay($_SESSION['uid'], $year, $month, $day));
usort($events, fn($a, $b) => strcmp($a->start_at, $b->start_at));

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="#" class="backLink" onclick="history.back(); return false;">&larr;</a>

<h2><?= $weekdayNames[$weekdayNumber - 1] ?> – <?= $day ?>. <?= $monthNames[$month - 1] ?> <?= $year ?></h2>

<?php if (empty($events)): ?>
    <p>Keine Termine an diesem Tag.</p>
<?php else: ?>
    <?php foreach ($events as $event): ?>
        <?php if (!empty($event->isBirthday)): ?>
            <div class="eventTile birthdayTile">
                <div class="tile_title"><?= htmlspecialchars($event->title) ?></div>
            </div>
        <?php else: ?>
            <?php $color = $familySettings[$event->fam_id]['color'] ?? '#6b7280'; ?>
            <div class="eventTile" style="border-left-color: <?= htmlspecialchars($color) ?>">
                <div class="tile_title"><?= htmlspecialchars($event->title) ?></div>
                <div class="tile_subtext">
                    <span class="calendarFamilyDot" style="background: <?= htmlspecialchars($color) ?>"></span>
                    <?= htmlspecialchars($event->family_name) ?><br>
                    <?= date('H:i', strtotime($event->start_at)) ?> – <?= date('H:i', strtotime($event->end_at)) ?>
                    <?php if ($event->location): ?><br><?= htmlspecialchars($event->location) ?><?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
