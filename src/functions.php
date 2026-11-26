<?php

function initials($firstname, $lastname) {
    return mb_strtoupper(mb_substr($firstname, 0, 1) . mb_substr($lastname, 0, 1));
}

// Adresse eines hochgeladenen Bildes. Bilder laufen ueber image.php, das prueft,
// ob die eingeloggte Person das Bild sehen darf ($type: 'recipe' oder 'avatar').
function imageUrl($type, $filename) {
    return '/image.php?type=' . urlencode($type) . '&file=' . urlencode($filename);
}

// Avatar-HTML: Foto falls hochgeladen, sonst Initialen-Kreis als Fallback.
// $attributes wird roh in den Tag eingesetzt (z.B. 'id="menuBtn" class="avatar"').
function avatarHtml($user, $attributes = 'class="avatar"') {
    if (!empty($user->avatar)) {
        return '<img src="' . htmlspecialchars(imageUrl('avatar', $user->avatar)) . '" alt="" ' . $attributes . '>';
    }
    return '<span ' . $attributes . '>' . htmlspecialchars(initials($user->firstname, $user->lastname)) . '</span>';
}

// CSRF-Schutz: ein zufaelliger Token pro Session, der bei jedem Formular mitgeschickt
// und beim Verarbeiten geprueft wird. Eine fremde Seite kennt diesen Token nicht und
// kann daher keine Aktion in unserem Namen auslösen.
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

// Sternzeichen aus dem Geburtsdatum berechnen
function zodiacSign($birthdate) {
    if (!$birthdate) {
        return null;
    }

    $date = new DateTime($birthdate);
    $month = (int) $date->format('n');
    $day = (int) $date->format('j');
    $monthDay = $month * 100 + $day; // z.B. 15.März -> 315

    if ($monthDay >= 321 && $monthDay <= 419) return 'Widder';
    if ($monthDay >= 420 && $monthDay <= 520) return 'Stier';
    if ($monthDay >= 521 && $monthDay <= 620) return 'Zwillinge';
    if ($monthDay >= 621 && $monthDay <= 722) return 'Krebs';
    if ($monthDay >= 723 && $monthDay <= 822) return 'Löwe';
    if ($monthDay >= 823 && $monthDay <= 922) return 'Jungfrau';
    if ($monthDay >= 923 && $monthDay <= 1022) return 'Waage';
    if ($monthDay >= 1023 && $monthDay <= 1121) return 'Skorpion';
    if ($monthDay >= 1122 && $monthDay <= 1221) return 'Schütze';
    if ($monthDay >= 1222 || $monthDay <= 119) return 'Steinbock';
    if ($monthDay >= 120 && $monthDay <= 218) return 'Wassermann';
    return 'Fische'; // Rest: 19.2 - 20.3
}


