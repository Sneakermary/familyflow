<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/ProfileNote.php';
require_once __DIR__ . '/../src/classes/Nickname.php';
require_once __DIR__ . '/../src/functions.php';

require_once __DIR__ . '/../src/components/guard.php';

$db = new Database;
$pdo = $db->connect();
$user = new User($pdo);
$noteClass = new ProfileNote($pdo);
$nicknameClass = new Nickname($pdo);

$targetId = isset($_GET['id']) ? (int) $_GET['id'] : (int) $_SESSION['uid'];
$isSelf = $targetId === (int) $_SESSION['uid'];

$target = $user->findById($targetId);

// Sicherheitscheck bei fremden Profilen: Zielperson muss Mitglied der aktuell aktiven Familie sein
if ($target && !$isSelf) {
    $famMembers = isset($_SESSION['fam_id']) ? $user->findUserByFamilyId($_SESSION['fam_id']) : [];
    $target = in_array($targetId, array_column($famMembers, 'id')) ? $target : null;
}

if (!$target) {
    header("Location: /Dashboard.php");
    exit();
}

if (isset($_POST['addNoteBtn'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /user/Profile.php?id=" . $targetId);
        exit();
    }

    $label = trim($_POST['label'] ?? '');
    $value = trim($_POST['value'] ?? '');
    // Bei sich selbst per Checkbox waehlbar (Standard: oeffentlich), bei fremden Profilen immer privat
    $isPrivate = $isSelf ? isset($_POST['private']) : true;

    if ($label !== '' && $value !== '') {
        $noteClass->createNote($_SESSION['uid'], $targetId, $label, $value, $isPrivate);
    }
    header("Location: /user/Profile.php?id=" . $targetId);
    exit();
}

if (isset($_GET['deleteNote'])) {
    if (!verifyCsrfToken($_GET['csrf_token'] ?? '')) {
        header("Location: /user/Profile.php?id=" . $targetId);
        exit();
    }

    // deleteNote() loescht ohnehin nur, wenn owner_id = eigene Id passt - fremde Notizen
    // (z.B. oeffentliche Felder, die die Zielperson selbst angelegt hat) bleiben unberuehrt
    $noteClass->deleteNote((int) $_GET['deleteNote'], $_SESSION['uid']);
    header("Location: /user/Profile.php?id=" . $targetId);
    exit();
}

$zodiac = zodiacSign($target->birthdate);
$myNotes = $noteClass->getNotesForOwnerAndTarget($_SESSION['uid'], $targetId);
$publicNotes = $isSelf ? [] : $noteClass->getPublicNotesForTarget($targetId);
$nickname = $isSelf ? null : $nicknameClass->getNickname($_SESSION['uid'], $targetId);

// Festes Linkziel statt history.back(): nach dem Bearbeiten des eigenen Profils landet man
// ueber den Redirect wieder hier, im Verlauf steht davor noch EditProfile.php - history.back()
// wuerde zurueck ins gerade verlassene Formular fuehren statt zur eigentlichen Profilansicht.
// Eigenes Profil ist von ueberall per Navbar erreichbar (-> Dashboard als sinnvolles Ziel),
// fremde Profile ausschliesslich ueber den "Anzeigen"-Link auf MyFamily.php.
$backUrl = $isSelf ? '/Dashboard.php' : '/family/MyFamily.php';

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="<?= $backUrl ?>" class="backLink">&larr;</a>

<div class="listHeader">
    <h2><?= $isSelf ? 'Mein Profil' : htmlspecialchars($nickname ?: $target->firstname) . 's Profil' ?></h2>
    <span id="listMenuBtn" class="dots">⋮</span>
    <div id="listMenuList" class="hidden">
        <?php if ($isSelf): ?>
            <a href="/user/EditProfile.php">Bearbeiten</a>
        <?php endif; ?>
        <a href="#" onclick="document.getElementById('addNoteForm').classList.toggle('hidden'); document.getElementById('listMenuList').classList.add('hidden'); return false;">Feld hinzufügen</a>
    </div>
</div>

<div class="contentcontainer">
    <?= avatarHtml($target, 'class="profileAvatar"') ?>

    <p class="profileName">
        <strong><?= htmlspecialchars($target->firstname) ?> <?= htmlspecialchars($target->lastname) ?></strong>
    </p>

    <p class="profileDetail"><?= htmlspecialchars($target->email) ?></p>

    <?php if ($zodiac): ?>
        <p class="profileDetail"><strong><?= htmlspecialchars($zodiac) ?></strong></p>
    <?php endif; ?>

    <?php if (!empty($publicNotes) || !empty($myNotes)): ?>
        <p class="noteHeading"><strong>Notiz</strong></p>

        <?php foreach ($publicNotes as $note): ?>
            <p class="noteRow"><strong class="noteLabel"><?= htmlspecialchars($note->label) ?>:</strong> <?= htmlspecialchars($note->value) ?></p>
        <?php endforeach; ?>

        <?php foreach ($myNotes as $note): ?>
            <div class="noteTile noteRow">
                <span><strong class="noteLabel"><?= htmlspecialchars($note->label) ?>:</strong> <?= htmlspecialchars($note->value) ?></span>
                <div class="eventMenu hidden">
                    <a href="/user/Profile.php?id=<?= $targetId ?>&deleteNote=<?= $note->id ?>&csrf_token=<?= urlencode(csrfToken()) ?>" onclick="return confirm('Feld wirklich löschen?')">Löschen</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form action="" method="POST" id="addNoteForm" class="hidden">
        <label class="stackedField">Bezeichnung
            <input type="text" name="label" placeholder="z. B. Lieblingstier" required>
        </label>
        <label class="stackedField">Wert
            <input type="text" name="value" placeholder="z. B. Hund" required>
        </label>
        <?php if ($isSelf): ?>
            <label><input type="checkbox" name="private" value="1"> Privat (nur ich sehe es)</label>
        <?php endif; ?>
        <?= csrfField() ?>
        <button type="submit" name="addNoteBtn">Speichern</button>
        <button type="button" onclick="document.getElementById('addNoteForm').classList.add('hidden')">Abbrechen</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
