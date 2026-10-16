<?php

class FamilyList {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Neue Liste anlegen, gibt die neue Id zurueck (wie Family::createFamily)
    // isDefault=true: Standardliste, immer fuer alle aktuellen und kuenftigen Familienmitglieder
    // sichtbar, unabhaengig von list_access (z.B. die automatisch angelegten "Einkaufen"/"To-Dos")
    public function createList($famId, $ownerId, $name, $isDefault = false) {
        $stmt = $this->pdo->prepare("INSERT INTO lists (fam_id, owner_id, name, is_default) VALUES(?, ?, ?, ?)");
        $stmt->execute([$famId, $ownerId, $name, $isDefault ? 1 : 0]);
        return $this->pdo->lastInsertId();
    }

    // Person zu einer Liste Zugriff geben (wie Family::addMember)
    public function addAccess($listId, $userId) {
        $stmt = $this->pdo->prepare("INSERT INTO list_access (list_id, user_id) VALUES (?, ?)");
        $stmt->execute([$listId, $userId]);  
    }

    // Prueft, ob eine Person Zugriff auf eine Liste hat: entweder ueber list_access,
    // oder weil es eine Standardliste ist und die Person Mitglied der zugehoerigen Familie ist
    public function hasAccess($listId, $userId) {
        $stmt = $this->pdo->prepare("SELECT 1 FROM lists
            LEFT JOIN list_access ON list_access.list_id = lists.id AND list_access.user_id = ?
            LEFT JOIN family_members ON family_members.fam_id = lists.fam_id AND family_members.user_id = ?
            WHERE lists.id = ?
            AND (list_access.user_id IS NOT NULL OR (lists.is_default = 1 AND family_members.user_id IS NOT NULL))");
        $stmt->execute([$userId, $userId, $listId]);
        return $stmt->fetch();
    }

    // Alle Listen, die eine Person innerhalb der aktuell ausgewaehlten Familie sehen darf:
    // eigene geteilte Listen (list_access) plus alle Standardlisten der Familie
    public function getListsForUser($userId, $famId) {
        $stmt = $this->pdo->prepare("SELECT lists.* FROM lists
            WHERE lists.fam_id = ?
            AND (
                lists.is_default = 1
                OR lists.id IN (SELECT list_id FROM list_access WHERE user_id = ?)
            )");
        $stmt->execute([$famId, $userId]);
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

    // Alle erledigten Eintraege einer Liste entfernen
    public function deleteDoneItems($listId) {
        $stmt = $this->pdo->prepare("DELETE FROM list_items WHERE list_id = ? AND done = 1");
        $stmt->execute([$listId]);
    }

    // Abhaken/wieder-oeffnen umschalten. listId muss mitgegeben werden, sonst koennte
    // jemand mit Zugriff auf eine eigene Liste per erratener Id Eintraege fremder Listen umschalten.
    public function toggleItem($itemId, $listId) {
        $stmt = $this->pdo->prepare("UPDATE list_items SET done = NOT done WHERE id = ? AND list_id = ?");
        $stmt->execute([$itemId, $listId]);
    }

    // Liste umbenennen
    public function renameList($listId, $newName) {
        $stmt = $this->pdo->prepare("UPDATE lists SET name = ? WHERE id = ?");
        $stmt->execute([$newName, $listId]);
    }
    // Liste löschen
    public function deleteList($listId) {
        $stmt = $this->pdo->prepare("DELETE FROM lists WHERE id = ?");
        $stmt->execute([$listId]);
    }

    public function getAccessUserIds($listId) {
        $stmt = $this->pdo->prepare("SELECT user_id FROM list_access WHERE list_id = ?");
        $stmt->execute([$listId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    public function removeAccess($listId, $userId) {
        $stmt = $this->pdo->prepare("DELETE FROM list_access WHERE list_id = ? AND user_id = ?");
        $stmt->execute([$listId, $userId]);
    }
}

?>
