<?php
require_once __DIR__ .'/src/classes/Database.php';
require_once __DIR__ .'/src/classes/User.php';
require_once __DIR__ .'/src/classes/Family.php';
session_start();




if(!isset($_SESSION['uid'])) {
header("Location: UserLogin.php");
exit();
}

if(!isset($_SESSION['fam_id'])) {
    header("Location: CreateFamily.php");
    exit();
}
$db = new Database;
$pdo = $db->connect();
$family = new Family($pdo);
$user = new User($pdo);

$familyData = $family->findById($_SESSION['fam_id']);
$members = $user->findByFamilyId($_SESSION['fam_id']);
?>

<h2>Dashboard</h2>
<h3><?= htmlspecialchars($familyData->name) ?></h3>

<?php foreach($members as $member): ?>
    <p> <?= htmlspecialchars($member->firstname) ?> 
        <?= htmlspecialchars($member->lastname) ?> </p>
<?php endforeach; ?>


<a href="UserLogout.php">Logout</a>

