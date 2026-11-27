<?php
class Notification {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Neue Benachrichtigung anlegen
    public function notify($userId, $message, $link = null) {
        $stmt = $this->pdo->prepare("INSERT INTO notifications(user_id, message, link) VALUES(?, ?, ?)");
        $stmt->execute([$userId, $message, $link]);
    }

    // Alle Benachrichtigungen einer Person, neueste zuerst
    public function getNotificationsForUser($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Anzahl der ungelesenen Benachrichtigungen (Glockenzähler)
    public function getUnreadCountForUser($userId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    // Neue, ungelesene Benachrichtigungen mit id > $afterId (fuer die Pop-ups), aelteste zuerst
    public function newSince($userId, $afterId, $limit = 10) {
        $stmt = $this->pdo->prepare("SELECT id, message, link FROM notifications
            WHERE user_id = ? AND id > ? AND is_read = 0 ORDER BY id LIMIT " . (int) $limit);
        $stmt->execute([$userId, (int) $afterId]);
        return $stmt->fetchAll();
    }

    // Hoechste id der eigenen Benachrichtigungen (Startpunkt fuer die Pop-ups)
    public function latestId($userId) {
        $stmt = $this->pdo->prepare("SELECT COALESCE(MAX(id), 0) FROM notifications WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    // Alle Benachrichtigungen einer Person als gelesen markieren
    public function markAllAsReadForUser($userId) {
        $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
}
?>