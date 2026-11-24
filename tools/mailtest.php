<?php
// ---------------------------------------------------------------
// Testmail verschicken, um den Mailversand zu pruefen.
// Zeigt das komplette Gespraech mit dem Mailserver an (ohne Passwort).
//
// Ausfuehren (nur auf der Kommandozeile):
//   sudo -u www-data php tools/mailtest.php deine@adresse.at
// ---------------------------------------------------------------

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Nur auf der Kommandozeile ausfuehrbar.');
}

$to = $argv[1] ?? '';
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    exit("Bitte eine Empfaenger-Adresse angeben: php tools/mailtest.php deine@adresse.at\n");
}

require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$config = include __DIR__ . '/../src/config/mailconfig.php';
if (($config['driver'] ?? '') !== 'smtp') {
    exit("In src/config/mailconfig.php steht nicht 'driver' => 'smtp'.\n");
}

$host = $config['smtp_host'];
echo "Server:  $host -> IPv4 " . gethostbyname($host) . ", Port {$config['smtp_port']}\n";
echo "Login:   {$config['smtp_user']}\n";
echo "Passwort: " . (strlen($config['smtp_pass']) === 16 ? '16 Zeichen (passt fuer App-Passwort)' : strlen($config['smtp_pass']) . ' Zeichen (App-Passwort hat 16, ohne Leerzeichen!)') . "\n\n";

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = gethostbyname($host);
    $mail->SMTPOptions = ['ssl' => ['peer_name' => $host, 'verify_peer' => true, 'verify_peer_name' => true]];
    $mail->Port = (int) $config['smtp_port'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_user'];
    $mail->Password = $config['smtp_pass'];
    $mail->SMTPSecure = $mail->Port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Timeout = 15;
    $mail->CharSet = 'UTF-8';
    // Gespraech mit dem Server anzeigen (Passwort wird von PHPMailer nur verschluesselt/kodiert gezeigt)
    $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
    $mail->Debugoutput = function ($line) {
        // Login-Daten (Base64, also leicht rueckrechenbar) nicht anzeigen
        if (preg_match('/CLIENT -> SERVER: AUTH \w+/', $line, $m)) {
            $line = $m[0] . ' [versteckt]';
        } elseif (preg_match('/CLIENT -> SERVER: [A-Za-z0-9+\/=]{8,}\s*$/', $line)) {
            $line = 'CLIENT -> SERVER: [versteckt]';
        }
        echo '  ' . trim($line) . "\n";
    };

    $mail->setFrom($config['from_email'], $config['from_name'] ?? 'FamilyFlow');
    $mail->addAddress($to);
    $mail->Subject = 'FamilyFlow Testmail';
    $mail->Body = 'Wenn du das liest, funktioniert der Mailversand von FamilyFlow.';
    $mail->send();

    echo "\nERFOLG: Testmail an $to verschickt.\n";
} catch (Exception $e) {
    echo "\nFEHLER: " . $mail->ErrorInfo . "\n";
}
