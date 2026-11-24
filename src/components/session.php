<?php
// Session sicher starten - wird von guard.php und allen Seiten ohne Login-Pflicht
// (Login, Registrieren, Passwort vergessen, ...) statt session_start() eingebunden.

if (session_status() === PHP_SESSION_NONE) {
    // Laeuft die Seite gerade ueber HTTPS? Direkt (Apache mit Zertifikat) oder ueber
    // Cloudflare Tunnel - dort kommt die Anfrage intern als http an, Cloudflare meldet
    // das Original-Protokoll aber im Header X-Forwarded-Proto.
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,          // Cookie gilt bis der Browser geschlossen wird
        'path' => '/',
        'secure' => $isHttps,     // bei HTTPS: Cookie nie ueber unverschluesseltes http schicken
        'httponly' => true,       // JavaScript kommt nicht an das Cookie (Schutz bei XSS)
        'samesite' => 'Lax',      // fremde Seiten koennen keine Formulare mit deinem Login abschicken
    ]);
    // Nur Session-Ids akzeptieren, die der Server selbst vergeben hat
    ini_set('session.use_strict_mode', '1');

    session_start();
}
