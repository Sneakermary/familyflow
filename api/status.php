<?php
// Live-Status fuer live.js (JSON), wird auf allen App-Seiten alle ~20 Sekunden abgefragt:
//   GET /api/status.php                 -> nur Zaehler + aktuelle hoechste ids (Startpunkt, keine Pop-ups)
//   GET /api/status.php?n=<id>&m=<id>   -> Zaehler + alles NEUE seit diesen ids (fuer die Pop-ups)
//        n = letzte bekannte Benachrichtigungs-id, m = letzte bekannte Nachrichten-id
// Nur fuer eingeloggte Personen, nur die eigenen Daten.
require_once __DIR__ . '/../src/components/session.php';
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Notification.php';
require_once __DIR__ . '/../src/classes/Chat.php';
require_once __DIR__ . '/../src/classes/Nickname.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not_logged_in']);
    exit();
}
$uid = (int) $_SESSION['uid'];
// Session wird nur gelesen -> sofort freigeben, damit andere Anfragen nicht warten
session_write_close();

$pdo = (new Database)->connect();
$notification = new Notification($pdo);
$chat = new Chat($pdo);

$result = [
    'uid' => $uid,
    'chatUnread' => $chat->unreadTotal($uid),
    'notifUnread' => $notification->getUnreadCountForUser($uid),
    'lastNotif' => $notification->latestId($uid),
    'lastMessage' => $chat->latestMessageId($uid),
    'notifications' => [],
    'messages' => [],
];

// Ohne Startpunkt nur die Zaehler + ids liefern (sonst wuerden beim ersten Mal alle alten aufpoppen)
if (isset($_GET['n'], $_GET['m'])) {
    foreach ($notification->newSince($uid, (int) $_GET['n']) as $n) {
        $result['notifications'][] = ['id' => (int) $n->id, 'message' => $n->message, 'link' => $n->link];
    }

    // Absender wie im Chat: eigener Spitzname, sonst Vorname
    $nicknames = (new Nickname($pdo))->getNicknamesForOwner($uid);
    foreach ($chat->newMessagesSince($uid, (int) $_GET['m']) as $m) {
        $from = ($nicknames[$m->sender_id] ?? '') !== '' ? $nicknames[$m->sender_id] : $m->firstname;
        $result['messages'][] = [
            'id' => (int) $m->id,
            'conversation' => (int) $m->conversation_id,
            'from' => $from,
            'group' => $m->type === 'group' ? $m->name : null,
            'preview' => mb_strimwidth($m->body, 0, 80, '…'),
            'link' => '/chat/Chat.php?tab=' . ($m->type === 'group' ? 'group' : 'direct') . '&c=' . (int) $m->conversation_id,
        ];
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
