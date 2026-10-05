<?php
$themeClass = 'theme-standard';
if (isset($_SESSION['uid'])) {
    require_once __DIR__ . '/../classes/Database.php';
    $db = new Database;
    $pdo = $db->connect();
    $stmt = $pdo->prepare("SELECT theme FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['uid']]);
    $themeClass = 'theme-' . $stmt->fetchColumn();
}
?>
<!-- HTML-Geruest, keine Session-/Login-Logik (die steckt in guard.php) -->
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FamilyFlow</title>
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="<?= htmlspecialchars($themeClass) ?>">
