<?php

class FamilyList {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Neue Liste anlegen, gibt die neue Id zurueck (wie Family::createFamily)
    public function createList($famId, $ownerId, $name) {
        $stmt = $this->pdo->prepare("INSERT INTO lists (fam_id, owner_id, name) VALUES(?, ?, ?)");
        $stmt->execute([$famId, $ownerId, $name]);
        return $this->pdo->lastInsertId();
    }

    // Person zu einer Liste Zugriff geben (wie Family::addMember)
    public function addAccess($listId, $userId) {
        $stmt = $this->pdo->prepare("INSERT INTO list_access (list_id, user_id) VALUES (?, ?)");
        $stmt->execute([$listId, $userId]);  
    }

    // Prueft, ob eine Person Zugriff auf eine Liste hat (wie Family::isMember)
    public function hasAccess($listId, $userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM list_access WHERE list_id = ? AND user_id = ?");
        $stmt->execute([$listId, $userId]);
        return $stmt->fetch();
        }

    // Alle Listen, auf die eine Person Zugriff hat (JOIN mit list_access, wie Family::findFamiliesByUserId)
    public function getListsForUser($userId) {
        $stmt = $this->pdo->prepare("SELECT lists.* FROM lists
            JOIN list_access ON lists.id = list_access.list_id
            WHERE list_access.user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Eine einzelne Liste per Id holen (wie Family::findById)
    public function getListById($listId) {
        $stmt = $this->pdo->prepare("SELECT * FROM lists WHERE id = ?");
        $stmt->execute([$listId]);
        return $stmt->fetch();
    }

    // Alle Eintraege einer Liste
    public function getItems($listId) {
        $stmt = $this->pdo->prepare("SELECT * FROM list_items WHERE list_id = ?");
        $stmt->execute([$listId]);
        return $stmt->fetchAll();
    }

    // Neuen Eintrag anlegen
    public function addItem($listId, $content) {
        $stmt = $this->pdo->prepare("INSERT INTO list_items (list_id, content) VALUES (?, ?)");
        $stmt->execute([$listId, $content]);
        return $this->pdo->lastInsertId();
    }

    // Abhaken/wieder-oeffnen umschalten
    public function toggleItem($itemId) {
        $stmt = $this->pdo->prepare("UPDATE list_items SET done = NOT done WHERE id = ?");
        $stmt->execute([$itemId]);
    }
}

?>
