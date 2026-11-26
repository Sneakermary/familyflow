<?php
// HTML-Geruest fuer die Seiten ohne Login (Login, Registrieren, Passwort vergessen/zuruecksetzen,
// Bestaetigungsmail). Sieht aus wie die Startseite und hat ein eigenes Stylesheet (auth.css),
// damit die App-Styles und Themes hier nicht hineinwirken.
//
// Vor dem Einbinden setzen:
//   $authTitle = 'Login';                      -> Titel im Browser-Tab
//   $authBadge = 'Schön, dass du wieder da bist!';  -> gelbes Schildchen ueber der Ueberschrift
$logoSvg = require __DIR__ . '/logo.php';
$authTitle = $authTitle ?? 'FamilyFlow';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title><?= htmlspecialchars($authTitle) ?> – FamilyFlow</title>
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body class="auth">

<span class="auth-blob auth-blob-pink" aria-hidden="true"></span>
<span class="auth-blob auth-blob-mint" aria-hidden="true"></span>

<header class="auth-header">
    <a href="/" class="auth-logo"><span class="auth-logo-mark"><?= $logoSvg ?></span> FamilyFlow</a>
    <a href="/" class="auth-back">&larr; Zur Startseite</a>
</header>

<main class="auth-main">
    <div class="auth-card">
        <?php if (!empty($authBadge)): ?>
            <span class="auth-badge"><?= htmlspecialchars($authBadge) ?></span>
        <?php endif; ?>
