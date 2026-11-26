<?php
// Datenschutzerklaerung (oeffentlich), Art. 13 DSGVO.
// Name/Anschrift der Verantwortlichen kommen aus src/config/impressum.php (nicht auf GitHub).
// WICHTIG: Wenn die App neue Daten speichert oder neue Dienste nutzt, diese Seite anpassen!
require_once __DIR__ . '/src/components/session.php';

$configFile = __DIR__ . '/src/config/impressum.php';
$imp = is_file($configFile) ? include $configFile : [];
$val = fn($key) => htmlspecialchars(trim($imp[$key] ?? ''));

$contactEmail = $val('email') !== '' ? $val('email') : 'fam.flow2000@gmail.com';

$authTitle = 'Datenschutz';
$authBadge = 'Deine Daten gehören dir!';
$authWide = true;
include __DIR__ . '/src/components/auth_head.php';
?>

<div class="legal">
    <h1>Datenschutzerklärung</h1>
    <p class="auth-intro">Stand: November 2026 · Informationen nach Art. 13 Datenschutz-Grundverordnung (DSGVO)</p>

    <div class="legal-summary">
        <strong>Kurz gesagt:</strong> FamilyFlow speichert nur, was für die App nötig ist. Es gibt <strong>keine Werbung, kein Tracking, keine Analyse-Tools</strong>, und deine Daten werden <strong>nicht verkauft</strong>. Was du in der App einträgst, sehen nur die Mitglieder deiner Familie, mit denen du es teilst.
    </div>

    <h2>1. Verantwortliche</h2>
    <p>
        <?php if ($val('name') !== ''): ?>
            <?= $val('name') ?><br>
            <?= $val('street') !== '' ? $val('street') . '<br>' : '' ?>
            <?= $val('city') !== '' ? $val('city') . '<br>' : '' ?>
        <?php else: ?>
            Die Betreiberin von FamilyFlow (Angaben siehe <a href="/impressum.php">Impressum</a>)<br>
        <?php endif; ?>
        E-Mail: <a href="mailto:<?= $contactEmail ?>"><?= $contactEmail ?></a>
    </p>
    <p>Für alle Fragen zum Datenschutz und für deine Rechte (siehe Punkt 9) erreichst du uns unter dieser E-Mail-Adresse.</p>

    <h2>2. Besuch der Website</h2>
    <p>Bei jedem Aufruf speichert der Server automatisch: IP-Adresse, Datum und Uhrzeit, aufgerufene Seite, Browser und Betriebssystem sowie die Seite, von der du kommst (Server-Logs).</p>
    <p><strong>Zweck:</strong> technischer Betrieb, Fehlersuche und Schutz vor Angriffen. <strong>Rechtsgrundlage:</strong> berechtigtes Interesse (Art. 6 Abs. 1 lit. f DSGVO). <strong>Speicherdauer:</strong> Die Logs werden täglich gewechselt und nach höchstens 15 Tagen automatisch gelöscht.</p>

    <h2>3. Dein Konto</h2>
    <p>Bei der Registrierung speichern wir <strong>Vorname, Nachname, E-Mail-Adresse</strong> und dein <strong>Passwort</strong> – das Passwort nur als nicht umkehrbarer Hash, niemand kann es im Klartext sehen. Außerdem speichern wir, wann du deine E-Mail-Adresse bestätigt hast.</p>
    <p>Freiwillig kannst du ein <strong>Profilbild</strong>, dein <strong>Geburtsdatum</strong> (wird deiner Familie als Geburtstag im Kalender angezeigt), eine <strong>Kalenderfarbe</strong> und ein <strong>Layout</strong> angeben.</p>
    <p><strong>Zweck:</strong> Bereitstellung der App. <strong>Rechtsgrundlage:</strong> Vertrag bzw. Nutzungsverhältnis (Art. 6 Abs. 1 lit. b DSGVO).</p>

    <h2>4. Inhalte in der App</h2>
    <p>Gespeichert werden die Inhalte, die du und deine Familie anlegen: <strong>Familien und Einladungen, Termine, Listen, Rezepte (mit Fotos), Aufgaben, Notizen und Spitznamen zu Familienmitgliedern sowie Benachrichtigungen</strong>.</p>
    <ul>
        <li>Sichtbar sind diese Inhalte nur für Mitglieder deiner Familie – und nur für die, mit denen du sie teilst. Als „privat“ markierte Inhalte sieht nur ihre Erstellerin bzw. ihr Ersteller.</li>
        <li>Bei hochgeladenen Fotos entfernen wir automatisch Standort (GPS) und Kameradaten.</li>
        <li>Bitte trag zu anderen Personen nur ein, was für den Familienalltag nötig ist, und möglichst keine besonders sensiblen Daten wie Gesundheitsangaben.</li>
    </ul>
    <p><strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. b DSGVO.</p>

    <h2>5. E-Mails</h2>
    <p>Wir schicken dir E-Mails, die für dein Konto nötig sind: Bestätigung deiner E-Mail-Adresse, Link zum Zurücksetzen des Passworts und Hinweise zu deinem Konto. Werbe-E-Mails oder Newsletter verschicken wir nicht. Verschickt werden die Mails über Google (siehe Punkt 7).</p>
    <p>Wenn sich jemand neu registriert, erhält die Betreiberin eine kurze Info-Mail mit Name und E-Mail-Adresse, um neue Konten im Blick zu behalten und Missbrauch zu erkennen (Art. 6 Abs. 1 lit. f DSGVO).</p>

    <h2>6. Sicherheit, Rückmeldungen, Cookies</h2>
    <ul>
        <li><strong>Login-Schutz:</strong> Um das Erraten von Passwörtern zu verhindern, merken wir uns fehlgeschlagene Anmeldeversuche – E-Mail-Adresse und IP-Adresse nur als Hash, also nicht lesbar – und löschen sie nach 24 Stunden (Art. 6 Abs. 1 lit. f DSGVO).</li>
        <li><strong>Links in E-Mails</strong> (Bestätigung, Passwort) gelten nur einmal und laufen nach 1 bzw. 24 Stunden ab.</li>
        <li><strong>Rückmeldungen:</strong> Wenn du über „Wünsche, Beschwerden und Anregungen“ Feedback gibst, speichern wir deinen Text zusammen mit deinem Konto, um die App zu verbessern (Art. 6 Abs. 1 lit. f DSGVO).</li>
        <li><strong>Cookies:</strong> Wir verwenden nur ein einziges, technisch notwendiges Cookie, das dich nach dem Login angemeldet hält (Sitzungs-Cookie). Es wird beim Schließen des Browsers gelöscht. Unser Dienstleister Cloudflare kann zum Schutz vor Angriffen ebenfalls technisch notwendige Cookies setzen. Tracking- oder Werbe-Cookies gibt es nicht – deshalb brauchen wir auch kein Cookie-Banner.</li>
    </ul>

    <h2>7. Dienstleister</h2>
    <p>Wir arbeiten mit wenigen Dienstleistern, die Daten nur in unserem Auftrag verarbeiten:</p>
    <table class="legal-table">
        <tr><th>Dienstleister</th><th>Wofür</th><th>Welche Daten</th></tr>
        <tr><td>Eigener Server in Österreich</td><td>Betrieb der App und der Datenbank</td><td>alle oben genannten Daten</td></tr>
        <tr><td>Cloudflare, Inc. (USA)</td><td>sichere Verbindung (HTTPS), Schutz vor Angriffen, Weiterleitung zum Server</td><td>IP-Adresse, aufgerufene Seiten, technische Daten</td></tr>
        <tr><td>Cloudflare R2 (Speicherort EU)</td><td>verschlüsselte Sicherungskopien (Backups)</td><td>Datenbank und Fotos – vor dem Hochladen verschlüsselt, Cloudflare kann sie nicht lesen</td></tr>
        <tr><td>Google LLC / Google Ireland Ltd. (Gmail)</td><td>Versand der E-Mails</td><td>E-Mail-Adresse, Name, Inhalt der Mail</td></tr>
    </table>
    <p>Cloudflare und Google haben ihren Sitz (auch) in den USA. Beide sind nach dem <strong>EU-US Data Privacy Framework</strong> zertifiziert; für die USA gilt damit ein Angemessenheitsbeschluss der EU-Kommission (Art. 45 DSGVO).</p>

    <h2>8. Wie lange wir Daten speichern</h2>
    <table class="legal-table">
        <tr><th>Daten</th><th>Speicherdauer</th></tr>
        <tr><td>Konto und Inhalte</td><td>bis du dein Konto löschst bzw. die Löschung verlangst</td></tr>
        <tr><td>Server-Logs</td><td>höchstens 15 Tage</td></tr>
        <tr><td>Fehlgeschlagene Anmeldeversuche</td><td>24 Stunden</td></tr>
        <tr><td>Links in E-Mails</td><td>1 Stunde (Passwort) bzw. 24 Stunden (Bestätigung)</td></tr>
        <tr><td>Backups</td><td>bis zu 6 Monate – gelöschte Daten verschwinden spätestens dann auch aus den Sicherungskopien</td></tr>
    </table>

    <h2>9. Deine Rechte</h2>
    <p>Du hast jederzeit das Recht auf:</p>
    <ul>
        <li><strong>Auskunft</strong> – welche Daten wir über dich speichern (Art. 15 DSGVO)</li>
        <li><strong>Berichtigung</strong> falscher Daten (Art. 16) – vieles kannst du direkt in deinem Profil ändern</li>
        <li><strong>Löschung</strong> deiner Daten (Art. 17)</li>
        <li><strong>Einschränkung</strong> der Verarbeitung (Art. 18)</li>
        <li><strong>Datenübertragbarkeit</strong> – deine Daten in einem gängigen Format (Art. 20)</li>
        <li><strong>Widerspruch</strong> gegen Verarbeitungen aufgrund berechtigter Interessen (Art. 21)</li>
    </ul>
    <p>Schreib uns dafür einfach an <a href="mailto:<?= $contactEmail ?>"><?= $contactEmail ?></a>. Wir antworten innerhalb eines Monats. Dein Konto löschen wir auf Wunsch vollständig.</p>
    <p>Wenn du meinst, dass wir mit deinen Daten nicht richtig umgehen, kannst du dich bei der <strong>Österreichischen Datenschutzbehörde</strong> beschweren: Barichgasse 40–42, 1030 Wien, <a href="mailto:dsb@dsb.gv.at">dsb@dsb.gv.at</a>, <a href="https://www.dsb.gv.at" rel="noopener">www.dsb.gv.at</a>.</p>

    <h2>10. Kinder</h2>
    <p>Ein eigenes Konto dürfen Personen ab 14 Jahren anlegen. Für Kinder unter 14 Jahren ist die Zustimmung der Eltern bzw. Erziehungsberechtigten nötig (§ 4 Abs. 4 Datenschutzgesetz).</p>

    <h2>11. Datensicherheit</h2>
    <p>Alle Verbindungen sind verschlüsselt (HTTPS). Passwörter werden nur als Hash gespeichert, Formulare sind gegen Fälschung geschützt, und Sicherungskopien werden verschlüsselt. Der Server ist nicht direkt aus dem Internet erreichbar, sondern nur über einen gesicherten Tunnel.</p>

    <h2>12. Änderungen</h2>
    <p>Wenn sich die App oder die Rechtslage ändert, passen wir diese Datenschutzerklärung an. Es gilt immer die hier veröffentlichte Fassung.</p>
</div>

<?php include __DIR__ . '/src/components/auth_footer.php'; ?>
