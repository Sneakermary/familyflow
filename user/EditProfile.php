<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Validator.php';
require_once __DIR__ . '/../src/functions.php';

require_once __DIR__ . '/../src/components/guard.php';

$db = new Database;
$pdo = $db->connect();
$user = new User($pdo);
$validator = new Validator();

$me = $user->findById($_SESSION['uid']);
$errors = [];

// Einrichtungs-Modus direkt nach dem Bestaetigen der E-Mail (?setup=1):
// Begruessung, "Spaeter"-Link, nach dem Speichern weiter zur Familie statt zum Profil.
// action="" im Formular behaelt ?setup=1 auch beim Absenden.
$isSetup = isset($_GET['setup']);
$nextPage = $isSetup ? '/family/SelectFamily.php' : '/user/Profile.php';

if (isset($_POST['saveProfileBtn'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /user/EditProfile.php");
        exit();
    }

    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birthdate = $_POST['birthdate'] ?? '';

    if (!$validator->required($firstname)) {
        $errors[] = 'Vorname ist erforderlich';
    }
    if (!$validator->required($lastname)) {
        $errors[] = 'Nachname ist erforderlich';
    }
    if (!$validator->email($email)) {
        $errors[] = 'Ungültige E-Mail-Adresse';
    } else {
        // Email darf nicht schon einer ANDEREN Person gehören
        $existing = $user->findByEmail($email);
        if ($existing && $existing->id != $_SESSION['uid']) {
            $errors[] = 'Diese E-Mail-Adresse ist schon vergeben';
        }
    }

    $birthdateValue = $birthdate !== '' ? $birthdate : null;

    // Profilbild optional, gleiche Hilfsfunktion wie bei Rezepten (nur kleiner)
    $avatarError = null;
    $avatarFilename = handleImageUpload('avatar', 'avatars', 'avatar', 512, $avatarError);
    if ($avatarError !== null) {
        $errors[] = $avatarError;
    }

    if (empty($errors)) {
        $user->updateProfile($_SESSION['uid'], $firstname, $lastname, $email, $birthdateValue, $avatarFilename);
        // Altes Profilbild loeschen, wenn ein neues hochgeladen wurde
        if ($avatarFilename !== null) {
            deleteUploadedImage('avatars', $me->avatar);
        }
        if ($isSetup) {
            $_SESSION['info'] = 'Dein Profil ist fertig! Jetzt erstelle eure Familie oder nimm eine Einladung an.';
        }
        header("Location: " . $nextPage);
        exit();
    }

    // Bei Fehlern wurde evtl. trotzdem schon ein Bild gespeichert -> wieder weg damit
    deleteUploadedImage('avatars', $avatarFilename);

    // Bei Fehlern: Formular mit den gerade eingegebenen Werten statt der alten DB-Werte zeigen
    $me->firstname = $firstname;
    $me->lastname = $lastname;
    $me->email = $email;
    $me->birthdate = $birthdateValue;
}

// Begruessung nach dem Bestaetigen der E-Mail (kommt aus VerifyEmail.php)
$info = $_SESSION['info'] ?? null;
unset($_SESSION['info']);

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<!-- Festes Linkziel statt history.back(): normal kommt man von Profile.php,
     beim Einrichten nach der Registrierung geht "zurueck" zur Familienauswahl. -->
<a href="<?= $nextPage ?>" class="backLink">&larr;</a>

<h2><?= $isSetup ? 'Profil einrichten' : 'Profil bearbeiten' ?></h2>

<div class="contentcontainer">
    <?php if ($info): ?>
        <div class="flash flashInfo"><?= htmlspecialchars($info) ?></div>
    <?php endif; ?>
    <?php if ($isSetup): ?>
        <p class="formHint">Lade ein Profilbild hoch und trag dein Geburtsdatum ein, dann sieht deine Familie deinen Geburtstag im Kalender. Du kannst alles später unter „Mein Profil“ ändern.</p>
    <?php endif; ?>
    <?php foreach ($errors as $error): ?>
        <div class="flash flashError"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <?= avatarHtml($me, 'class="profileAvatar"') ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <label>Profilbild
            <input type="file" name="avatar" accept="image/*">
        </label>
        <label>Vorname
            <input type="text" name="firstname" value="<?= htmlspecialchars($me->firstname) ?>" required>
        </label>
        <label>Nachname
            <input type="text" name="lastname" value="<?= htmlspecialchars($me->lastname) ?>" required>
        </label>
        <label>E-Mail
            <input type="email" name="email" value="<?= htmlspecialchars($me->email) ?>" required>
        </label>
        <label>Geburtsdatum
            <input type="date" name="birthdate" value="<?= htmlspecialchars($me->birthdate ?? '') ?>">
        </label>
        <?= csrfField() ?>
        <button type="submit" name="saveProfileBtn"><?= $isSetup ? 'Speichern und weiter' : 'Speichern' ?></button>
    </form>
    <?php if ($isSetup): ?>
        <p class="formHint skipLink"><a href="/family/SelectFamily.php">Später einrichten &rarr;</a></p>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
