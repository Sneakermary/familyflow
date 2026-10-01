<?php
class RecipeFolder{
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Neuen Ordner anlegen, gibt die neue Id zurück
    public function createFolder($famId, $name) {
        $stmt = $this->pdo->prepare("INSERT INTO recipe_folders(fam_id, name) VALUES (?, ?)");
        $stmt->execute([$famId, $name]);
        return $this->pdo->lastInsertId();
    }
    // Alle Ordner einer Familie, alpgabetisch sortiert
    public function getFoldersForFamily($famId) {
        $stmt = $this->pdo->prepare("SELECT * FROM recipe_folders WHERE fam_id = ? ORDER BY name ASC");
        $stmt->execute([$famId]);
        return $stmt->fetchAll();
    }

    // Einen einzelnen Ordner per Id holen
    public function getFolderById($folderId) {
        $stmt = $this->pdo->prepare("SELECT * FROM recipe_folders WHERE id = ?");
        $stmt->execute([$folderId]);
        return $stmt->fetch();
    }
}
?>