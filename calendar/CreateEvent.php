<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Event.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Validator.php';
require_once __DIR__ . '/../src/classes/Notification.php';
require_once __DIR__ . '/../src/functions.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$errors = $_SESSION['errors'] ?? [];
$prefilledDate = $_GET['date'] ?? '';

if(isset($_POST['createEventBtn'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /calendar/CreateEvent.php");
        exit();
    }

    $title = trim($_POST['title'] ?? '');
    $allDay = isset($_POST['all_day']) ? 1 : 0;
    $startAt = $_POST['start_at'] ?? '';
    $endAt = $_POST['end_at'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $sharedAll = isset($_POST['shared_all']);
    $sharedWithIds = array_map('intval', $_POST['shared_with'] ?? []);

    $validator = new Validator;
    $minDate = date('Y-m-d', strtotime('-1 year'));

    if(!$validator->required($title)){
        $errors['title'] = 'Titel ist erforderlich';
    }

    if($allDay) {
        $eventDateFrom = $_POST['event_date_from'] ?? '';
        $eventDateTo = $_POST['event_date_to'] ?? '';
        if(!$validator->required($eventDateFrom)) {
            $errors['event_date_from'] = 'Startdatum ist erforderlich';
        } elseif ($eventDateFrom < $minDate) {
            $errors['event_date_from'] = 'Datum darf nicht mehr als 1 Jahr in der Vergangenheit liegen';
        }
        if(!$validator->required($eventDateTo)) {
            $errors['event_date_to'] = 'Enddatum ist erforderlich';
        } elseif ($eventDateFrom !== '' && $eventDateTo < $eventDateFrom) {
            $errors['event_date_to'] = 'Enddatum darf nicht vor dem Startdatum liegen';
        }
    } else {
        $startAt = $_POST['start_at'] ?? '';
        $endAt = $_POST['end_at'] ?? '';
        if (!$validator->required($startAt)) {
            $errors['start_at'] = 'Start ist erforderlich';
        } elseif (substr($startAt, 0, 10) < $minDate) {
            $errors['start_at'] = 'Start darf nicht mehr als 1 Jahr in der Vergangenheit liegen';
        }
        if (!$validator->required($endAt)) {
            $errors['end_at'] = 'Ende ist erforderlich';
        }
    }

    if(!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: /calendar/CreateEvent.php");
        exit();
    }

    if($allDay) {
        $startAtForDb = $eventDateFrom . ' 00:00:00';
        $endAtForDb = $eventDateTo . ' 23:59:59';
    } else {
        $startAtForDb = str_replace('T', ' ', $startAt) . ':00';
        $endAtForDb = str_replace('T', ' ', $endAt) . ':00';
    }

    $db = new Database;
    $pdo = $db->connect();
    $eventClass = new Event($pdo);
    $user = new User($pdo);

    $eventId = $eventClass->createEvent(
        $_SESSION['fam_id'],
        $_SESSION['uid'],
        $title,
        $startAtForDb,
        $endAtForDb,
        $location !== '' ? $location : null,
        $description !== '' ? $description : null,
        $allDay
    );

    // Leeres Array = fuer alle sichtbar (gleiche Bedeutung wie frueher shared_with = NULL)
    $eventClass->setSharedWith($eventId, $sharedAll ? [] : $sharedWithIds);

    // Benachrichtigung an alle, die den Termin sehen duerfen (ausser einem selbst)
    $notificationClass = new Notification($pdo);
    $fromUser = $user->findById($_SESSION['uid']);
    $dayTimestamp = strtotime($startAtForDb);
    $eventLink = '/calendar/Day.php?year=' . date('Y', $dayTimestamp) . '&month=' . (int) date('n', $dayTimestamp) . '&day=' . (int) date('j', $dayTimestamp);

    $notifyIds = $sharedAll
        ? array_column($user->findUserByFamilyId($_SESSION['fam_id']), 'id')
        : $sharedWithIds;

    foreach ($notifyIds as $notifyId) {
        if ($notifyId != $_SESSION['uid']) {
            $notificationClass->notify(
                $notifyId,
                $fromUser->firstname . ' ' . $fromUser->lastname . ' hat einen Termin erstellt: ' . $title,
                $eventLink
            );
        }
    }

    header("Location: /calendar/Calendar.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$user = new User($pdo);
$members = $user->findUserByFamilyId($_SESSION['fam_id']);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="#" class="backLink" onclick="history.back(); return false;">&larr;</a>

<h2>Neuer Termin</h2>

<?php foreach ($errors as $error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>
<?php unset($_SESSION['errors']); ?>

<div class="contentcontainer">
    <form action="" method="POST">
        <input type="text" name="title" placeholder="Titel" required>

        <label>
            <input type="checkbox" name="all_day" id="allDayCheckbox">
            Ganztägig
        </label>
        <div id="timeFields">
            <label>Start: <input type="datetime-local" name="start_at" id="startAtInput" value="<?= $prefilledDate ? htmlspecialchars($prefilledDate) . 'T09:00' : '' ?>"></label>
            <label>Ende: <input type="datetime-local" name="end_at" id="endAtInput" value="<?= $prefilledDate ? htmlspecialchars($prefilledDate) . 'T10:00' : '' ?>"></label>
        </div>

        <div id="dateField" class="hidden">
            <label>Von: <input type="date" name="event_date_from" value="<?= htmlspecialchars($prefilledDate) ?>"></label>
            <label>Bis: <input type="date" name="event_date_to" value="<?= htmlspecialchars($prefilledDate) ?>"></label>
        </div>

        <input type="text" name="location" placeholder="Ort">
        <textarea name="description" placeholder="Beschreibung"></textarea>

        <label>
            <input type="checkbox" name="shared_all" id="sharedAllCheckbox" checked>
            Für alle sichtbar
        </label>
        <div id="sharedWithFields" class="hidden">
            <p>Wer soll es sehen können?</p>
            <?php foreach ($members as $member): ?>
                <?php if ($member->id != $_SESSION['uid']): ?>
                    <label>
                        <input type="checkbox" name="shared_with[]" value="<?= $member->id ?>">
                        <?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?= csrfField() ?>
        <button type="submit" name="createEventBtn">Termin erstellen</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>