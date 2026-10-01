<?php

class Recipe{
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    // Neues Rezept anlegen, gibt die neue Id zurück
    public function createRecipe($famId, $ownerId, $folderId, $title, $ingredients, $photo, $sharedWith) {
        $stmt = $this->pdo->prepare("INSERT INTO recipes(fam_id, owner_id, folder_id, title, ingredients, photo, shared_with) VALUES(?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$famId, $ownerId, $folderId, $title, $ingredients, $photo, $sharedWith]);
        return $this->pdo->lastInsertId();
    }

    // Einen Zubereitungsschritt hinzufügen
    public function addStep($recipeId, $stepNumber, $instruction) {
        $stmt = $this->pdo->prepare("INSERT INTO recipe_steps(recipe_id, step_number, instruction) VALUES (?, ?, ?)");
        $stmt->execute([$recipeId, $stepNumber, $instruction]);
    }

    // Alle Schritte eines Rezepts, in der richtigen Reihenfolge
    public function getStepsForRecipe($recipeId) {
        $stmt = $this->pdo->prepare("SELECT * FROM recipe_steps WHERE recipe_id = ? ORDER BY step_number ASC");
        $stmt->execute([$recipeId]);
        return $stmt->fetchAll();
    }

    // Alle Rezepte, die eine Person sehen darf
     public function getRecipesForUser($userId, $famId) {
        $stmt = $this->pdo->prepare("SELECT * FROM recipes
            WHERE fam_id = ? AND (shared_with IS NULL OR shared_with = ? OR owner_id = ?)
            ORDER BY title ASC");
        $stmt->execute([$famId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    // Nur die Rezepte in einem bestimmten Ordner
    public function getRecipesForUserInFolder($userId, $famId, $folderId) {
        $stmt = $this->pdo->prepare("SELECT * FROM recipes
            WHERE fam_id = ? AND folder_id = ? AND (shared_with IS NULL OR shared_with = ? OR owner_id = ?)
            ORDER BY title ASC");
        $stmt->execute([$famId, $folderId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    // Rezepte ohne Ordner
    public function getRecipesForUserWithoutFolder($userId, $famId) {
        $stmt = $this->pdo->prepare("SELECT * FROM recipes
            WHERE fam_id = ? AND folder_id IS NULL AND (shared_with IS NULL OR shared_with = ? OR owner_id = ?)
            ORDER BY title ASC");
        $stmt->execute([$famId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    // Ein einzelnes Rezept per Id holen
    public function getRecipeById($recipeId) {
        $stmt = $this->pdo->prepare("SELECT * FROM recipes WHERE id = ?");
        $stmt->execute([$recipeId]);
        return $stmt->fetch();
    }

    // Rezept aktualisieren (Grunddaten, kein Foto-Austausch wird hier entschieden - das macht die Seite selbst)
    public function updateRecipe($recipeId, $folderId, $title, $ingredients, $photo, $sharedWith) {
        $stmt = $this->pdo->prepare("UPDATE recipes SET folder_id = ?, title = ?, ingredients = ?, photo = ?, shared_with = ? WHERE id = ?");
        $stmt->execute([$folderId, $title, $ingredients, $photo, $sharedWith, $recipeId]);
    }

    // Alle Schritte eines Rezepts loeschen (vor dem Neu-Einfuegen beim Bearbeiten)
    public function deleteStepsForRecipe($recipeId) {
        $stmt = $this->pdo->prepare("DELETE FROM recipe_steps WHERE recipe_id = ?");
        $stmt->execute([$recipeId]);
    }

}
?>