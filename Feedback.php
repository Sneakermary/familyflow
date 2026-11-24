<?php
require_once __DIR__ . '/src/classes/Database.php';
require_once __DIR__ . '/src/classes/Feedback.php';
require_once __DIR__ . '/src/functions.php';

require_once __DIR__ . '/src/components/guard.php';

$db = new Database;
$pdo = $db->connect();
$feedbackClass = new Feedback($pdo);

$success = false;

if (isset($_POST['sendFeedbackBtn'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /Feedback.php");
        exit();
    }

    $fields = [
        'dashboard' => trim($_POST['dashboard'] ?? ''),
        'listen' => trim($_POST['listen'] ?? ''),
        'kalender' => trim($_POST['kalender'] ?? ''),
        'meine_familie' => trim($_POST['meine_familie'] ?? ''),
        'rezepte' => trim($_POST['rezepte'] ?? ''),
        'aufgaben' => trim($_POST['aufgaben'] ?? ''),
        'layout' => trim($_POST['layout'] ?? ''),
        'allgemein' => trim($_POST['allgemein'] ?? ''),
    ];

    $feedbackClass->createFeedback($_SESSION['uid'], $fields);
    $success = true;
}

include_once __DIR__ . '/src/components/head.php';
include_once __DIR__ . '/src/components/navbar.php';
?>

<a href="#" class="backLink" onclick="history.back(); return false;">&larr;</a>

<h2>Wünsche, Beschwerden und Anregungen</h2>

<div class="contentcontainer">
    <?php if (isAppAdmin($pdo)): ?>
        <!-- Nur fuer die Betreiberin sichtbar (Adresse aus mailconfig.php) -->
        <a href="/FeedbackList.php" class="linkButton">📋 Alle Rückmeldungen ansehen</a>
    <?php endif; ?>

    <?php if ($success): ?>
        <p>Danke für dein Feedback! 🎉</p>
    <?php endif; ?>

    <p>Schreib einfach zu den Bereichen, die dir auffallen – alle Felder sind optional.</p>

    <form action="" method="POST">
        <label class="stackedField">Dashboard
            <textarea name="dashboard" rows="3"></textarea>
        </label>
        <label class="stackedField">Listen
            <textarea name="listen" rows="3"></textarea>
        </label>
        <label class="stackedField">Kalender
            <textarea name="kalender" rows="3"></textarea>
        </label>
        <label class="stackedField">Meine Familie
            <textarea name="meine_familie" rows="3"></textarea>
        </label>
        <label class="stackedField">Rezepte
            <textarea name="rezepte" rows="3"></textarea>
        </label>
        <label class="stackedField">Aufgaben
            <textarea name="aufgaben" rows="3"></textarea>
        </label>
        <label class="stackedField">Layout
            <textarea name="layout" rows="3"></textarea>
        </label>
        <label class="stackedField">Allgemein
            <textarea name="allgemein" rows="3"></textarea>
        </label>
        <?= csrfField() ?>
        <button type="submit" name="sendFeedbackBtn">Absenden</button>
    </form>
</div>

<?php include_once __DIR__ . '/src/components/footer.php'; ?>
