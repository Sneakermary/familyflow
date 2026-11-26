<?php
// Impressum (oeffentlich): Angaben nach § 5 E-Commerce-Gesetz und Offenlegung nach § 25 Mediengesetz.
// Name und Adresse stehen NICHT im Code, sondern in src/config/impressum.php (nicht auf GitHub).
// Vorlage: src/config/impressum.example.php
require_once __DIR__ . '/src/components/session.php';

$configFile = __DIR__ . '/src/config/impressum.php';
$imp = is_file($configFile) ? include $configFile : [];

// Kurzer Helfer: Wert aus der Konfiguration, sicher fuer HTML
function imp($imp, $key) {
    return htmlspecialchars(trim($imp[$key] ?? ''));
}

$hasData = imp($imp, 'name') !== '' && imp($imp, 'street') !== '';
$hasTrade = imp($imp, 'trade') !== '';

$authTitle = 'Impressum';
$authBadge = 'Wer steckt dahinter?';
$authWide = true;
include __DIR__ . '/src/components/auth_head.php';
?>

<div class="legal">
    <h1>Impressum</h1>
    <p class="auth-intro">Informationen nach § 5 E-Commerce-Gesetz (ECG) und Offenlegung nach § 25 Mediengesetz (MedienG)</p>

    <?php if (!$hasData): ?>
        <div class="flash flashError">Die Angaben im Impressum werden gerade eingerichtet.</div>
    <?php else: ?>
        <h2>Medieninhaberin und Betreiberin</h2>
        <p>
            <?= imp($imp, 'name') ?><br>
            <?= imp($imp, 'street') ?><br>
            <?= imp($imp, 'city') ?><?= imp($imp, 'country') !== '' ? '<br>' . imp($imp, 'country') : '' ?>
        </p>

        <h2>Kontakt</h2>
        <p>
            E-Mail: <a href="mailto:<?= imp($imp, 'email') ?>"><?= imp($imp, 'email') ?></a>
            <?php if (imp($imp, 'phone') !== ''): ?>
                <br>Telefon: <?= imp($imp, 'phone') ?>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <h2>Unternehmensgegenstand</h2>
    <p>Entwicklung und Betrieb der Web-App FamilyFlow – ein Organizer für den Familienalltag mit gemeinsamem Kalender, Listen, Aufgaben und Rezepten.</p>

    <?php if ($hasTrade): ?>
        <h2>Gewerbe</h2>
        <p>
            Gewerbewortlaut: <?= imp($imp, 'trade') ?>
            <?php if (imp($imp, 'authority') !== ''): ?><br>Gewerbebehörde: <?= imp($imp, 'authority') ?><?php endif; ?>
            <?php if (imp($imp, 'chamber') !== ''): ?><br>Mitglied der: <?= imp($imp, 'chamber') ?><?php endif; ?>
            <?php if (imp($imp, 'vat_id') !== ''): ?><br>UID-Nummer: <?= imp($imp, 'vat_id') ?><?php endif; ?>
            <?php if (imp($imp, 'company_reg') !== ''): ?><br>Firmenbuchnummer: <?= imp($imp, 'company_reg') ?><?php endif; ?>
            <br>Anwendbare Rechtsvorschriften: Gewerbeordnung, abrufbar unter <a href="https://www.ris.bka.gv.at" rel="noopener">www.ris.bka.gv.at</a>
        </p>
    <?php endif; ?>

    <h2>Grundlegende Richtung (Blattlinie)</h2>
    <p>Information über die Web-App FamilyFlow sowie deren Bereitstellung für Familien.</p>

    <h2>Haftung für Inhalte und Links</h2>
    <p>Die Inhalte dieser Website wurden mit größter Sorgfalt erstellt. Für die Richtigkeit, Vollständigkeit und Aktualität kann dennoch keine Gewähr übernommen werden. Für Inhalte externer Websites, auf die verlinkt wird, sind ausschließlich deren Betreiber verantwortlich. Sollten rechtswidrige Inhalte auf verlinkten Seiten bekannt werden, wird der Link umgehend entfernt.</p>

    <h2>Inhalte der Nutzerinnen und Nutzer</h2>
    <p>Termine, Listen, Rezepte, Fotos und andere Inhalte innerhalb der App werden von den jeweiligen Nutzerinnen und Nutzern selbst erstellt und sind nur für ihre Familie sichtbar. Wird uns ein rechtswidriger Inhalt gemeldet, entfernen wir ihn umgehend.</p>

    <h2>Urheberrecht</h2>
    <p>Texte, Gestaltung und Grafiken dieser Website (einschließlich Logo) sind urheberrechtlich geschützt. Eine Verwendung außerhalb der gesetzlichen Ausnahmen ist nur mit vorheriger Zustimmung erlaubt.</p>

    <h2>Datenschutz</h2>
    <p>Informationen zur Verarbeitung personenbezogener Daten findest du in der <a href="/datenschutz.php">Datenschutzerklärung</a>.</p>
</div>

<?php include __DIR__ . '/src/components/auth_footer.php'; ?>
