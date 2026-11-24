<?php
// Kopiere diese Datei nach mailconfig.php und trage deine Werte ein.
// mailconfig.php steht in der .gitignore (enthält das Mail-Passwort).

return [
    // 'log'  = Mails NICHT verschicken, sondern als Datei in storage/mails/ ablegen (zum Testen)
    // 'smtp' = Mails wirklich verschicken (z.B. über Gmail)
    'driver' => 'log',

    // Adresse, unter der FamilyFlow erreichbar ist, ohne / am Ende.
    // Daraus werden die Links in den Mails gebaut (lokal z.B. http://familyflow.test,
    // auf dem Pi z.B. http://192.168.1.50).
    'base_url' => 'http://familyflow.test',

    // Müssen neue User ihre E-Mail bestätigen, bevor sie sich einloggen können?
    // Auf false stellen, solange noch kein echter Mailversand eingerichtet ist.
    'require_verification' => true,

    'from_email' => 'familyflow.app@gmail.com',
    'from_name' => 'FamilyFlow',

    // Nur für 'smtp' nötig. Gmail: smtp.gmail.com, Port 587,
    // Passwort = App-Passwort (Google-Konto -> Sicherheit), NICHT das normale Passwort.
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_user' => 'familyflow.app@gmail.com',
    'smtp_pass' => '',
];
