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

    // Termin aktualisieren (gleiche Felder wie beim Anlegen)
    public function updateEvent($eventId, $title, $startAt, $endAt, $location, $description, $sharedWith, $allDay)
    {
        $stmt = $this->pdo->prepare("UPDATE events SET title = ?, start_at = ?, end_at = ?, location = ?, description = ?, shared_with = ?, all_day = ? WHERE id = ?");
        $stmt->execute([$title, $startAt, $endAt, $location, $description, $sharedWith, $allDay, $eventId]);
    }

    public function deleteEvent($eventId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
    }

    // Alle Termine einer Person fuer einen bestimmten Monat (fuers Monats-Raster).
    // Ueberlappungs-Check (start_at <= Monatsende UND end_at >= Monatsanfang) statt nur
    // "start_at im Monat", damit mehrtaegige Termine auch dann auftauchen, wenn sie in
    // einem frueheren Monat begonnen haben und in diesen hineinragen.
    public function getEventsForUserInMonth($userId, $famId, $year, $month)
    {
        $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $end = date('Y-m-t 23:59:59', strtotime($start));

        $stmt = $this->pdo->prepare("SELECT * FROM events
            WHERE fam_id = ? AND (shared_with IS NULL OR shared_with = ? OR owner_id = ?)
            AND start_at <= ? AND end_at >= ?
            ORDER BY start_at ASC");
        $stmt->execute([$famId, $userId, $userId, $end, $start]);
        return $stmt->fetchAll();
    }

    // Alle Termine einer Person für einen einzelnen Tag (Tages-Ansicht), gleicher
    // Ueberlappungs-Check wie oben
    public function getEventsForUserOnDay($userId, $famId, $year, $month, $day)
    {
        $start = sprintf('%04d-%02d-%02d 00:00:00', $year, $month, $day);
        $end = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $day);

        $stmt = $this->pdo->prepare("SELECT * FROM events
            WHERE fam_id = ? AND (shared_with IS NULL OR shared_with = ? OR owner_id = ?)
            AND start_at <= ? AND end_at >= ?
            ORDER BY start_at ASC");
        $stmt->execute([$famId, $userId, $userId, $end, $start]);
        return $stmt->fetchAll();
    }

    // "Mein Kalender": Termine aus ALLEN Familien der Person fuer einen Monat, nicht nur
    // der gerade aktiven. Join ueber family_members statt fam_id = ?, damit jede Familie
    // mitzaehlt, in der die Person Mitglied ist. families.name wird mitgeladen, damit man
    // auf der Uebersicht sieht, zu welcher Familie ein Termin gehoert.
    public function getEventsForUserAcrossFamiliesInMonth($userId, $year, $month)
    {
        $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $end = date('Y-m-t 23:59:59', strtotime($start));

        $stmt = $this->pdo->prepare("SELECT events.*, families.name AS family_name FROM events
            JOIN family_members ON family_members.fam_id = events.fam_id AND family_members.user_id = ?
            JOIN families ON families.id = events.fam_id
            WHERE (events.shared_with IS NULL OR events.shared_with = ? OR events.owner_id = ?)
            AND events.start_at <= ? AND events.end_at >= ?
            ORDER BY events.start_at ASC");
        $stmt->execute([$userId, $userId, $userId, $end, $start]);
        return $stmt->fetchAll();
    }

    // Gleiches Prinzip wie oben, nur fuer einen einzelnen Tag (Tages-Ansicht von "Mein Kalender")
    public function getEventsForUserAcrossFamiliesOnDay($userId, $year, $month, $day)
    {
        $start = sprintf('%04d-%02d-%02d 00:00:00', $year, $month, $day);
        $end = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $day);

        $stmt = $this->pdo->prepare("SELECT events.*, families.name AS family_name FROM events
            JOIN family_members ON family_members.fam_id = events.fam_id AND family_members.user_id = ?
            JOIN families ON families.id = events.fam_id
            WHERE (events.shared_with IS NULL OR events.shared_with = ? OR events.owner_id = ?)
            AND events.start_at <= ? AND events.end_at >= ?
            ORDER BY events.start_at ASC");
        $stmt->execute([$userId, $userId, $userId, $end, $start]);
        return $stmt->fetchAll();
    }
}
