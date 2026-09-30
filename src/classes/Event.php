<?php

class Event
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Neuen Termin anlegen, gibt neue Id zurück
    public function createEvent($famId, $ownerId, $title, $startAt, $endAt, $location, $description, $sharedWith, $allDay)
    {
        $stmt = $this->pdo->prepare("INSERT INTO events (fam_id, owner_id, title, start_at, end_at, location, description, shared_with, all_day) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$famId, $ownerId, $title, $startAt, $endAt, $location, $description, $sharedWith, $allDay]);
        return $this->pdo->lastInsertId();
    }

    // Alle Termine die eine Person sehen darf: eigene "für alle", oder extra für sie geteilte
    public function getEventForUsers($userId, $famId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM events
            WHERE fam_id = ? AND (shared_with IS NULL OR shared_with = ? OR owner_id = ?)
            ORDER BY start_at ASC");
        $stmt->execute([$famId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    // Einen einzelnen Termin per Id holen
    public function getEventById($eventId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        return $stmt->fetch();
    }

    // Alle Termine einer Person fuer einen bestimmten Monat (fuers Monats-Raster)
    public function getEventsForUserInMonth($userId, $famId, $year, $month)
    {
        $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $end = date('Y-m-t 23:59:59', strtotime($start));

        $stmt = $this->pdo->prepare("SELECT * FROM events
            WHERE fam_id = ? AND (shared_with IS NULL OR shared_with = ? OR owner_id = ?)
            AND start_at BETWEEN ? AND ?
            ORDER BY start_at ASC");
        $stmt->execute([$famId, $userId, $userId, $start, $end]);
        return $stmt->fetchAll();
    }

    // Alle Termine einer Person für einen einzelnen Tag (Tages-Ansicht)
    public function getEventsForUserOnDay($userId, $famId, $year, $month, $day)
    {
        $start = sprintf('%04d-%02d-%02d 00:00:00', $year, $month, $day);
        $end = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $day);

        $stmt = $this->pdo->prepare("SELECT * FROM events
            WHERE fam_id = ? AND (shared_with IS NULL OR shared_with = ? OR owner_id = ?)
            AND start_at BETWEEN ? AND ?
            ORDER BY start_at ASC");
        $stmt->execute([$famId, $userId, $userId, $start, $end]);
        return $stmt->fetchAll();
    }
}