// ---------------------------------------------------------------
// Bild-Upload: prueft, verkleinert, dreht richtig, entfernt EXIF/GPS
// und speichert als JPEG unter uploads/<ordner>/.
// Rueckgabe: Dateiname (bei Erfolg) oder null (kein Bild gewaehlt ODER Fehler).
// Bei einem Fehler steht die Meldung danach in $error.
// ---------------------------------------------------------------
function handleImageUpload($fileKey, $folder, $prefix, $maxSide, &$error) {
    $error = null;

    // Kein Bild ausgewaehlt -> kein Fehler, einfach nichts tun
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fileKey];

    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        $error = 'Das Bild ist zu groß für den Server (max. ' . ini_get('upload_max_filesize') . ').';
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $error = 'Das Hochladen ist fehlgeschlagen (Fehlercode ' . $file['error'] . ').';
        return null;
    }
    if ($file['size'] > 20 * 1024 * 1024) {
        $error = 'Das Bild ist zu groß (max. 20 MB).';
        return null;
    }
    if (!function_exists('imagecreatefromjpeg')) {
        $error = 'Bildverarbeitung ist am Server nicht installiert (PHP-Erweiterung GD fehlt).';
        return null;
    }

    // Echten Dateityp pruefen (nicht dem Dateinamen vertrauen)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);

    if ($mimeType === 'image/heic' || $mimeType === 'image/heif') {
        $error = 'HEIC-Fotos (iPhone) werden nicht unterstützt. Bitte in der Kamera "Maximale Kompatibilität" einstellen.';
        return null;
    }
    if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        $error = 'Nur JPG, PNG oder WebP erlaubt.';
        return null;
    }

    // Handyfotos sind riesig - beim Entpacken brauchen sie viel Arbeitsspeicher
    ini_set('memory_limit', '256M');

    if ($mimeType === 'image/jpeg') {
        $image = @imagecreatefromjpeg($file['tmp_name']);
    } elseif ($mimeType === 'image/png') {
        $image = @imagecreatefrompng($file['tmp_name']);
    } else {
        $image = @imagecreatefromwebp($file['tmp_name']);
    }

    if (!$image) {
        $error = 'Das Bild konnte nicht gelesen werden.';
        return null;
    }

    // Handys speichern Fotos oft "liegend" und merken sich die Drehung nur in den EXIF-Daten
    if ($mimeType === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        $orientation = $exif['Orientation'] ?? 1;
        if ($orientation == 3) {
            $image = imagerotate($image, 180, 0);
        } elseif ($orientation == 6) {
            $image = imagerotate($image, -90, 0);
        } elseif ($orientation == 8) {
            $image = imagerotate($image, 90, 0);
        }
    }

    // Verkleinern, sodass die laengere Seite hoechstens $maxSide Pixel hat
    $width = imagesx($image);
    $height = imagesy($image);
    $scale = min(1, $maxSide / max($width, $height));
    $newWidth = (int) round($width * $scale);
    $newHeight = (int) round($height * $scale);

    // Neues Bild mit weissem Hintergrund (falls PNG/WebP transparent war)
    $resized = imagecreatetruecolor($newWidth, $newHeight);
    imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));
    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imagedestroy($image);

    $targetDir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($targetDir) || !is_writable($targetDir)) {
        imagedestroy($resized);
        $error = 'Der Upload-Ordner ist am Server nicht beschreibbar.';
        return null;
    }

    // Zufaelliger, nicht erratbarer Dateiname
    $filename = $prefix . '_' . bin2hex(random_bytes(16)) . '.jpg';

    // Neu als JPEG speichern -> EXIF-Daten (inkl. GPS-Standort) sind dabei weg
    $saved = imagejpeg($resized, $targetDir . '/' . $filename, 82);
    imagedestroy($resized);

    if (!$saved) {
        $error = 'Das Bild konnte nicht gespeichert werden.';
        return null;
    }

    return $filename;
}

// Loescht ein hochgeladenes Bild wieder (z.B. altes Foto nach dem Ersetzen)
function deleteUploadedImage($folder, $filename) {
    if ($filename === null || $filename === '') {
        return;
    }
    $path = __DIR__ . '/../uploads/' . $folder . '/' . basename($filename);
    if (is_file($path)) {
        unlink($path);
    }
}

// Nur Ids uebernehmen, die wirklich zu Mitgliedern der Familie gehoeren
// (Formularwerte kann man im Browser beliebig veraendern)
function filterFamilyMemberIds($postedIds, $members) {
    $memberIds = array_map('intval', array_column($members, 'id'));
    $ids = array_map('intval', (array) $postedIds);
    return array_values(array_unique(array_intersect($ids, $memberIds)));
}

