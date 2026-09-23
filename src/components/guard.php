<?php
// Session + Login-Pflicht fuer alle geschuetzten Seiten (kein HTML hier drin!)
session_start();

if (!isset($_SESSION['uid'])) {
    header("Location: UserLogin.php");
    exit();
}
