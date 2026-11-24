<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

// Verschickt Mails ueber SMTP (PHPMailer) oder legt sie zum Testen nur als Datei ab.
// Welche Variante, steht in src/config/mailconfig.php ('driver').
class Mailer {
    private array $config;

    public function __construct() {
        $configFile = __DIR__ . '/../config/mailconfig.php';
        $config = is_file($configFile) ? include $configFile : [];

        // Ohne Config-Datei: nichts verschicken, nur ablegen, und keine Bestaetigung verlangen
        $this->config = array_merge([
            'driver' => 'log',
            'base_url' => '',
            'require_verification' => false,
            'from_email' => 'noreply@familyflow.local',
            'from_name' => 'FamilyFlow',
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_user' => '',
            'smtp_pass' => '',
        ], is_array($config) ? $config : []);
    }

    // Feste Adresse aus der Config (NICHT aus $_SERVER['HTTP_HOST'] - den kann ein Angreifer
    // faelschen und sich so z.B. Passwort-Reset-Links auf seine eigene Seite schicken lassen)
    public function url($path) {
        return rtrim($this->config['base_url'], '/') . $path;
    }

    public function requiresVerification() {
        return (bool) $this->config['require_verification'];
    }

    // Gibt true zurueck, wenn die Mail raus ist (bzw. abgelegt wurde), sonst false
    public function send($toEmail, $toName, $subject, $html, $text) {
        if ($this->config['driver'] !== 'smtp') {
            return $this->writeToFile($toEmail, $subject, $html);
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            // IPv4 erzwingen: In manchen Netzen (z.B. am Pi) wird fuer smtp.gmail.com auch eine
            // IPv6-Adresse gefunden, IPv6 funktioniert dort aber nicht -> Verbindung haengt.
            // gethostbyname() liefert nur IPv4. Das TLS-Zertifikat wird trotzdem gegen den
            // echten Hostnamen geprueft (peer_name), die Verbindung bleibt also sicher.
            $host = $this->config['smtp_host'];
            $mail->Host = gethostbyname($host);
            $mail->SMTPOptions = ['ssl' => [
                'peer_name' => $host,
                'verify_peer' => true,
                'verify_peer_name' => true,
            ]];
            $mail->Port = (int) $this->config['smtp_port'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_user'];
            $mail->Password = $this->config['smtp_pass'];
            // Port 465 = verschluesselt von Anfang an, sonst (587) per STARTTLS
            $mail->SMTPSecure = $mail->Port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = 'UTF-8';
            // Nicht minutenlang haengen, wenn der Mailserver nicht erreichbar ist (Standard waeren 300 s)
            $mail->Timeout = 15;

            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = $text;

            $mail->send();
            return true;
        } catch (MailException $e) {
            // Details nur ins Server-Log, nie auf der Seite anzeigen
            error_log('FamilyFlow Mailversand fehlgeschlagen: ' . $mail->ErrorInfo);
            return false;
        }
    }

    // Einheitliche Mail mit Begruessung, Text und einem Button-Link.
    // Gibt [html, text] zurueck (Text-Version fuer Mailprogramme ohne HTML).
    public function buildButtonMail($firstname, $intro, $buttonText, $url, $outro) {
        $name = htmlspecialchars($firstname);
        $safeUrl = htmlspecialchars($url);

        $html = '<div style="font-family: Arial, sans-serif; max-width: 480px; margin: 0 auto; color: #333;">'
            . '<h2 style="color: #4f46e5;">FamilyFlow</h2>'
            . '<p>Hallo ' . $name . ',</p>'
            . '<p>' . htmlspecialchars($intro) . '</p>'
            . '<p style="margin: 28px 0;"><a href="' . $safeUrl . '" style="background: #4f46e5; color: #fff; padding: 12px 20px; border-radius: 6px; text-decoration: none;">' . htmlspecialchars($buttonText) . '</a></p>'
            . '<p style="font-size: 13px; color: #666;">Falls der Button nicht funktioniert, kopiere diesen Link in deinen Browser:<br>' . $safeUrl . '</p>'
            . '<p style="font-size: 13px; color: #666;">' . htmlspecialchars($outro) . '</p>'
            . '</div>';

        $text = "Hallo $firstname,\n\n$intro\n\n$buttonText:\n$url\n\n$outro\n";

        return [$html, $text];
    }

    // Test-Modus: Mail als HTML-Datei in storage/mails/ ablegen (Ordner ist per .htaccess gesperrt)
    private function writeToFile($toEmail, $subject, $html) {
        $dir = __DIR__ . '/../../storage/mails';
        if (!is_dir($dir) || !is_writable($dir)) {
            error_log('FamilyFlow: storage/mails ist nicht beschreibbar');
            return false;
        }
        $filename = date('Y-m-d_His') . '_' . bin2hex(random_bytes(4)) . '.html';
        $content = '<p><b>An:</b> ' . htmlspecialchars($toEmail) . '<br><b>Betreff:</b> ' . htmlspecialchars($subject) . '</p><hr>' . $html;
        return file_put_contents($dir . '/' . $filename, $content) !== false;
    }
}
