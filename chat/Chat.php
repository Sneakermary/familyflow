<?php
// Chat-Seite: links die Liste (Reiter "Chats" / "Gruppen", Plus fuer Neues),
// rechts der geoeffnete Chat mit Nachrichten und Eingabefeld.
//   /chat/Chat.php?tab=direct|group      Liste
//   /chat/Chat.php?tab=...&c=<id>        Chat geoeffnet
//   /chat/Chat.php?tab=...&new=1         "Neuer Chat" bzw. "Neue Gruppe"
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/Chat.php';
require_once __DIR__ . '/../src/classes/Nickname.php';
require_once __DIR__ . '/../src/functions.php';

require_once __DIR__ . '/../src/components/guard.php';

$pdo = (new Database)->connect();
$chat = new Chat($pdo);
$uid = (int) $_SESSION['uid'];

$tab = ($_GET['tab'] ?? 'direct') === 'group' ? 'group' : 'direct';
$convId = isset($_GET['c']) ? (int) $_GET['c'] : 0;
$showNew = isset($_GET['new']);
$error = null;

// ---------- Formulare (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header("Location: /chat/Chat.php");
        exit();
    }

    // Nachricht senden (ohne JavaScript; mit JavaScript laeuft das ueber ChatApi.php)
    if (isset($_POST['sendBtn'])) {
        $chat->send($convId, $uid, $_POST['body'] ?? '');
        header("Location: /chat/Chat.php?tab=$tab&c=$convId");
        exit();
    }

    // Direkt-Chat mit einem Kontakt starten (oder den vorhandenen oeffnen)
    if (isset($_POST['startDirect'])) {
        $newId = $chat->getOrCreateDirect($uid, (int) $_POST['startDirect']);
        header("Location: /chat/Chat.php?tab=direct" . ($newId ? "&c=$newId" : ''));
        exit();
    }

    // Gruppe anlegen
    if (isset($_POST['createGroupBtn'])) {
        $newId = $chat->createGroup($uid, $_POST['groupName'] ?? '', $_POST['members'] ?? []);
        if ($newId) {
            header("Location: /chat/Chat.php?tab=group&c=$newId");
            exit();
        }
        $error = 'Bitte gib einen Gruppennamen ein und wähle mindestens eine Person aus.';
        $showNew = true;
    }
}

// ---------- Daten fuer die Anzeige ----------
// Eigene Spitznamen (privat: nur ich sehe, wie ich jemanden genannt habe)
$nicknames = (new Nickname($pdo))->getNicknamesForOwner($uid);

// Anzeigename einer Person: Spitzname, sonst "Vorname Nachname" ($short: nur Vorname)
function chatName($person, $nicknames, $short = false) {
    if (isset($nicknames[$person->id]) && $nicknames[$person->id] !== '') {
        return $nicknames[$person->id];
    }
    return $short ? $person->firstname : $person->firstname . ' ' . $person->lastname;
}

// Suchtext fuer die Lupe: Spitzname + echter Name (klein geschrieben)
function searchText($person, $nicknames) {
    return mb_strtolower(($nicknames[$person->id] ?? '') . ' ' . $person->firstname . ' ' . $person->lastname);
}

$conversations = $chat->conversationsForUser($uid, $tab);
$contacts = $chat->contactsForUser($uid);

// Titel und Suchtext pro Chat: Direkt-Chat -> (Spitz-)Name der Person, Gruppe -> Name + Mitglieder
$hasDirectWith = [];
foreach ($conversations as $conv) {
    if ($conv->type === 'direct' && $conv->other) {
        $conv->title = chatName($conv->other, $nicknames);
        $conv->search = searchText($conv->other, $nicknames);
        $hasDirectWith[$conv->other->id] = true;
    } else {
        $conv->search = mb_strtolower($conv->title);
        foreach ($chat->members($conv->id) as $m) {
            if ($m->id != $uid) {
                $conv->search .= ' ' . searchText($m, $nicknames);
            }
        }
    }
}

$current = $convId ? $chat->getConversation($convId, $uid) : null;
$messages = [];
$members = [];
if ($current) {
    $messages = $chat->messages($current->id, $uid);
    $members = $chat->members($current->id);
    if ($current->type === 'direct' && $current->other) {
        $current->title = chatName($current->other, $nicknames);
    }
    // Geoeffnet = gelesen (vor der Navbar, damit der Zaehler gleich stimmt)
    $chat->markRead($current->id, $uid);
    $tab = $current->type;
}

