<?php
// Wird von tools/backup.sh aufgerufen, wenn das Backup fehlschlaegt.
// Schickt eine kurze Mail an admin_email aus mailconfig.php.
//   php tools/backup_failed_mail.php "Text"

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Nur auf der Kommandozeile ausfuehrbar.');
}

require_once __DIR__ . '/../src/classes/Mailer.php';

$reason = $argv[1] ?? 'unbekannter Fehler';
$mailer = new Mailer();
$to = $mailer->adminEmail();

if ($to === '') {
    fwrite(STDERR, "Keine admin_email in mailconfig.php - keine Mail verschickt.\n");
    exit(1);
}

$html = '<div style="font-family: Arial, sans-serif; color: #333;">'
    . '<h2 style="color: #b42318;">FamilyFlow: Backup fehlgeschlagen</h2>'
    . '<p>' . htmlspecialchars($reason) . '</p>'
    . '<p>Details am Pi: <code>sudo journalctl -u familyflow-backup -n 50</code></p>'
    . '</div>';
$text = "FamilyFlow: Backup fehlgeschlagen\n\n$reason\n\nDetails am Pi: sudo journalctl -u familyflow-backup -n 50\n";

exit($mailer->send($to, 'FamilyFlow', 'Backup fehlgeschlagen', $html, $text) ? 0 : 1);
