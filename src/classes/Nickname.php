<?php

class Nickname{
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Nickname setzen oder ändern -> legt neu an oder überschreibt
    public function setNickname($ownerId, $targetId, $nickname) {
        $stmt = $this->pdo->prepare("INSERT INTO nicknames(owner_id, target_id, nickname) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE nickname = ?");
        $stmt->execute([$ownerId, $targetId, $nickname, $nickname]);
    }

    // Einen einzelnen Spitznamen holen (fürs Bearbeiten-Formular)
    public function getNickname($ownerId, $targetId) {
        $stmt = $this->pdo->prepare("SELECT nickname FROM nicknames WHERE owner_id = ? AND target_id = ?");
        $stmt->execute([$ownerId, $targetId]);
        $row = $stmt->fetch();
        return $row ? $row->nickname : null;
    }

    // Alle Nicknames einer Person auf einmal holen, als target_id => nickname
    public function getNicknamesForOwner($ownerId) {
        $stmt = $this->pdo->prepare("SELECT target_id, nickname FROM nicknames WHERE owner_id = ?");
        $stmt->execute([$ownerId]);

        $result = [];
        foreach($stmt->fetchAll() as $row) {
            $result[$row->target_id] = $row->nickname;
        }
        return $result;
    }

    public function deleteNickname($ownerId, $targetId) {
        $stmt = $this->pdo->prepare("DELETE FROM nicknames WHERE owner_id = ? AND target_id = ?");
        $stmt->execute([$ownerId, $targetId]);
    }
}