// "Für alle sichtbar" + Checkboxen -> Liste fuer setSharedWith() (Termine, Rezepte).
// Leeres Array = fuer alle sichtbar. Sonst ist die erstellende Person immer mit drin -
// so bleibt "niemand angekreuzt" wirklich privat, statt als leere Liste "fuer alle" zu bedeuten.
function sharedWithFromForm($sharedAll, $postedIds, $members, $ownerId) {
    if ($sharedAll) {
        return [];
    }
    $ids = filterFamilyMemberIds($postedIds, $members);
    $ids[] = (int) $ownerId;
    return array_values(array_unique($ids));
}

// Bestaetigungsmail (neu) verschicken - beim Registrieren und bei "Mail erneut senden".
// Der Link ist 24 Stunden gueltig. Gibt true zurueck, wenn die Mail raus ist.
function sendVerificationMail(PDO $pdo, $user) {
    require_once __DIR__ . '/classes/UserToken.php';
    require_once __DIR__ . '/classes/Mailer.php';

    $token = (new UserToken($pdo))->create($user->id, 'verify', 24 * 60);
    $mailer = new Mailer();
    $url = $mailer->url('/user/VerifyEmail.php?token=' . $token);

    [$html, $text] = $mailer->buildButtonMail(
        $user->firstname,
        'willkommen bei FamilyFlow! Bitte bestätige noch deine E-Mail-Adresse, damit du dich einloggen kannst.',
        'E-Mail bestätigen',
        $url,
        'Der Link ist 24 Stunden gültig. Falls du dich nicht bei FamilyFlow registriert hast, kannst du diese Mail einfach ignorieren.'
    );

    $sent = $mailer->send($user->email, $user->firstname . ' ' . $user->lastname, 'Bitte bestätige deine E-Mail-Adresse', $html, $text);
    if (!$sent) {
        // Nicht verschickter Link wird wieder geloescht, sonst blockiert der Spam-Schutz "erneut senden"
        (new UserToken($pdo))->deleteForUser($user->id, 'verify');
    }
    return $sent;
}

// Mail mit Link zum Passwort-Zuruecksetzen verschicken. Der Link ist 1 Stunde gueltig.
function sendPasswordResetMail(PDO $pdo, $user) {
    require_once __DIR__ . '/classes/UserToken.php';
    require_once __DIR__ . '/classes/Mailer.php';

    $token = (new UserToken($pdo))->create($user->id, 'reset', 60);
    $mailer = new Mailer();
    $url = $mailer->url('/user/ResetPassword.php?token=' . $token);

    [$html, $text] = $mailer->buildButtonMail(
        $user->firstname,
        'du hast ein neues Passwort für FamilyFlow angefordert. Klicke auf den Button, um ein neues Passwort festzulegen.',
        'Neues Passwort festlegen',
        $url,
        'Der Link ist 1 Stunde gültig. Falls du das nicht warst, kannst du diese Mail ignorieren - dein Passwort bleibt unverändert.'
    );

    $sent = $mailer->send($user->email, $user->firstname . ' ' . $user->lastname, 'Passwort zurücksetzen', $html, $text);
    if (!$sent) {
        (new UserToken($pdo))->deleteForUser($user->id, 'reset');
    }
    return $sent;
}

// Jemand will sich mit einer Adresse registrieren, die schon ein bestaetigtes Konto hat.
// Auf der Seite steht trotzdem dieselbe Meldung wie bei einer neuen Registrierung (sonst koennte
// jeder ausprobieren, welche Adressen registriert sind). Nur die echte Besitzerin erfaehrt per
// Mail, dass es das Konto schon gibt - mit Link zum Login und einem Link fuer ein neues Passwort.
function sendAlreadyRegisteredMail(PDO $pdo, $user) {
    require_once __DIR__ . '/classes/UserToken.php';
    require_once __DIR__ . '/classes/Mailer.php';

    $token = (new UserToken($pdo))->create($user->id, 'reset', 60);
    $mailer = new Mailer();
    $url = $mailer->url('/user/ResetPassword.php?token=' . $token);
    $loginUrl = $mailer->url('/user/UserLogin.php');

    [$html, $text] = $mailer->buildButtonMail(
        $user->firstname,
        'gerade wollte sich jemand mit deiner E-Mail-Adresse bei FamilyFlow registrieren. Du hast aber schon ein Konto! '
            . 'Du kannst dich einfach einloggen: ' . $loginUrl . ' - falls du dein Passwort vergessen hast, lege hier ein neues fest:',
        'Neues Passwort festlegen',
        $url,
        'Der Link ist 1 Stunde gültig. Falls du das nicht warst, kannst du diese Mail ignorieren - an deinem Konto ändert sich nichts.'
    );

    $sent = $mailer->send($user->email, $user->firstname . ' ' . $user->lastname, 'Du hast schon ein FamilyFlow-Konto', $html, $text);
    if (!$sent) {
        (new UserToken($pdo))->deleteForUser($user->id, 'reset');
    }
    return $sent;
}

