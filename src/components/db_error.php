<?php
// Freundliche Fehlerseite, wenn die Datenbank nicht erreichbar ist (wird von Database.php eingebunden).
// Die technischen Details stehen NUR im Server-Log, nie im Browser.
// Bewusst ohne Datenbank-Zugriff - die ist ja gerade das Problem.

if (headers_sent()) {
    // Ein Teil der Seite (z.B. Kopfbereich) wurde schon ausgegeben -> nur eine Meldung anhaengen
    echo '<div style="max-width:440px;margin:24px auto;padding:16px 20px;border-radius:14px;'
        . 'background:#ffe4dc;color:#9a2d17;font-family:system-ui,Arial,sans-serif;">'
        . '<strong>Hoppla, da hakt gerade etwas.</strong><br>Bitte versuch es in ein paar Minuten nochmal.</div>';
    return;
}

// 503 = "Dienst vorübergehend nicht verfügbar"; Retry-After: in 5 Minuten nochmal versuchen
http_response_code(503);
header('Retry-After: 300');

$authTitle = 'Kurze Pause';
$authBadge = 'Gleich wieder da!';
include __DIR__ . '/auth_head.php';
?>

        <h1>Hoppla, da <em>hakt</em> gerade etwas.</h1>
        <p class="auth-intro">FamilyFlow ist im Moment nicht erreichbar. Deine Daten sind sicher – bitte versuch es in ein paar Minuten nochmal.</p>

        <form action="" method="GET">
            <button type="button" onclick="location.reload()">Nochmal versuchen</button>
        </form>

        <p class="auth-switch">Dauert es länger? Schreib uns an <a href="mailto:fam.flow2000@gmail.com">fam.flow2000@gmail.com</a></p>

<?php include __DIR__ . '/auth_footer.php'; ?>
