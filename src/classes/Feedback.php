<?php

class Feedback {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Leere Felder werden als NULL gespeichert statt als leerer String
    public function createFeedback($userId, array $fields) {
        $stmt = $this->pdo->prepare("INSERT INTO feedback
            (user_id, dashboard, listen, kalender, meine_familie, rezepte, aufgaben, layout)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $fields['dashboard'] !== '' ? $fields['dashboard'] : null,
            $fields['listen'] !== '' ? $fields['listen'] : null,
            $fields['kalender'] !== '' ? $fields['kalender'] : null,
            $fields['meine_familie'] !== '' ? $fields['meine_familie'] : null,
            $fields['rezepte'] !== '' ? $fields['rezepte'] : null,
            $fields['aufgaben'] !== '' ? $fields['aufgaben'] : null,
            $fields['layout'] !== '' ? $fields['layout'] : null,
        ]);
    }

    // Alle Rueckmeldungen, neueste zuerst, inkl. Name der Person die sie abgeschickt hat
    public function getAllFeedback() {
        $stmt = $this->pdo->query("SELECT feedback.*, users.firstname, users.lastname
            FROM feedback
            JOIN users ON users.id = feedback.user_id
            ORDER BY feedback.created_at DESC");
        return $stmt->fetchAll();
    }
}
