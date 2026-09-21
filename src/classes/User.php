<?php

class User{
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }


    public function findByEmail($email){
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByFamilyId($famid) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE fam_id = ?");
        $stmt->execute([$famid]);
        return $stmt->fetchAll();
    }

    public function createUser($firstname, $lastname, $email, $pw_hash) {
        $stmt = $this->pdo->prepare("INSERT INTO users (firstname, lastname, email, pw_hash, fam_id, role) VALUES (?, ?, ?, ?, NULL, 'member')");
        $stmt->execute([$firstname, $lastname, $email, $pw_hash]);
    }

    public function setFamily($fam_id, $id) {
        $stmt = $this->pdo->prepare("UPDATE users SET fam_id = ? WHERE id = ?");
        $stmt->execute([$fam_id, $id]);
    }
}
?>