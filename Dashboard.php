<?php
require_once __DIR__ .'/src/classes/Database.php';
require_once __DIR__ .'/src/classes/User.php';
require_once __DIR__ .'/src/classes/Family.php';

// guard.php: session_start() + Login-Pflicht (kein HTML, darf also zuerst kommen)
require_once __DIR__ . '/src/components/guard.php';

// Zweiter Check nur hier noetig (nicht alle Seiten brauchen ihn), auch noch vor jedem HTML
if(!isset($_SESSION['fam_id'])) {
    header("Location: CreateFamily.php");
    exit();
}

$db = new Database;
$pdo = $db->connect();
$family = new Family($pdo);
$user = new User($pdo);

$familyData = $family->findById($_SESSION['fam_id']);
$members = $user->findUserByFamilyId($_SESSION['fam_id']);

// Ab hier darf HTML ausgegeben werden, alle Redirects sind durch
include_once __DIR__ . '/src/components/head.php';
include_once __DIR__ . '/src/components/navbar.php';
?>

<h2>Dashboard</h2>
<h3><?= htmlspecialchars($familyData->name) ?></h3>

<?php foreach($members as $member): ?>
    <p> <?= htmlspecialchars($member->firstname) ?>
        <?= htmlspecialchars($member->lastname) ?> </p>
<?php endforeach; ?>

<?php include_once __DIR__ . '/src/components/footer.php'; ?>

<div class="tile">
    <div class="tile_icon">
        📋
        <div class="tile_title">
            <div class="tile_subtext">
                Noch keine Listen
            </div>
        </div>
    </div>
</div>