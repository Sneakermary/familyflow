<?php

// Notizfelder auf Profilen. Zwei Faelle:
// 1) Notiz auf dem Profil einer ANDEREN Person (owner_id != target_id): immer privat,
//    nur der/die Ersteller:in sieht sie, auch die Zielperson nicht.
// 2) Feld auf dem EIGENEN Profil (owner_id == target_id): standardmaessig oeffentlich
//    (jeder, der das Profil sehen darf, sieht es), optional per Checkbox privat stellbar.
class ProfileNote {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function createNote($ownerId, $targetId, $label, $value, $isPrivate = true) {
        $stmt = $this->pdo->prepare("INSERT INTO profile_notes (owner_id, target_id, label, value, is_private) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$ownerId, $targetId, $label, $value, $isPrivate ? 1 : 0]);
    }

    // Alle eigenen Notizfelder zu einer bestimmten Zielperson (bei sich selbst: alle eigenen Felder, egal ob privat/oeffentlich)
    public function getNotesForOwnerAndTarget($ownerId, $targetId) {
        $stmt = $this->pdo->prepare("SELECT * FROM profile_notes WHERE owner_id = ? AND target_id = ? ORDER BY created_at ASC");
        $stmt->execute([$ownerId, $targetId]);
        return $stmt->fetchAll();
    }

    // Oeffentliche Selbstauskunft-Felder einer Person, fuer alle anderen sichtbar, die das Profil sehen duerfen
    public function getPublicNotesForTarget($targetId) {
        $stmt = $this->pdo->prepare("SELECT * FROM profile_notes WHERE target_id = ? AND owner_id = target_id AND is_private = 0 ORDER BY created_at ASC");
        $stmt->execute([$targetId]);
        return $stmt->fetchAll();
    }

    // Nur die eigene Notiz darf geloescht werden (owner_id mit in der WHERE-Klausel)
    public function deleteNote($noteId, $ownerId) {
        $stmt = $this->pdo->prepare("DELETE FROM profile_notes WHERE id = ? AND owner_id = ?");
        $stmt->execute([$noteId, $ownerId]);
    }
}
