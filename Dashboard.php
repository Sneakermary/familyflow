<?php
require_once __DIR__ . '/src/classes/Database.php';
require_once __DIR__ . '/src/classes/User.php';
require_once __DIR__ . '/src/classes/Family.php';
require_once __DIR__ . '/src/functions.php';

// guard.php: session_start() + Login-Pflicht (kein HTML, darf also zuerst kommen)
require_once __DIR__ . '/src/components/guard.php';

// Zweiter Check nur hier noetig (nicht alle Seiten brauchen ihn), auch noch vor jedem HTML
if (!isset($_SESSION['fam_id'])) {
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

<div class="tiles">
<!-- LISTEN -->
    <div class="tile" data-href="Lists.php">
        <div class="tile_icon">📋</div>
        <div class="tile_title">Listen</div>
        <div class="tile_subtext">
            Noch keine Listen
        </div>
    </div>
    <div class="tile">
<!-- KALENDER -->
        <div class="tile_icon">📅</div>
        <div class="tile_title">Kalender</div>
        <div class="tile_subtext">
            Noch keine Termine
        </div>
    </div>
<!-- FAMILIE -->
<div class="tile" data-href="MyFamily.php">
        <div class="tile_icon">👨‍👩‍👧‍👦</div>
        <div class="tile_title">Meine Familie</div>
        <div class="tile_subtext">
            Familie verwalten
        </div>
    </div>
    <div class="tile">
<!-- REZEPTE -->
        <div class="tile_icon">🍽️</div>
        <div class="tile_title">Rezepte</div>
        <div class="tile_subtext">
            Noch keine Rezepte
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/src/components/footer.php'; ?>

