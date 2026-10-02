<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Event.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Validator.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$errors = $_SESSION['errors'] ?? [];
$prefilledDate = $_GET['date'] ?? '';

if(isset($_POST['createEventBtn'])) {
    $title = trim($_POST['title'] ?? '');
    $allDay = isset($_POST['all_day']) ? 1 : 0;
    $startAt = $_POST['start_at'] ?? '';
    $endAt = $_POST['end_at'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $sharedWith = $_POST['shared_with'] ?? '';

    $validator = new Validator;

    if(!$validator->required($title)){
        $errors['title'] = 'Titel ist erforderlich';
    }

    if($allDay) {
        $eventDate = $_POST['event_date'] ?? '';
        if(!$validator->required($eventDate)) {
            $errors['event_date'] = 'Datum ist erforderlich';
        }
    } else {
        $startAt = $_POST['start_at'] ?? '';
        $endAt = $_POST['end_at'] ?? '';
        if (!$validator->required($startAt)) {
            $errors['start_at'] = 'Start ist erforderlich';
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
        $startAtForDb = $eventDate . ' 00:00:00';
        $endAtForDb = $eventDate . ' 23:59:59';
    } else {
        $startAtForDb = str_replace('T', ' ', $startAt) . ':00';
        $endAtForDb = str_replace('T', ' ', $endAt) . ':00';
    }

    $db = new Database;
    $pdo = $db->connect();
    $eventClass = new Event($pdo);

    $sharedWithId = $sharedWith !== '' ? (int) $sharedWith : null;

    $eventClass->createEvent(
        $_SESSION['fam_id'],
        $_SESSION['uid'],
        $title,
        $startAtForDb,
        $endAtForDb,
        $location !== '' ? $location : null,
        $description !== '' ? $description : null,
        $sharedWithId,
        $allDay
    );

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

<a href="/calendar/Calendar.php" class="backLink">&larr;</a>

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
            <label>Start: <input type="datetime-local" name="start_at" value="<?= $prefilledDate ? htmlspecialchars($prefilledDate) . 'T09:00' : '' ?>"></label>
            <label>Ende: <input type="datetime-local" name="end_at" value="<?= $prefilledDate ? htmlspecialchars($prefilledDate) . 'T10:00' : '' ?>"></label>
        </div>

        <div id="dateField" class="hidden">
            <label>Datum: <input type="date" name="event_date" value="<?= htmlspecialchars($prefilledDate) ?>"></label>
        </div>

        <input type="text" name="location" placeholder="Ort">
        <textarea name="description" placeholder="Beschreibung"></textarea>

        <label> Teilen mit:
            <select name="shared_with">
                <option value="">Alle</option>
                <option value="<?= $_SESSION['uid'] ?>">Nur ich (privat)</option>
                <?php foreach($members as $member): ?>
                    <?php if($member->id != $_SESSION['uid']): ?>
                        <option value="<?= $member->id ?>"><?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?></option>
                    <?php endif; ?> 
                <?php endforeach; ?>
            </select>
        </label>

        <button type="submit" name="createEventBtn">Termin erstellen</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>