<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/User.php';
require_once __DIR__ . '/../src/classes/Family.php';
require_once __DIR__ . '/../src/classes/Nickname.php';
require_once __DIR__ . '/../src/functions.php';

require_once __DIR__ . '/../src/components/guard.php';

// Zweiter Check nur hier noetig (nicht alle Seiten brauchen ihn), auch noch vor jedem HTML
if (!isset($_SESSION['fam_id'])) {
    header("Location: /family/CreateFamily.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$family = new Family($pdo);
$user = new User($pdo);

$familyData = $family->findById($_SESSION['fam_id']);
$members = $user->findUserByFamilyId($_SESSION['fam_id']);
$nicknameClass = new Nickname($pdo);
$nicknames = $nicknameClass->getNicknamesForOwner($_SESSION['uid']);

// Ab hier darf HTML ausgegeben werden, alle Redirects sind durch
include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="/Dashboard.php" class="backLink">&larr;</a>

<h2>Meine Familie</h2>
<h3><?= htmlspecialchars($familyData->name) ?></h3>

<?php foreach ($members as $member): ?>
    <?php $isSelf = $member->id == $_SESSION['uid']; ?>
    <div class="member<?= $isSelf ? '' : ' memberTile' ?>">
        <span class="avatar"><?= htmlspecialchars(initials($member->firstname, $member->lastname)) ?></span>
        <span>
            <?php if (isset($nicknames[$member->id])): ?>
                <?= htmlspecialchars($nicknames[$member->id]) ?>
            <?php else: ?>
                <?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?>
            <?php endif; ?>
        </span>
        <?php if (!$isSelf): ?>
            <div class="eventMenu hidden">
                <a href="/family/EditNickname.php?id=<?= $member->id ?>">Bearbeiten</a>
                <a href="/family/RemoveMember.php?id=<?= $member->id ?>&csrf_token=<?= urlencode(csrfToken()) ?>" onclick="return confirm('<?= htmlspecialchars($member->firstname) ?> <?= htmlspecialchars($member->lastname) ?> wirklich aus der Familie entfernen?')">Löschen</a>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>


<?php include_once __DIR__ . '/../src/components/footer.php'; ?>