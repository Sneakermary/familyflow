<?php

class FamilyInvite {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Neue Einladung anlegen, gibt die neue Id zurueck
    public function createInvite($famId, $fromUserId, $toUserId) {
        $stmt = $this->pdo->prepare("INSERT INTO family_invites (fam_id, from_user_id, to_user_id) VALUES (?, ?, ?)");
        $stmt->execute([$famId, $fromUserId, $toUserId]);
        return $this->pdo->lastInsertId();
    }

    // Gibt es schon eine offene Einladung fuer diese Familie+Person?
    public function hasPendingInvite($famId, $toUserId) {
        $stmt = $this->pdo->prepare("SELECT * FROM family_invites WHERE fam_id = ? AND to_user_id = ? AND status = 'pending'");
        $stmt->execute([$famId, $toUserId]);
        return (bool) $stmt->fetch();
    }

    // Eine einzelne Einladung per Id holen
    public function getInviteById($inviteId) {
        $stmt = $this->pdo->prepare("SELECT * FROM family_invites WHERE id = ?");
        $stmt->execute([$inviteId]);
        return $stmt->fetch();
    }

    // Einladung annehmen
    public function acceptInvite($inviteId) {
        $stmt = $this->pdo->prepare("UPDATE family_invites SET status = 'accepted' WHERE id = ?");
        $stmt->execute([$inviteId]);
    }

    // Einladung ablehnen
    public function declineInvite($inviteId) {
        $stmt = $this->pdo->prepare("UPDATE family_invites SET status = 'declined' WHERE id = ?");
        $stmt->execute([$inviteId]);
    }
}
