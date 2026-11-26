<?php
// Startseite (oeffentlich), Entwurf 2: warm und verspielt.
// Eigenes HTML-Geruest mit eigener landing.css, damit die App-Styles
// (style.css, Themes) die Startseite nicht beeinflussen.
// Hell als Standard, dunkel automatisch, wenn das Geraet auf Dunkelmodus steht.
require_once __DIR__ . '/src/components/session.php';

$loggedIn = isset($_SESSION['uid']);
$startLink = $loggedIn ? '/family/SelectFamily.php' : '/user/UserRegister.php';

// Smiley-Logo (oben und unten verwendet)
$logoSvg = require __DIR__ . '/src/components/logo.php';

// Zeichnet ein Smiley-Gesicht fuer das Haus (Mittelpunkt x/y, Radius r, Farbe)
function lpFace(float $x, float $y, float $r, string $color, bool $cheeks = false): string
{
    $eyeY = $y - $r * 0.15;
    $svg = "<circle cx=\"$x\" cy=\"$y\" r=\"$r\" fill=\"$color\"/>";
    $svg .= '<circle cx="' . ($x - $r * 0.32) . "\" cy=\"$eyeY\" r=\"" . ($r * 0.09) . '" fill="#352a4a"/>';
    $svg .= '<circle cx="' . ($x + $r * 0.32) . "\" cy=\"$eyeY\" r=\"" . ($r * 0.09) . '" fill="#352a4a"/>';
    $svg .= '<path d="M' . ($x - $r * 0.35) . ' ' . ($y + $r * 0.2) . ' Q' . $x . ' ' . ($y + $r * 0.6) . ' ' . ($x + $r * 0.35) . ' ' . ($y + $r * 0.2)
        . '" fill="none" stroke="#352a4a" stroke-width="' . ($r * 0.1) . '" stroke-linecap="round"/>';
    if ($cheeks) {
        $svg .= '<circle cx="' . ($x - $r * 0.6) . '" cy="' . ($y + $r * 0.2) . '" r="' . ($r * 0.15) . '" fill="#f28b8b" opacity=".6"/>';
        $svg .= '<circle cx="' . ($x + $r * 0.6) . '" cy="' . ($y + $r * 0.2) . '" r="' . ($r * 0.15) . '" fill="#f28b8b" opacity=".6"/>';
    }
    return $svg;
}

// Die 6 Kacheln: Titel, Text, Farb-Klasse, Icon (SVG-Pfade, 24x24)
$features = [
    ['Gemeinsamer Kalender', 'Jeder weiß, wer wann wohin muss – ohne Nachfragen.', 'mint',
        '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/>'],
    ['Einkaufslisten', 'Alle schreiben mit, im Laden wird abgehakt.', 'yellow',
        '<path d="M3 4h2l2.4 11h10.2L20 8H6.2"/><circle cx="9" cy="19.5" r="1.5"/><circle cx="17" cy="19.5" r="1.5"/>'],
    ['Aufgaben für alle', 'Fair verteilt, mit einem Häkchen und viel Lob.', 'pink',
        '<rect x="4" y="3" width="16" height="18" rx="3"/><path d="m8.5 12 2.5 2.5 4.5-5"/>'],
    ['Dein Familien-Dashboard', 'Das Wichtigste vom Tag gleich auf der Startseite.', 'lilac',
        '<path d="M4 11 12 4l8 7v9a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z"/>'],
    ['Alle sind dabei', 'Ein Familien-Account, jeder mit eigenem Zugang.', 'peach',
        '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17" cy="9" r="2.5"/><path d="M17 14c2.5 0 4.5 1.8 4.5 4.5"/>'],
    ['Sicher & privat', 'Ihr bestimmt selbst, wer welchen Termin und welches Rezept sieht.', 'sky',
        '<path d="M12 3 4.5 6v5.5c0 4.5 3.2 8.2 7.5 9.5 4.3-1.3 7.5-5 7.5-9.5V6z"/><path d="m9 12 2.2 2.2L15.5 10"/>'],
];

