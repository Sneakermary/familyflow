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
}

?>