// Info-Mail an die Betreiberin, wenn sich jemand neu registriert hat.
// Empfaenger = 'admin_email' in mailconfig.php (leer = keine Mail).
// Fehler hier duerfen die Registrierung nie stoppen, deshalb nur true/false zurueck.
function sendNewUserNotification($user) {
    require_once __DIR__ . '/classes/Mailer.php';

    $mailer = new Mailer();
    $to = $mailer->adminEmail();
    if ($to === '') {
        return false;
    }

    $name = $user->firstname . ' ' . $user->lastname;
    // Zeitzone ausdruecklich angeben - am Pi steht PHP sonst evtl. auf UTC (1 Stunde daneben)
    $time = (new DateTime('now', new DateTimeZone('Europe/Vienna')))->format('d.m.Y, H:i');

    $html = '<div style="font-family: Arial, sans-serif; color: #333;">'
        . '<h2 style="color: #4f46e5;">Neue Registrierung bei FamilyFlow</h2>'
        . '<p><b>Name:</b> ' . htmlspecialchars($name) . '<br>'
        . '<b>E-Mail:</b> ' . htmlspecialchars($user->email) . '<br>'
        . '<b>Zeit:</b> ' . $time . '</p>'
        . '<p style="font-size: 13px; color: #666;">Die E-Mail-Adresse ist noch nicht bestätigt.</p>'
        . '</div>';
    $text = "Neue Registrierung bei FamilyFlow\n\nName: $name\nE-Mail: {$user->email}\nZeit: $time\n";

    return $mailer->send($to, 'FamilyFlow', 'Neue Registrierung: ' . $name, $html, $text);
}

// Meldung fuer die Login-Sperre, z.B. "... in 5 Minuten wieder versuchen."
// Mit Link zu "Passwort vergessen" (Text stammt nur aus unserem Code, deshalb HTML erlaubt).
function lockMessage($seconds) {
    $minutes = max(1, (int) ceil($seconds / 60));
    $when = $minutes === 1 ? 'einer Minute' : $minutes . ' Minuten';
    return 'Zu viele fehlgeschlagene Anmeldeversuche. Aus Sicherheitsgründen kannst du es erst in '
        . $when . ' wieder versuchen. <a href="/user/ForgotPassword.php">Passwort vergessen?</a>';
}

// Ist die eingeloggte Person die Betreiberin der App? (z.B. fuer die Feedback-Uebersicht)
// Verglichen wird mit 'admin_email' aus mailconfig.php - so steht keine private
// Adresse im Code. Leere Einstellung = niemand ist Betreiberin.
function isAppAdmin(PDO $pdo) {
    if (!isset($_SESSION['uid'])) {
        return false;
    }
    require_once __DIR__ . '/classes/Mailer.php';
    require_once __DIR__ . '/classes/User.php';

    $adminEmail = (new Mailer())->adminEmail();
    if ($adminEmail === '') {
        return false;
    }
    $me = (new User($pdo))->findById($_SESSION['uid']);
    return $me && strcasecmp($me->email, $adminEmail) === 0;
}
