<?php

class Event
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Neuen Termin anlegen, gibt neue Id zurück. Wer ihn sehen darf, wird separat per
    // setSharedWith() gesetzt (mehrere Personen moeglich, siehe event_shared_with)
    public function createEvent($famId, $ownerId, $title, $startAt, $endAt, $location, $description, $allDay)
    {
        $stmt = $this->pdo->prepare("INSERT INTO events (fam_id, owner_id, title, start_at, end_at, location, description, all_day) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$famId, $ownerId, $title, $startAt, $endAt, $location, $description, $allDay]);
        return $this->pdo->lastInsertId();
    }

    // Alle Termine die eine Person sehen darf: eigene "für alle", oder extra für sie geteilte
    public function getEventForUsers($userId, $famId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM events
            WHERE fam_id = ? AND " . $this->visibilityCondition() . "
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

    // Termin aktualisieren (gleiche Felder wie beim Anlegen, shared_with laeuft separat ueber setSharedWith())
    public function updateEvent($eventId, $title, $startAt, $endAt, $location, $description, $allDay)
    {
        $stmt = $this->pdo->prepare("UPDATE events SET title = ?, start_at = ?, end_at = ?, location = ?, description = ?, all_day = ? WHERE id = ?");
        $stmt->execute([$title, $startAt, $endAt, $location, $description, $allDay, $eventId]);
    }

    public function deleteEvent($eventId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
    }

    // Wen ein Termin betrifft/wer ihn sehen darf - mehrere Personen moeglich (statt wie
    // frueher nur eine einzige). Leeres Array = fuer alle Familienmitglieder sichtbar
    // (gleiche Bedeutung wie das alte shared_with = NULL). Erst alles loeschen, dann neu
    // einfuegen, gleiches Prinzip wie Chore::setAccess()/setRotationUserIds().
    public function setSharedWith($eventId, array $userIds)
    {
        $stmt = $this->pdo->prepare("DELETE FROM event_shared_with WHERE event_id = ?");
        $stmt->execute([$eventId]);

        $stmt = $this->pdo->prepare("INSERT INTO event_shared_with (event_id, user_id) VALUES (?, ?)");
        foreach (array_unique($userIds) as $userId) {
            $stmt->execute([$eventId, $userId]);
        }
    }

    // Fuer das Formular: welche User-Ids sind gerade als "betroffen" eingetragen
    public function getSharedWithIds($eventId)
    {
        $stmt = $this->pdo->prepare("SELECT user_id FROM event_shared_with WHERE event_id = ?");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Darf diese Person den Termin ueberhaupt sehen? Ja, wenn sie der/die Erstellende ist,
    // ODER der Termin fuer alle sichtbar ist (keine Zeilen in event_shared_with), ODER sie
    // explizit als betroffene Person eingetragen wurde
    public function isVisibleToUser($eventId, $userId)
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM events WHERE id = ? AND " . $this->visibilityCondition());
        $stmt->execute([$eventId, $userId, $userId]);
        return (bool) $stmt->fetch();
    }

    // Gemeinsame Sichtbarkeits-Bedingung fuer events.owner_id/events.id - braucht in dieser
    // Reihenfolge genau zwei "?" (einmal fuer owner_id, einmal fuer die EXISTS-Pruefung)
    private function visibilityCondition()
    {
        return "(owner_id = ?
            OR NOT EXISTS (SELECT 1 FROM event_shared_with WHERE event_shared_with.event_id = events.id)
            OR EXISTS (SELECT 1 FROM event_shared_with WHERE event_shared_with.event_id = events.id AND event_shared_with.user_id = ?))";
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
            WHERE fam_id = ? AND " . $this->visibilityCondition() . "
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
            WHERE fam_id = ? AND " . $this->visibilityCondition() . "
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
            WHERE " . $this->visibilityCondition() . "
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
            WHERE " . $this->visibilityCondition() . "
            AND events.start_at <= ? AND events.end_at >= ?
            ORDER BY events.start_at ASC");
        $stmt->execute([$userId, $userId, $userId, $end, $start]);
        return $stmt->fetchAll();
    }

    // Geburtstage werden nicht in der DB gespeichert, sondern aus users.birthdate berechnet -
    // so gibt es nie doppelte Eintraege und sie aktualisieren sich automatisch, wenn jemand sein
    // Geburtsdatum aendert oder die Familie verlaesst. Jahr kommt vom aktuell angezeigten Monat.
    public function getBirthdaysForFamilyInMonth($famId, $year, $month)
    {
        $stmt = $this->pdo->prepare("SELECT users.id, users.firstname, users.lastname, users.birthdate
            FROM users
            JOIN family_members ON family_members.user_id = users.id
            WHERE family_members.fam_id = ? AND users.birthdate IS NOT NULL AND MONTH(users.birthdate) = ?");
        $stmt->execute([$famId, $month]);
        return $this->toBirthdayEvents($stmt->fetchAll(), $year, $famId);
    }

    public function getBirthdaysForFamilyOnDay($famId, $year, $month, $day)
    {
        $stmt = $this->pdo->prepare("SELECT users.id, users.firstname, users.lastname, users.birthdate
            FROM users
            JOIN family_members ON family_members.user_id = users.id
            WHERE family_members.fam_id = ? AND users.birthdate IS NOT NULL
            AND MONTH(users.birthdate) = ? AND DAY(users.birthdate) = ?");
        $stmt->execute([$famId, $month, $day]);
        return $this->toBirthdayEvents($stmt->fetchAll(), $year, $famId);
    }

    // "Mein Kalender": Geburtstage aus ALLEN eigenen Familien, per DISTINCT auf die User-Id
    // dedupliziert - eine Person, die in mehreren gemeinsamen Familien ist, taucht nur einmal auf
    public function getBirthdaysForUserAcrossFamiliesInMonth($userId, $year, $month)
    {
        $stmt = $this->pdo->prepare("SELECT DISTINCT users.id, users.firstname, users.lastname, users.birthdate
            FROM users
            JOIN family_members ON family_members.user_id = users.id
            WHERE family_members.fam_id IN (SELECT fam_id FROM family_members WHERE user_id = ?)
            AND users.birthdate IS NOT NULL AND MONTH(users.birthdate) = ?");
        $stmt->execute([$userId, $month]);
        return $this->toBirthdayEvents($stmt->fetchAll(), $year, null);
    }

    public function getBirthdaysForUserAcrossFamiliesOnDay($userId, $year, $month, $day)
    {
        $stmt = $this->pdo->prepare("SELECT DISTINCT users.id, users.firstname, users.lastname, users.birthdate
            FROM users
            JOIN family_members ON family_members.user_id = users.id
            WHERE family_members.fam_id IN (SELECT fam_id FROM family_members WHERE user_id = ?)
            AND users.birthdate IS NOT NULL AND MONTH(users.birthdate) = ? AND DAY(users.birthdate) = ?");
        $stmt->execute([$userId, $month, $day]);
        return $this->toBirthdayEvents($stmt->fetchAll(), $year, null);
    }

    // Baut aus Mitglieder-Zeilen virtuelle, ganztaegige "Termine" in der gleichen Form wie
    // echte Events, damit Calendar.php/Day.php sie ohne Sonderfaelle mitrendern koennen
    private function toBirthdayEvents($members, $year, $famId)
    {
        $result = [];
        foreach ($members as $member) {
            $month = (int) date('m', strtotime($member->birthdate));
            $day = (int) date('d', strtotime($member->birthdate));
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

            $event = new stdClass();
            $event->id = null;
            $event->fam_id = $famId;
            $event->owner_id = $member->id;
            $event->title = '🎂 ' . $member->firstname . ' ' . $member->lastname . ' hat Geburtstag';
            $event->start_at = $date . ' 00:00:00';
            $event->end_at = $date . ' 23:59:59';
            $event->location = null;
            $event->description = null;
            $event->all_day = 1;
            $event->isBirthday = true;
            $result[] = $event;
        }
        return $result;
    }
}
