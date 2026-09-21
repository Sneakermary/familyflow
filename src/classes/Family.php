<?php

class Family{

    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo =$pdo;
    }

    public function createFamily($name) {
        $stmt = $this->pdo->prepare("INSERT INTO families (name) values (?)");
        $stmt->execute([$name]);
        return $this->pdo->lastInsertId();
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM families WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findFamiliesByUserId($userId) {  
        $stmt = $this->pdo->prepare("SELECT families.*
        FROM family_members
        JOIN families ON families.id = family_members.fam_id
        WHERE family_members.user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function addMember($userId, $famId) {
        $stmt = $this->pdo->prepare("INSERT INTO family_members(user_id, fam_id) VALUES (?, ?)");
        $stmt->execute([$userId, $famId]);
    }

    public function isMember($userId, $famId) {
        $stmt = $this->pdo->prepare("SELECT * FROM family_members WHERE user_id = ? AND fam_id = ?");
        $stmt->execute([$userId, $famId]);
        return $stmt->fetch();
    }

}

?>