// Uhrzeit fuer die Nachrichten: heute nur "14:32", sonst "24.11. 14:32"
function chatTime($dateTime) {
    $ts = strtotime($dateTime);
    return date('Y-m-d', $ts) === date('Y-m-d') ? date('H:i', $ts) : date('d.m. H:i', $ts);
}

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<section class="chatPage <?= ($current || $showNew) ? 'chatOpen' : '' ?>">

    <!-- ===== LINKS: Liste ===== -->
    <aside class="chatSidebar">
        <div class="chatTabs">
            <a href="/chat/Chat.php?tab=direct" class="chatTab <?= $tab === 'direct' ? 'active' : '' ?>">Chats</a>
            <a href="/chat/Chat.php?tab=group" class="chatTab <?= $tab === 'group' ? 'active' : '' ?>">Gruppen</a>
            <button type="button" class="chatSearchBtn" id="chatSearchBtn" title="Suchen" aria-label="Suchen" aria-expanded="false">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </button>
            <a href="/chat/Chat.php?tab=<?= $tab ?>&amp;new=1" class="chatNewBtn"
               title="<?= $tab === 'group' ? 'Neue Gruppe' : 'Neuer Chat' ?>" aria-label="<?= $tab === 'group' ? 'Neue Gruppe' : 'Neuer Chat' ?>">+</a>
        </div>

        <div class="chatSearchBox hidden" id="chatSearchBox">
            <input type="search" id="chatSearch" placeholder="<?= $tab === 'group' ? 'Gruppe oder Person suchen' : 'Name oder Spitzname suchen' ?>" autocomplete="off" aria-label="Suchen">
        </div>

        <div class="chatList" id="chatList">
            <?php if (empty($conversations)): ?>
                <p class="chatEmpty" id="chatListEmpty">
                    <?= $tab === 'group' ? 'Noch keine Gruppen.' : 'Noch keine Chats.' ?><br>
                    Tippe auf <strong>+</strong>, um <?= $tab === 'group' ? 'eine Gruppe zu erstellen' : 'einen Chat zu starten' ?>.
                </p>
            <?php endif; ?>

            <?php foreach ($conversations as $conv): ?>
                <a href="/chat/Chat.php?tab=<?= $tab ?>&amp;c=<?= $conv->id ?>"
                   class="chatListItem <?= $current && $current->id == $conv->id ? 'active' : '' ?>"
                   data-search="<?= htmlspecialchars($conv->search) ?>">
                    <?php if ($conv->type === 'direct' && $conv->other): ?>
                        <?= avatarHtml($conv->other, 'class="chatAvatar"') ?>
                    <?php else: ?>
                        <span class="chatAvatar chatAvatarGroup"><?= htmlspecialchars(mb_strtoupper(mb_substr($conv->title, 0, 1))) ?></span>
                    <?php endif; ?>
                    <span class="chatListText">
                        <span class="chatListName"><?= htmlspecialchars($conv->title) ?></span>
                        <span class="chatListPreview">
                            <?php if ($conv->last_message): ?>
                                <?= $conv->last_message->sender_id == $uid ? 'Du: ' : '' ?><?= htmlspecialchars(mb_strimwidth($conv->last_message->body, 0, 45, '…')) ?>
                            <?php else: ?>
                                Noch keine Nachrichten
                            <?php endif; ?>
                        </span>
                    </span>
                    <?php if ($conv->unread > 0 && !($current && $current->id == $conv->id)): ?>
                        <span class="chatUnread"><?= $conv->unread ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>

            <?php if ($tab === 'direct'): ?>
                <!-- Kontakte, mit denen es noch keinen Chat gibt - erscheinen nur beim Suchen -->
                <form method="POST" class="chatSearchContacts hidden" id="chatSearchContacts">
                    <?= csrfField() ?>
                    <p class="chatSearchLabel">Neuen Chat starten</p>
                    <?php foreach ($contacts as $contact): ?>
                        <?php if (!isset($hasDirectWith[$contact->id])): ?>
                            <button type="submit" name="startDirect" value="<?= $contact->id ?>" class="chatListItem chatSearchContact"
                                    data-search="<?= htmlspecialchars(searchText($contact, $nicknames)) ?>">
                                <?= avatarHtml($contact, 'class="chatAvatar"') ?>
                                <span class="chatListText">
                                    <span class="chatListName"><?= htmlspecialchars(chatName($contact, $nicknames)) ?></span>
                                    <?php if (isset($nicknames[$contact->id])): ?>
                                        <span class="chatListPreview"><?= htmlspecialchars($contact->firstname . ' ' . $contact->lastname) ?></span>
                                    <?php endif; ?>
                                </span>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </form>
            <?php endif; ?>

            <p class="chatEmpty hidden" id="chatNoResults">Keine Treffer.</p>
        </div>
    </aside>

    <!-- ===== RECHTS: Chat / Neu ===== -->
    <div class="chatMain">
        <?php if ($showNew): ?>
            <div class="chatHeader">
                <a href="/chat/Chat.php?tab=<?= $tab ?>" class="chatBack" aria-label="Zurück">&larr;</a>
                <strong><?= $tab === 'group' ? 'Neue Gruppe' : 'Neuer Chat' ?></strong>
            </div>
            <div class="chatNew">
                <?php if ($error): ?>
                    <div class="flash flashError"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (empty($contacts)): ?>
                    <p class="chatEmpty">Du hast noch keine Kontakte. Kontakte sind alle Mitglieder deiner Familien – lade jemanden in deine Familie ein.</p>
                <?php elseif ($tab === 'direct'): ?>
                    <p class="formHint">Mit wem möchtest du schreiben? Du kannst allen schreiben, mit denen du in einer Familie bist.</p>
                    <form method="POST" class="chatContactPick">
                        <?= csrfField() ?>
                        <?php foreach ($contacts as $contact): ?>
                            <button type="submit" name="startDirect" value="<?= $contact->id ?>" class="chatContact">
                                <?= avatarHtml($contact, 'class="chatAvatar"') ?>
                                <span><?= htmlspecialchars(chatName($contact, $nicknames)) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </form>
                <?php else: ?>
                    <form method="POST" class="chatGroupForm">
                        <?= csrfField() ?>
                        <label>Gruppenname
                            <input type="text" name="groupName" maxlength="100" required placeholder="z.B. Geschwister" value="<?= htmlspecialchars($_POST['groupName'] ?? '') ?>">
                        </label>
                        <p class="formHint">Wer soll dabei sein?</p>
                        <?php foreach ($contacts as $contact): ?>
                            <label class="chatContactCheck">
                                <input type="checkbox" name="members[]" value="<?= $contact->id ?>"
                                    <?= in_array((string) $contact->id, $_POST['members'] ?? [], true) ? 'checked' : '' ?>>
                                <?= avatarHtml($contact, 'class="chatAvatar"') ?>
                                <span><?= htmlspecialchars(chatName($contact, $nicknames)) ?></span>
                            </label>
                        <?php endforeach; ?>
                        <button type="submit" name="createGroupBtn">Gruppe erstellen</button>
                    </form>
                <?php endif; ?>
            </div>

        <?php elseif ($current): ?>
            <div class="chatHeader">
                <a href="/chat/Chat.php?tab=<?= $tab ?>" class="chatBack" aria-label="Zurück">&larr;</a>
                <?php if ($current->type === 'direct' && $current->other): ?>
                    <?= avatarHtml($current->other, 'class="chatAvatar"') ?>
                <?php else: ?>
                    <span class="chatAvatar chatAvatarGroup"><?= htmlspecialchars(mb_strtoupper(mb_substr($current->title, 0, 1))) ?></span>
                <?php endif; ?>
                <span class="chatHeaderText">
                    <strong><?= htmlspecialchars($current->title) ?></strong>
                    <?php if ($current->type === 'group'): ?>
                        <small><?= htmlspecialchars(implode(', ', array_map(fn($m) => $m->id == $uid ? 'Du' : chatName($m, $nicknames, true), $members))) ?></small>
                    <?php endif; ?>
                </span>
            </div>

            <div class="chatMessages" id="chatMessages"
                 data-conv="<?= $current->id ?>"
                 data-me="<?= $uid ?>"
                 data-group="<?= $current->type === 'group' ? '1' : '0' ?>"
                 data-last="<?= $messages ? end($messages)->id : 0 ?>">
                <?php if (empty($messages)): ?>
                    <p class="chatEmpty" id="chatNoMessages">Noch keine Nachrichten – schreib die erste! 👋</p>
                <?php endif; ?>
                <?php foreach ($messages as $m): ?>
                    <div class="chatMsg <?= $m->sender_id == $uid ? 'mine' : 'theirs' ?>">
                        <?php if ($current->type === 'group' && $m->sender_id != $uid): ?>
                            <span class="chatMsgSender"><?= htmlspecialchars(chatName((object) ['id' => $m->sender_id, 'firstname' => $m->firstname, 'lastname' => $m->lastname], $nicknames, true)) ?></span>
                        <?php endif; ?>
                        <span class="chatBubble"><?= htmlspecialchars($m->body) ?></span>
                        <span class="chatMsgTime"><?= chatTime($m->created_at) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="POST" class="chatInput" id="chatForm">
                <?= csrfField() ?>
                <button type="button" class="chatIconBtn" title="Bilder senden – kommt bald" disabled>+</button>
                <button type="button" class="chatIconBtn" id="chatEmojiBtn" title="Emoji – kommt bald" disabled>😊</button>
                <textarea name="body" id="chatBody" rows="1" maxlength="<?= Chat::MAX_LENGTH ?>" placeholder="Gib eine Nachricht ein" required></textarea>
                <button type="submit" name="sendBtn" class="chatSendBtn" aria-label="Senden">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
                </button>
            </form>

        <?php else: ?>
            <div class="chatPlaceholder">
                <span class="chatPlaceholderIcon">💬</span>
                <p>Wähle links einen Chat aus<br>oder starte mit <strong>+</strong> einen neuen.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<script src="<?= asset('/assets/js/chat.js') ?>"></script>
<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
