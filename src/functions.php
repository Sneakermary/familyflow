<?php

function initials($firstname, $lastname) {
    return mb_strtoupper(mb_substr($firstname, 0, 1) . mb_substr($lastname, 0, 1));
}

// Avatar-HTML: Foto falls hochgeladen, sonst Initialen-Kreis als Fallback.
// $attributes wird roh in den Tag eingesetzt (z.B. 'id="menuBtn" class="avatar"').
function avatarHtml($user, $attributes = 'class="avatar"') {
    if (!empty($user->avatar)) {
        return '<img src="/uploads/avatars/' . htmlspecialchars($user->avatar) . '" alt="" ' . $attributes . '>';
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
