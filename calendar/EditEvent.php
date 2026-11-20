<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Event.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Validator.php';
require_once __DIR__ . '/../src/functions.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$eventId = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($eventId === null) {
    header("Location: /calendar/Calendar.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$eventClass = new Event($pdo);

$event = $eventClass->getEventById($eventId);

// Sicherheitscheck: gehoert der Termin zur aktuellen Familie, und darf ich ihn ueberhaupt sehen?
$darfBearbeiten = $event && $event->fam_id == $_SESSION['fam_id']
    && $eventClass->isVisibleToUser($eventId, $_SESSION['uid']);

if (!$darfBearbeiten) {
    header("Location: /calendar/Calendar.php");
    exit();
}

$errors = $_SESSION['errors'] ?? [];

if (isset($_POST['editEventBtn'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /calendar/EditEvent.php?id=" . $eventId);
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

    if (!$validator->required($title)) {
        $errors['title'] = 'Titel ist erforderlich';
    }

    if ($allDay) {
        $eventDateFrom = $_POST['event_date_from'] ?? '';
        $eventDateTo = $_POST['event_date_to'] ?? '';
        if (!$validator->required($eventDateFrom)) {
            $errors['event_date_from'] = 'Startdatum ist erforderlich';
        }
        if (!$validator->required($eventDateTo)) {
            $errors['event_date_to'] = 'Enddatum ist erforderlich';
        } elseif ($eventDateFrom !== '' && $eventDateTo < $eventDateFrom) {
            $errors['event_date_to'] = 'Enddatum darf nicht vor dem Startdatum liegen';
        }
    } else {
        if (!$validator->required($startAt)) {
            $errors['start_at'] = 'Start ist erforderlich';
        }
        if (!$validator->required($endAt)) {
            $errors['end_at'] = 'Ende ist erforderlich';
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: /calendar/EditEvent.php?id=" . $eventId);
        exit();
    }

    if ($allDay) {
        $startAtForDb = $eventDateFrom . ' 00:00:00';
        $endAtForDb = $eventDateTo . ' 23:59:59';
    } else {
        $startAtForDb = str_replace('T', ' ', $startAt) . ':00';
        $endAtForDb = str_replace('T', ' ', $endAt) . ':00';
    }

    $eventClass->updateEvent(
        $eventId,
        $title,
        $startAtForDb,
        $endAtForDb,
        $location !== '' ? $location : null,
        $description !== '' ? $description : null,
        $allDay
    );
    $eventClass->setSharedWith($eventId, $sharedAll ? [] : $sharedWithIds);

    $dayTimestamp = strtotime($startAtForDb);
    header("Location: /calendar/Day.php?year=" . date('Y', $dayTimestamp) . "&month=" . (int) date('n', $dayTimestamp) . "&day=" . (int) date('j', $dayTimestamp));
    exit();
}

$user = new User($pdo);
$members = $user->findUserByFamilyId($_SESSION['fam_id']);
$currentSharedWith = $eventClass->getSharedWithIds($eventId);

$startAtValue = str_replace(' ', 'T', substr($event->start_at, 0, 16));
$endAtValue = str_replace(' ', 'T', substr($event->end_at, 0, 16));
$eventDateFromValue = substr($event->start_at, 0, 10);
$eventDateToValue = substr($event->end_at, 0, 10);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="#" class="backLink" onclick="history.back(); return false;">&larr;</a>

<h2>Termin bearbeiten</h2>

<?php foreach ($errors as $error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>
<?php unset($_SESSION['errors']); ?>

<div class="contentcontainer">
    <form action="" method="POST">
        <input type="text" name="title" placeholder="Titel" value="<?= htmlspecialchars($event->title) ?>" required>

        <label>
            <input type="checkbox" name="all_day" id="allDayCheckbox" <?= $event->all_day ? 'checked' : '' ?>>
            Ganztägig
        </label>
        <div id="timeFields" class="<?= $event->all_day ? 'hidden' : '' ?>">
            <label>Start: <input type="datetime-local" name="start_at" id="startAtInput" value="<?= htmlspecialchars($startAtValue) ?>"></label>
            <label>Ende: <input type="datetime-local" name="end_at" id="endAtInput" value="<?= htmlspecialchars($endAtValue) ?>"></label>
        </div>

        <div id="dateField" class="<?= $event->all_day ? '' : 'hidden' ?>">
            <label>Von: <input type="date" name="event_date_from" value="<?= htmlspecialchars($eventDateFromValue) ?>"></label>
            <label>Bis: <input type="date" name="event_date_to" value="<?= htmlspecialchars($eventDateToValue) ?>"></label>
        </div>

        <input type="text" name="location" placeholder="Ort" value="<?= htmlspecialchars($event->location ?? '') ?>">
        <textarea name="description" placeholder="Beschreibung"><?= htmlspecialchars($event->description ?? '') ?></textarea>

        <label>
            <input type="checkbox" name="shared_all" id="sharedAllCheckbox" <?= empty($currentSharedWith) ? 'checked' : '' ?>>
            Für alle sichtbar
        </label>
        <div id="sharedWithFields" class="<?= empty($currentSharedWith) ? 'hidden' : '' ?>">
            <p>Wer soll es sehen können?</p>
            <?php foreach ($members as $member): ?>
                <?php if ($member->id != $_SESSION['uid']): ?>
                    <label>
                        <input type="checkbox" name="shared_with[]" value="<?= $member->id ?>" <?= in_array($member->id, $currentSharedWith) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?= csrfField() ?>
        <button type="submit" name="editEventBtn">Speichern</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
