<?php
// Liefert hochgeladene Bilder (Rezeptfotos, Profilbilder) NUR an berechtigte Personen aus.
// Der Ordner uploads/ ist fuer direkte Aufrufe gesperrt (uploads/.htaccess bzw. Apache-Config am Pi).
//
//   /image.php?type=recipe&file=recipe_....jpg   -> nur wer das Rezept sehen darf
//   /image.php?type=avatar&file=avatar_....jpg   -> nur man selbst, die eigene Familie
//                                                   und bei offener Einladung
// Kein Zugriff -> 404 (verraet nicht, ob es das Bild gibt).
require_once __DIR__ . '/src/components/session.php';
require_once __DIR__ . '/src/classes/Database.php';
require_once __DIR__ . '/src/classes/Recipe.php';

function notFound() {
    http_response_code(404);
    exit();
}

if (!isset($_SESSION['uid'])) {
    notFound();
}
$uid = (int) $_SESSION['uid'];

$type = $_GET['type'] ?? '';
$file = $_GET['file'] ?? '';

// Nur einfache Dateinamen mit Bild-Endung - kein "../" o.ae.
if (!preg_match('/^[A-Za-z0-9_.\-]+\.(jpe?g|png|webp|gif)$/i', $file) || str_contains($file, '..')) {
    notFound();
}

$folders = ['recipe' => 'recipes', 'avatar' => 'avatars'];
if (!isset($folders[$type])) {
    notFound();
}

$pdo = (new Database)->connect();
$allowed = false;

if ($type === 'recipe') {
    // Zu welchem Rezept gehoert das Foto? Darf diese Person das Rezept sehen?
    // (Mitglied der Familie des Rezepts + nicht privat bzw. mit ihr geteilt)
    $stmt = $pdo->prepare("SELECT r.id FROM recipes r
        JOIN family_members m ON m.fam_id = r.fam_id AND m.user_id = ?
        WHERE r.photo = ?");
    $stmt->execute([$uid, $file]);
    $recipeId = $stmt->fetchColumn();
    $allowed = $recipeId !== false && (new Recipe($pdo))->isVisibleToUser($recipeId, $uid);
} else {
    // Profilbild: das eigene, von jemandem aus einer gemeinsamen Familie,
    // oder von jemandem, mit dem es eine offene Einladung gibt
    $stmt = $pdo->prepare("SELECT u.id FROM users u
        WHERE u.avatar = ? AND (
            u.id = ?
            OR EXISTS (SELECT 1 FROM family_members a JOIN family_members b ON a.fam_id = b.fam_id
                       WHERE a.user_id = u.id AND b.user_id = ?)
            OR EXISTS (SELECT 1 FROM family_invites i WHERE i.status = 'pending'
                       AND ((i.from_user_id = u.id AND i.to_user_id = ?) OR (i.from_user_id = ? AND i.to_user_id = u.id)))
        )");
    $stmt->execute([$file, $uid, $uid, $uid, $uid]);
    $allowed = $stmt->fetchColumn() !== false;
}

if (!$allowed) {
    notFound();
}

$path = __DIR__ . '/uploads/' . $folders[$type] . '/' . basename($file);
if (!is_file($path)) {
    notFound();
}

// Session freigeben, damit mehrere Bilder einer Seite gleichzeitig laden koennen
session_write_close();

$types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

header('Content-Type: ' . $types[$ext]);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
// "private": nur der eigene Browser darf das Bild zwischenspeichern, kein Cloudflare-/Proxy-Cache
header('Cache-Control: private, max-age=86400');
readfile($path);
