<?php
require_once __DIR__ . '/../src/components/session.php';

session_unset();
session_destroy();
header("Location: /user/UserLogin.php");
exit();
?>