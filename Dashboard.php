<?php
session_start();

if(!isset($_SESSION['uid'])) {
header("Location: UserLogin.php");
exit();
}

if(!isset($_SESSION['fam_id'])) {
    header("Location: CreateFamily.php");
    exit();
}
?>

<h3>Dashboard</h3>

<a href="UserLogout.php">Logout</a>