$steps = [
    ['Familie anlegen', 'Account erstellen und einen Namen für eure Familie wählen.'],
    ['Alle einladen', 'Partner, Kinder und Großeltern kommen mit an Bord.'],
    ['Gemeinsam loslegen', 'Termine, Einkäufe und Aufgaben wandern ins Dashboard.'],
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>FamilyFlow – Zuhause läuft's einfach gemeinsam besser</title>
    <meta name="description" content="Mit FamilyFlow haben alle Termine, Einkäufe und Aufgaben im Blick – ganz entspannt und für jeden verständlich.">
    <link rel="stylesheet" href="/assets/css/landing.css">
</head>
<body class="lp">

<header class="lp-header">
    <div class="lp-wrap">
        <div class="lp-nav">
            <a href="/" class="lp-logo"><span class="lp-logo-mark"><?= $logoSvg ?></span> FamilyFlow</a>
            <nav class="lp-links">
                <a href="#funktionen" class="lp-nav-anchor">Was drin steckt</a>
                <a href="#ablauf" class="lp-nav-anchor">So geht's</a>
                <?php if ($loggedIn): ?>
                    <a href="/family/SelectFamily.php" class="lp-btn lp-btn-red lp-btn-small">Zur App</a>
                <?php else: ?>
                    <a href="/user/UserLogin.php">Anmelden</a>
                    <a href="/user/UserRegister.php" class="lp-btn lp-btn-red lp-btn-small">Los geht's</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</header>

<main>
    <!-- HERO -->
    <section class="lp-hero">
        <span class="lp-blob lp-blob-pink" aria-hidden="true"></span>
        <span class="lp-blob lp-blob-mint" aria-hidden="true"></span>

        <div class="lp-wrap lp-hero-grid">
            <div class="lp-hero-text">
                <span class="lp-badge">Hallo, liebe Familie!</span>
                <h1>Zuhause läuft's einfach <em>gemeinsam</em> besser.</h1>
                <p class="lp-lead">Mit FamilyFlow haben alle Termine, Einkäufe und Aufgaben im Blick – ganz entspannt und für jeden verständlich.</p>
                <div class="lp-actions">
                    <a href="<?= $startLink ?>" class="lp-btn lp-btn-red lp-btn-glow"><?= $loggedIn ? 'Zur App' : 'Familie anlegen' ?></a>
                    <a href="#ablauf" class="lp-btn lp-btn-outline">Wie funktioniert's?</a>
                </div>
                <ul class="lp-checks">
                    <li>Für Groß &amp; Klein</li>
                    <li>Schnell eingerichtet</li>
                </ul>
            </div>

            <!-- Bild: Haus mit Smiley-Familie (nur Deko, fuer Screenreader ausgeblendet) -->
            <div class="lp-visual" aria-hidden="true">
                <div class="lp-visual-card">
                    <svg class="lp-house" viewBox="0 0 320 230">
                        <circle cx="262" cy="92" r="24" fill="#fdc84b"/>
                        <path d="M28 104 160 30l132 74z" fill="#e8553a" stroke="#e8553a" stroke-width="14" stroke-linejoin="round"/>
                        <rect x="48" y="112" width="224" height="104" rx="18" fill="#ffd3dc"/>
                        <rect x="66" y="122" width="38" height="34" rx="9" fill="#ffffff"/>
                        <rect x="216" y="122" width="38" height="34" rx="9" fill="#ffffff"/>
                        <rect x="138" y="138" width="44" height="78" rx="20" fill="#fdc84b"/>
                        <circle cx="173" cy="178" r="3" fill="#352a4a"/>
                        <?= lpFace(96, 172, 24, '#9ed9d8', true) ?>
                        <?= lpFace(124, 196, 16, '#c9bff7') ?>
                        <?= lpFace(198, 194, 16, '#fdd46a') ?>
                        <?= lpFace(236, 168, 24, '#ffb39d', true) ?>
                    </svg>
                    <div class="lp-tag lp-tag-mint">
                        <small>HEUTE · 19:00</small>
                        <strong>Pizza-Abend zu Hause</strong>
                    </div>
                    <div class="lp-tag lp-tag-yellow">
                        <small>EINKAUF</small>
                        <strong>Noch 3 Dinge offen</strong>
                    </div>
                    <div class="lp-toast">
                        <span class="lp-toast-check">✓</span> Lena: Zimmer aufgeräumt!
                    </div>
                </div>
            </div>
        </div>

        <svg class="lp-wave" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0 30C240 0 480 0 720 22s480 38 720 8V60H0z"/>
        </svg>
    </section>

    <!-- FUNKTIONEN -->
    <section id="funktionen" class="lp-features">
        <div class="lp-wrap">
            <h2>Alles, was euer Alltag braucht.</h2>
            <p class="lp-sub">Kleine Helfer für große und kleine Familienmomente – damit mehr Zeit für das Schöne bleibt.</p>

            <div class="lp-tiles">
                <?php foreach ($features as [$title, $text, $color, $icon]): ?>
                    <article class="lp-tile lp-tile-<?= $color ?>">
                        <span class="lp-tile-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icon ?></svg>
                        </span>
                        <h3><?= $title ?></h3>
                        <p><?= $text ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SO GEHT'S -->
    <section id="ablauf" class="lp-steps">
        <div class="lp-wrap">
            <h2>So einfach geht's.</h2>
            <p class="lp-sub">In drei kleinen Schritten seid ihr startklar.</p>

            <ol class="lp-step-list">
                <?php foreach ($steps as $i => [$title, $text]): ?>
                    <li class="lp-step">
                        <span class="lp-step-num lp-step-num-<?= $i + 1 ?>"><?= $i + 1 ?></span>
                        <h3><?= $title ?></h3>
                        <p><?= $text ?></p>
                    </li>
                <?php endforeach; ?>
            </ol>

            <!-- AUFRUF -->
            <div class="lp-cta">
                <span class="lp-cta-circle lp-cta-circle-1" aria-hidden="true"></span>
                <span class="lp-cta-circle lp-cta-circle-2" aria-hidden="true"></span>
                <h2>Willkommen in eurer Familien-Zentrale!</h2>
                <p>Legt los und macht den Alltag ein bisschen leichter – für alle zu Hause.</p>
                <div class="lp-actions lp-actions-center">
                    <?php if ($loggedIn): ?>
                        <a href="/family/SelectFamily.php" class="lp-btn lp-btn-dark">Zur App</a>
                    <?php else: ?>
                        <a href="/user/UserRegister.php" class="lp-btn lp-btn-dark">Jetzt kostenlos starten</a>
                        <a href="/user/UserLogin.php" class="lp-btn lp-btn-white">Anmelden</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="lp-footer">
    <div class="lp-wrap lp-footer-row">
        <div class="lp-footer-brand">
            <a href="/" class="lp-logo lp-logo-small"><span class="lp-logo-mark"><?= $logoSvg ?></span> FamilyFlow</a>
            <span>© <?= date('Y') ?> · Mit Liebe gemacht</span>
        </div>
        <nav class="lp-footer-links">
            <a href="/impressum.php">Impressum</a>
            <a href="/datenschutz.php">Datenschutz</a>
            <a href="mailto:fam.flow2000@gmail.com">Kontakt</a>
        </nav>
    </div>
</footer>

</body>
</html>
