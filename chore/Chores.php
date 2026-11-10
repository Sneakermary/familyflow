<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Chore.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/functions.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$choreClass = new Chore($pdo);
$user = new User($pdo);

// Erledigt-Haken setzen (Sicherheitscheck: Choreid muss zur aktuellen Familie gehoeren und sichtbar sein)
if (isset($_GET['markDone'])) {
    if (!verifyCsrfToken($_GET['csrf_token'] ?? '')) {
        header("Location: /chore/Chores.php");
        exit();
    }

    $choreId = (int) $_GET['markDone'];
    $chore = $choreClass->getChoreById($choreId);
    if ($chore && $chore->fam_id == $_SESSION['fam_id'] && $choreClass->isVisibleToUser($chore, $_SESSION['uid'])) {
        $choreClass->markDone($choreId, $_SESSION['uid']);
    }
    header("Location: /chore/Chores.php");
    exit();
}

// Erledigt-Status zuruecksetzen
if (isset($_GET['markNotDone'])) {
    if (!verifyCsrfToken($_GET['csrf_token'] ?? '')) {
        header("Location: /chore/Chores.php");
        exit();
    }

    $choreId = (int) $_GET['markNotDone'];
    $chore = $choreClass->getChoreById($choreId);
    if ($chore && $chore->fam_id == $_SESSION['fam_id'] && $choreClass->isVisibleToUser($chore, $_SESSION['uid'])) {
        $choreClass->markNotDone($choreId);
    }
    header("Location: /chore/Chores.php");
    exit();
}

$myChores = $choreClass->getChoresForUser($_SESSION['uid'], $_SESSION['fam_id']);

// Namen der Familienmitglieder einmal holen, statt pro Ämtli einzeln nachzufragen
$members = $user->findUserByFamilyId($_SESSION['fam_id']);
$memberNames = [];
foreach ($members as $member) {
    $memberNames[$member->id] = $member->firstname . ' ' . $member->lastname;
}

$rotationLabels = [
    'none' => 'Einmalig',
    'daily' => 'Täglich rotierend',
    'weekly' => 'Wöchentlich rotierend',
    'monthly' => 'Monatlich rotierend',
    'half_yearly' => 'Halbjährlich rotierend',
    'yearly' => 'Jährlich rotierend',
];

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="#" class="backLink" onclick="history.back(); return false;">&larr;</a>

<div class="listHeader">
    <h2>Aufgaben</h2>
    <span id="listMenuBtn" class="dots">⋮</span>
    <div id="listMenuList" class="hidden">
        <a href="/chore/CreateChore.php">Aufgabe erstellen</a>
    </div>
</div>

<?php foreach ($myChores as $chore): ?>
    <?php
        $assigneeId = $choreClass->getCurrentAssigneeId($chore);
        $assigneeName = $assigneeId ? ($memberNames[$assigneeId] ?? 'Unbekannt') : 'Noch niemand zugeteilt';
        $done = $choreClass->isDoneInCurrentPeriod($chore);
    ?>
    <div class="choreTile">
        <div class="tile_title"><?= htmlspecialchars($chore->name) ?></div>
        <div class="tile_subtext">
            <?= htmlspecialchars($rotationLabels[$chore->rotation] ?? $chore->rotation) ?><br>
            Zuständig: <?= htmlspecialchars($assigneeName) ?>
            <?php if ($done): ?><br>✅ Erledigt<?php endif; ?>
        </div>
        <div class="eventMenu hidden">
            <a href="/chore/EditChore.php?id=<?= $chore->id ?>">Bearbeiten</a>
            <?php if ($chore->owner_id == $_SESSION['uid']): ?>
                <a href="/chore/DeleteChore.php?id=<?= $chore->id ?>&csrf_token=<?= urlencode(csrfToken()) ?>" onclick="return confirm('Aufgabe wirklich löschen?')">Löschen</a>
            <?php endif; ?>
            <?php if ($done): ?>
                <a href="/chore/Chores.php?markNotDone=<?= $chore->id ?>&csrf_token=<?= urlencode(csrfToken()) ?>">Nicht erledigt</a>
            <?php else: ?>
                <a href="/chore/Chores.php?markDone=<?= $chore->id ?>&csrf_token=<?= urlencode(csrfToken()) ?>">Erledigt</a>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
