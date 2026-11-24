<?php
// Session + Login-Pflicht fuer alle geschuetzten Seiten (kein HTML hier drin!)
require_once __DIR__ . '/session.php';

if (!isset($_SESSION['uid'])) {
    header("Location: /user/UserLogin.php");
    exit();
}
