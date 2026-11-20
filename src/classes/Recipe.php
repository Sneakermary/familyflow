<?php

class Recipe{
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Neues Rezept anlegen, gibt die neue Id zurück. Wer es sehen darf, wird separat per
    // setSharedWith() gesetzt (mehrere Personen moeglich, siehe recipe_shared_with)
    public function createRecipe($famId, $ownerId, $folderId, $title, $ingredients, $photo) {
        $stmt = $this->pdo->prepare("INSERT INTO recipes(fam_id, owner_id, folder_id, title, ingredients, photo) VALUES(?, ?, ?, ?, ?, ?)");
        $stmt->execute([$famId, $ownerId, $folderId, $title, $ingredients, $photo]);
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
            WHERE fam_id = ? AND " . $this->visibilityCondition() . "
            ORDER BY title ASC");
        $stmt->execute([$famId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    // Nur die Rezepte in einem bestimmten Ordner
    public function getRecipesForUserInFolder($userId, $famId, $folderId) {
        $stmt = $this->pdo->prepare("SELECT * FROM recipes
            WHERE fam_id = ? AND folder_id = ? AND " . $this->visibilityCondition() . "
            ORDER BY title ASC");
        $stmt->execute([$famId, $folderId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    // Rezepte ohne Ordner
    public function getRecipesForUserWithoutFolder($userId, $famId) {
        $stmt = $this->pdo->prepare("SELECT * FROM recipes
            WHERE fam_id = ? AND folder_id IS NULL AND " . $this->visibilityCondition() . "
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

    // Rezept aktualisieren (Grunddaten, kein Foto-Austausch wird hier entschieden - das macht die Seite selbst;
    // wer es sehen darf, laeuft separat ueber setSharedWith())
    public function updateRecipe($recipeId, $folderId, $title, $ingredients, $photo) {
        $stmt = $this->pdo->prepare("UPDATE recipes SET folder_id = ?, title = ?, ingredients = ?, photo = ? WHERE id = ?");
        $stmt->execute([$folderId, $title, $ingredients, $photo, $recipeId]);
    }

    // Alle Schritte eines Rezepts loeschen (vor dem Neu-Einfuegen beim Bearbeiten)
    public function deleteStepsForRecipe($recipeId) {
        $stmt = $this->pdo->prepare("DELETE FROM recipe_steps WHERE recipe_id = ?");
        $stmt->execute([$recipeId]);
    }

    // Wer das Rezept sehen darf - mehrere Personen moeglich. Leeres Array = fuer alle in der
    // Familie sichtbar. Privat = nur die erstellende Person steht drin. Gleiches Prinzip wie
    // Event::setSharedWith(): erst alles loeschen, dann neu einfuegen.
    public function setSharedWith($recipeId, array $userIds) {
        $stmt = $this->pdo->prepare("DELETE FROM recipe_shared_with WHERE recipe_id = ?");
        $stmt->execute([$recipeId]);

        $stmt = $this->pdo->prepare("INSERT INTO recipe_shared_with (recipe_id, user_id) VALUES (?, ?)");
        foreach (array_unique($userIds) as $userId) {
            $stmt->execute([$recipeId, $userId]);
        }
    }

    // Fuer das Formular: welche User-Ids sind gerade eingetragen (leer = fuer alle)
    public function getSharedWithIds($recipeId) {
        $stmt = $this->pdo->prepare("SELECT user_id FROM recipe_shared_with WHERE recipe_id = ?");
        $stmt->execute([$recipeId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Darf diese Person das Rezept sehen?
    public function isVisibleToUser($recipeId, $userId) {
        $stmt = $this->pdo->prepare("SELECT 1 FROM recipes WHERE id = ? AND " . $this->visibilityCondition());
        $stmt->execute([$recipeId, $userId, $userId]);
        return (bool) $stmt->fetch();
    }

    // Sichtbar, wenn man es selbst erstellt hat, ODER niemand eingetragen ist (= fuer alle),
    // ODER man selbst eingetragen ist. Braucht genau zwei "?" (owner_id, dann user_id).
    private function visibilityCondition() {
        return "(owner_id = ?
            OR NOT EXISTS (SELECT 1 FROM recipe_shared_with WHERE recipe_shared_with.recipe_id = recipes.id)
            OR EXISTS (SELECT 1 FROM recipe_shared_with WHERE recipe_shared_with.recipe_id = recipes.id AND recipe_shared_with.user_id = ?))";
    }

}
?>
