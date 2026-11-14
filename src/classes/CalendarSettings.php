<?php

// Persoenliche Einstellungen fuer "Mein Kalender": pro Familie, ob ihre Termine angezeigt
// werden sollen und in welcher Farbe. Rein persoenlich (an user_id gebunden), beeinflusst
// nicht, was andere Familienmitglieder sehen.
class CalendarSettings {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Gespeicherte Einstellungen dieser Person, als [fam_id => ['visible' => bool, 'color' => '#rrggbb']]
    public function getSettingsForUser($userId) {
        $stmt = $this->pdo->prepare("SELECT fam_id, visible, color FROM calendar_family_settings WHERE user_id = ?");
        $stmt->execute([$userId]);

        $settings = [];
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row->fam_id] = ['visible' => (bool) $row->visible, 'color' => $row->color];
        }
        return $settings;
    }

    // Einstellungen fuer mehrere Familien auf einmal speichern (upsert: neu oder ueberschreiben)
    public function saveSettings($userId, array $settingsByFamId) {
        $stmt = $this->pdo->prepare("INSERT INTO calendar_family_settings (user_id, fam_id, visible, color)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE visible = VALUES(visible), color = VALUES(color)");

        foreach ($settingsByFamId as $famId => $setting) {
            $stmt->execute([$userId, $famId, $setting['visible'] ? 1 : 0, $setting['color']]);
        }
    }
}
