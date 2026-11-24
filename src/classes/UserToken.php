<?php

// Einmal-Links per Mail: E-Mail bestaetigen ('verify') und Passwort zuruecksetzen ('reset').
// In der Datenbank steht nur der SHA-256-Hash des Tokens - der echte Token steht nur im Link.
// Wer die Datenbank in die Haende bekommt, kann damit also kein Passwort zuruecksetzen.
class UserToken {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Neuen Token anlegen (alte desselben Typs werden ungueltig), gibt den echten Token zurueck
    public function create($userId, $type, $validMinutes) {
        $this->deleteForUser($userId, $type);

        $token = bin2hex(random_bytes(32));
        $stmt = $this->pdo->prepare("INSERT INTO user_tokens (user_id, type, token_hash, expires_at)
            VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))");
        $stmt->execute([$userId, $type, hash('sha256', $token), (int) $validMinutes]);
        return $token;
    }

    // Zu welchem User gehoert dieser Token? null, wenn falsch, abgelaufen oder schon benutzt
    public function findValidUserId($token, $type) {
        if (!is_string($token) || $token === '') {
            return null;
        }
        $stmt = $this->pdo->prepare("SELECT user_id FROM user_tokens
            WHERE token_hash = ? AND type = ? AND expires_at > NOW()");
        $stmt->execute([hash('sha256', $token), $type]);
        $userId = $stmt->fetchColumn();
        return $userId === false ? null : (int) $userId;
    }

    public function deleteForUser($userId, $type) {
        $stmt = $this->pdo->prepare("DELETE FROM user_tokens WHERE user_id = ? AND type = ?");
        $stmt->execute([$userId, $type]);
    }

    // Schutz gegen Mail-Spam: wurde in den letzten X Sekunden schon ein Link verschickt?
    public function wasCreatedRecently($userId, $type, $seconds) {
        $stmt = $this->pdo->prepare("SELECT 1 FROM user_tokens
            WHERE user_id = ? AND type = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$userId, $type, (int) $seconds]);
        return (bool) $stmt->fetch();
    }
}
