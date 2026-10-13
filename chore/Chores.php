<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Chore.php';
require_once __DIR__ . '/../src/classes/User.php';

require_once __DIR__ . '/../src/components/guard.php';

if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$choreClass = new Chore($pdo);
$user = new User($pdo);

// Erledigt-Haken setzen (POST/Redirect/Get, Sicherheitscheck: Choreid muss zur aktuellen Familie gehoeren)
if (isset($_POST['markDoneBtn'])) {
    $choreId = (int) ($_POST['choreId'] ?? 0);
    $chore = $choreClass->getChoreById($choreId);
    if ($chore && $chore->fam_id == $_SESSION['fam_id'] && $choreClass->isVisibleToUser($chore, $_SESSION['uid'])) {
        $choreClass->markDone($choreId, $_SESSION['uid']);
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
    'none' => 'Fest zugeteilt',
    'daily' => 'Täglich rotierend',
    'weekly' => 'Wöchentlich rotierend',
    'monthly' => 'Monatlich rotierend',
    'half_yearly' => 'Halbjährlich rotierend',
    'yearly' => 'Jährlich rotierend',
];

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="/Dashboard.php" class="backLink">&larr;</a>

<h2>Ämtli</h2>

<div class="tiles">
<?php foreach ($myChores as $chore): ?>
    <?php
        $assigneeId = $choreClass->getCurrentAssigneeId($chore);
        $assigneeName = $assigneeId ? ($memberNames[$assigneeId] ?? 'Unbekannt') : 'Noch niemand zugeteilt';
        $done = $choreClass->isDoneInCurrentPeriod($chore);
    ?>
    <div class="tile">
        <a class="tile_edit" href="/chore/EditChore.php?id=<?= $chore->id ?>">⚙️</a>
        <div class="tile_icon">🧹</div>
        <div class="tile_title"><?= htmlspecialchars($chore->name) ?></div>
        <div class="tile_subtext">
            <?= htmlspecialchars($rotationLabels[$chore->rotation] ?? $chore->rotation) ?><br>
            Zuständig: <?= htmlspecialchars($assigneeName) ?>
        </div>
        <?php if ($done): ?>
            <p>✅ Erledigt</p>
        <?php else: ?>
            <form action="" method="POST">
                <input type="hidden" name="choreId" value="<?= $chore->id ?>">
                <button type="submit" name="markDoneBtn">Erledigt</button>
            </form>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>

<p><a href="/chore/CreateChore.php">+ Neues Ämtli</a></p>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
