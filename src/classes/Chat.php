<?php

// Chat zwischen Familienmitgliedern - familienuebergreifend:
// Schreiben darf man allen, mit denen man in IRGENDEINER gemeinsamen Familie ist.
//
// Ein Chat ("conversation") ist entweder
//   'direct'  - genau zwei Personen
//   'group'   - mehrere Personen, mit Namen
// Gelesen/ungelesen: pro Mitglied steht in conversation_members die id der zuletzt
// gelesenen Nachricht (last_read_message_id). Alles mit hoeherer id ist ungelesen.
class Chat {
    private PDO $pdo;

    public const MAX_LENGTH = 2000;   // max. Zeichen pro Nachricht

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // ---------- Kontakte ----------

    // Alle Personen, mit denen man mindestens eine Familie teilt (ohne sich selbst)
    public function contactsForUser($userId) {
        $stmt = $this->pdo->prepare("SELECT DISTINCT u.id, u.firstname, u.lastname, u.avatar
            FROM users u
            JOIN family_members theirs ON theirs.user_id = u.id
            JOIN family_members mine ON mine.fam_id = theirs.fam_id AND mine.user_id = ?
            WHERE u.id <> ?
            ORDER BY u.firstname, u.lastname");
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }

    // Teilen die beiden mindestens eine Familie?
    public function canContact($userId, $otherId) {
        $stmt = $this->pdo->prepare("SELECT 1 FROM family_members a
            JOIN family_members b ON b.fam_id = a.fam_id
            WHERE a.user_id = ? AND b.user_id = ? LIMIT 1");
        $stmt->execute([$userId, $otherId]);
        return (bool) $stmt->fetchColumn();
    }

    // ---------- Chats anlegen ----------

    // Direkt-Chat zwischen zwei Personen: vorhandenen zurueckgeben, sonst neu anlegen.
    // null, wenn die beiden keine gemeinsame Familie haben.
    public function getOrCreateDirect($userId, $otherId) {
        if ($userId == $otherId || !$this->canContact($userId, $otherId)) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT c.id FROM conversations c
            JOIN conversation_members a ON a.conversation_id = c.id AND a.user_id = ?
            JOIN conversation_members b ON b.conversation_id = c.id AND b.user_id = ?
            WHERE c.type = 'direct' LIMIT 1");
        $stmt->execute([$userId, $otherId]);
        $existing = $stmt->fetchColumn();
        if ($existing) {
            return (int) $existing;
        }

        return $this->createConversation('direct', null, $userId, [$userId, $otherId]);
    }

    // Gruppe anlegen. Mitglieder werden auf die eigenen Kontakte beschraenkt.
    // null, wenn kein gueltiges Mitglied (ausser einem selbst) uebrig bleibt.
    public function createGroup($userId, $name, array $memberIds) {
        $allowed = array_column($this->contactsForUser($userId), 'id');
        $memberIds = array_values(array_intersect(array_map('intval', $memberIds), array_map('intval', $allowed)));
        $name = trim($name);

        if (empty($memberIds) || $name === '' || mb_strlen($name) > 100) {
            return null;
        }
        $memberIds[] = (int) $userId;

        return $this->createConversation('group', $name, $userId, array_unique($memberIds));
    }

    private function createConversation($type, $name, $createdBy, array $memberIds) {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("INSERT INTO conversations (type, name, created_by) VALUES (?, ?, ?)");
            $stmt->execute([$type, $name, $createdBy]);
            $convId = (int) $this->pdo->lastInsertId();

            $add = $this->pdo->prepare("INSERT INTO conversation_members (conversation_id, user_id) VALUES (?, ?)");
            foreach ($memberIds as $memberId) {
                $add->execute([$convId, $memberId]);
            }
            $this->pdo->commit();
            return $convId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // ---------- Chats lesen ----------

    // Ist die Person Mitglied in diesem Chat?
    public function isMember($convId, $userId) {
        $stmt = $this->pdo->prepare("SELECT 1 FROM conversation_members WHERE conversation_id = ? AND user_id = ?");
        $stmt->execute([$convId, $userId]);
        return (bool) $stmt->fetchColumn();
    }

    // Ein Chat mit Anzeigename/-bild - nur fuer Mitglieder, sonst null
    public function getConversation($convId, $userId) {
        if (!$this->isMember($convId, $userId)) {
            return null;
        }
        $stmt = $this->pdo->prepare("SELECT * FROM conversations WHERE id = ?");
        $stmt->execute([$convId]);
        $conv = $stmt->fetch();
        return $conv ? $this->decorate($conv, $userId) : null;
    }

    // Alle Mitglieder eines Chats
    public function members($convId) {
        $stmt = $this->pdo->prepare("SELECT u.id, u.firstname, u.lastname, u.avatar
            FROM conversation_members cm JOIN users u ON u.id = cm.user_id
            WHERE cm.conversation_id = ? ORDER BY u.firstname");
        $stmt->execute([$convId]);
        return $stmt->fetchAll();
    }

    // Chat-Liste fuer die linke Spalte: $type 'direct' oder 'group',
    // neueste Aktivitaet zuerst, mit letzter Nachricht und Anzahl ungelesener
    public function conversationsForUser($userId, $type) {
        $stmt = $this->pdo->prepare("SELECT c.*,
                (SELECT MAX(m.id) FROM messages m WHERE m.conversation_id = c.id) AS last_message_id,
                (SELECT COUNT(*) FROM messages m
                    WHERE m.conversation_id = c.id AND m.id > cm.last_read_message_id AND m.sender_id <> ?) AS unread
            FROM conversations c
            JOIN conversation_members cm ON cm.conversation_id = c.id AND cm.user_id = ?
            WHERE c.type = ?
            ORDER BY COALESCE((SELECT MAX(m.created_at) FROM messages m WHERE m.conversation_id = c.id), c.created_at) DESC");
        $stmt->execute([$userId, $userId, $type]);

        $lastMsg = $this->pdo->prepare("SELECT m.body, m.sender_id, m.created_at FROM messages m WHERE m.id = ?");
        $list = [];
        foreach ($stmt->fetchAll() as $conv) {
            $conv->last_message = null;
            if ($conv->last_message_id) {
                $lastMsg->execute([$conv->last_message_id]);
                $conv->last_message = $lastMsg->fetch();
            }
            $list[] = $this->decorate($conv, $userId);
        }
        return $list;
    }

    // Anzeigename + Bild: bei Direkt-Chats die andere Person, bei Gruppen der Gruppenname
    private function decorate($conv, $userId) {
        $conv->other = null;
        if ($conv->type === 'direct') {
            $stmt = $this->pdo->prepare("SELECT u.id, u.firstname, u.lastname, u.avatar
                FROM conversation_members cm JOIN users u ON u.id = cm.user_id
                WHERE cm.conversation_id = ? AND cm.user_id <> ? LIMIT 1");
            $stmt->execute([$conv->id, $userId]);
            $conv->other = $stmt->fetch() ?: null;
            $conv->title = $conv->other ? $conv->other->firstname . ' ' . $conv->other->lastname : 'Gelöschtes Konto';
        } else {
            $conv->title = $conv->name;
        }
        return $conv;
    }

    // ---------- Nachrichten ----------

    // Nachrichten eines Chats (nur fuer Mitglieder), aelteste zuerst.
    // $afterId > 0: nur neuere als diese (fuer das automatische Nachladen)
    public function messages($convId, $userId, $afterId = 0, $limit = 200) {
        if (!$this->isMember($convId, $userId)) {
            return [];
        }
        $stmt = $this->pdo->prepare("SELECT m.id, m.sender_id, m.body, m.created_at,
                u.firstname, u.lastname, u.avatar
            FROM messages m JOIN users u ON u.id = m.sender_id
            WHERE m.conversation_id = ? AND m.id > ?
            ORDER BY m.id DESC LIMIT " . (int) $limit);
        $stmt->execute([$convId, (int) $afterId]);
        return array_reverse($stmt->fetchAll());
    }

    // Nachricht senden. Gibt die neue id zurueck oder null (kein Mitglied, leer, zu lang,
    // bei Direkt-Chats: keine gemeinsame Familie mehr)
    public function send($convId, $userId, $body) {
        $body = trim((string) $body);
        if ($body === '' || mb_strlen($body) > self::MAX_LENGTH || !$this->isMember($convId, $userId)) {
            return null;
        }

        $conv = $this->getConversation($convId, $userId);
        if ($conv->type === 'direct' && (!$conv->other || !$this->canContact($userId, $conv->other->id))) {
            return null;
        }

        $stmt = $this->pdo->prepare("INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)");
        $stmt->execute([$convId, $userId, $body]);
        $messageId = (int) $this->pdo->lastInsertId();

        // Eigene Nachricht gilt natuerlich als gelesen
        $this->markRead($convId, $userId);
        return $messageId;
    }

    // Alles in diesem Chat als gelesen markieren
    public function markRead($convId, $userId) {
        $stmt = $this->pdo->prepare("UPDATE conversation_members
            SET last_read_message_id = (SELECT COALESCE(MAX(id), 0) FROM messages WHERE conversation_id = ?)
            WHERE conversation_id = ? AND user_id = ?");
        $stmt->execute([$convId, $convId, $userId]);
    }

    // Anzahl aller ungelesenen Nachrichten (Zaehler an der Sprechblase in der Navbar)
    public function unreadTotal($userId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM messages m
            JOIN conversation_members cm ON cm.conversation_id = m.conversation_id AND cm.user_id = ?
            WHERE m.id > cm.last_read_message_id AND m.sender_id <> ?");
        $stmt->execute([$userId, $userId]);
        return (int) $stmt->fetchColumn();
    }
}
