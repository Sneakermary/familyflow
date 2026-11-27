<?php
// Schnittstelle fuer chat.js (Antwort immer JSON):
//   GET  ?action=poll&c=<chat>&after=<id>   neue Nachrichten seit <id> (markiert sie als gelesen)
//   POST action=send, c, body, csrf_token   Nachricht senden
// Nur fuer eingeloggte Mitglieder des jeweiligen Chats.
require_once __DIR__ . '/../src/components/session.php';
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Chat.php';
require_once __DIR__ . '/../src/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function reply($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

if (!isset($_SESSION['uid'])) {
    reply(401, ['error' => 'not_logged_in']);
}
$uid = (int) $_SESSION['uid'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$convId = (int) ($_POST['c'] ?? $_GET['c'] ?? 0);

if ($action === 'send') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        reply(403, ['error' => 'csrf']);
    }
}
// Session wird ab hier nicht mehr gebraucht -> freigeben (sonst warten parallele Anfragen)
session_write_close();

$chat = new Chat((new Database)->connect());

if (!$chat->isMember($convId, $uid)) {
    reply(404, ['error' => 'not_found']);
}

// Nachricht in das Format fuer chat.js bringen (Text bleibt Text - chat.js setzt ihn mit textContent ein)
function format($m) {
    $ts = strtotime($m->created_at);
    return [
        'id' => (int) $m->id,
        'sender_id' => (int) $m->sender_id,
        'firstname' => $m->firstname,
        'body' => $m->body,
        'time' => date('Y-m-d', $ts) === date('Y-m-d') ? date('H:i', $ts) : date('d.m. H:i', $ts),
    ];
}

if ($action === 'send') {
    $after = (int) ($_POST['after'] ?? 0);
    $newId = $chat->send($convId, $uid, $_POST['body'] ?? '');
    if ($newId === null) {
        reply(422, ['error' => 'not_sent']);
    }
    // Alles seit dem letzten bekannten Stand zurueckgeben (inkl. der eigenen neuen Nachricht)
    reply(200, ['messages' => array_map('format', $chat->messages($convId, $uid, $after))]);
}

if ($action === 'poll') {
    $after = (int) ($_GET['after'] ?? 0);
    $messages = $chat->messages($convId, $uid, $after);
    if ($messages) {
        $chat->markRead($convId, $uid);
    }
    reply(200, ['messages' => array_map('format', $messages)]);
}

reply(400, ['error' => 'unknown_action']);
