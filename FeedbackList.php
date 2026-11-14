<?php
require_once __DIR__ . '/src/classes/Database.php';
require_once __DIR__ . '/src/classes/User.php';
require_once __DIR__ . '/src/classes/Feedback.php';

require_once __DIR__ . '/src/components/guard.php';

$db = new Database;
$pdo = $db->connect();
$user = new User($pdo);
$feedbackClass = new Feedback($pdo);

// Nur Annas eigener Account darf das Feedback von allen lesen - kein allgemeines
// Admin-System in der App, deshalb fest an die eigene E-Mail-Adresse gebunden.
$me = $user->findById($_SESSION['uid']);
if (!$me || $me->email !== 'anna@mama.at') {
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
