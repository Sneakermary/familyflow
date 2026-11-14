<?php
require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$year = (int) ($_GET['year'] ?? date('Y'));

$monthNames = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
$weekdays = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="#" class="backLink" onclick="history.back(); return false;">&larr;</a>

<div class="yearHeader">
    <a href="?year=<?= $year - 1 ?>">&larr;</a>
    <div class="yearHeaderCenter">
        <h2><?= $year ?></h2>
        <a href="/calendar/Calendar.php?year=<?= date('Y') ?>&month=<?= date('n') ?>" class="todayBtn">Heute</a>
    </div>
    <a href="?year=<?= $year + 1 ?>">&rarr;</a>
</div>

<div class="yearGrid">
    <?php for ($month = 1; $month <= 12; $month++): ?>
        <?php $isCurrentMonth = ($year == date('Y') && $month == date('n')); ?>
        <div class="miniMonth<?= $isCurrentMonth ? ' miniMonthCurrent' : '' ?>">
            <a href="/calendar/Calendar.php?year=<?= $year ?>&month=<?= $month ?>" class="miniMonthTitle"><?= $monthNames[$month - 1] ?></a>

            <div class="miniMonthGrid">
                <?php foreach ($weekdays as $wd): ?>
                    <span class="miniWeekday"><?= $wd ?></span>
                <?php endforeach; ?>

                <?php
                    $firstDayTimestamp = mktime(0, 0, 0, $month, 1, $year);
                    $daysInMonth = date('t', $firstDayTimestamp);
                    $startWeekday = date('N', $firstDayTimestamp);
                ?>

                <?php for ($i = 1; $i < $startWeekday; $i++): ?>
                    <span class="miniDay empty"></span>
                <?php endfor; ?>

                <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                    <span class="miniDay"><?= $day ?></span>
                <?php endfor; ?>
            </div>
        </div>
    <?php endfor; ?>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>