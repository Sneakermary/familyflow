<?php
require_once __DIR__ . '/src/classes/Database.php';
require_once __DIR__ . '/src/classes/User.php';
require_once __DIR__ . '/src/classes/Feedback.php';
require_once __DIR__ . '/src/functions.php';

require_once __DIR__ . '/src/components/guard.php';

$db = new Database;
$pdo = $db->connect();
$feedbackClass = new Feedback($pdo);

// Nur die Betreiberin darf das Feedback von allen lesen. Wer das ist, steht als
// 'admin_email' in src/config/mailconfig.php (nicht im Code, also nicht auf GitHub).
if (!isAppAdmin($pdo)) {
    header("Location: /Dashboard.php");
    exit();
}

$entries = $feedbackClass->getAllFeedback();

$categories = [
    'dashboard' => 'Dashboard',
    'listen' => 'Listen',
    'kalender' => 'Kalender',
    'meine_familie' => 'Meine Familie',
    'rezepte' => 'Rezepte',
    'aufgaben' => 'Aufgaben',
    'layout' => 'Layout',
    'allgemein' => 'Allgemein',
];

include_once __DIR__ . '/src/components/head.php';
include_once __DIR__ . '/src/components/navbar.php';
?>

<a href="#" class="backLink" onclick="history.back(); return false;">&larr;</a>

<h2>Feedback von Testerinnen und Testern</h2>

<?php if (empty($entries)): ?>
    <p>Noch kein Feedback vorhanden.</p>
<?php endif; ?>

<?php foreach ($entries as $entry): ?>
    <div class="contentcontainer">
        <p><strong><?= htmlspecialchars($entry->firstname) ?> <?= htmlspecialchars($entry->lastname) ?></strong>
            – <?= date('d.m.Y H:i', strtotime($entry->created_at)) ?></p>

        <?php foreach ($categories as $field => $label): ?>
            <?php if (!empty($entry->$field)): ?>
                <p><strong><?= htmlspecialchars($label) ?>:</strong><br><?= nl2br(htmlspecialchars($entry->$field)) ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<?php include_once __DIR__ . '/src/components/footer.php'; ?>
