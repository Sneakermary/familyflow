<?php

class Chore {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    //Neues Ämtli anlegen, gibt die neue Id zurück. Feste Zustaendige (auch mehrere)
    // werden separat per setAssignees() gesetzt, Rotations-Teilnehmer per setRotationUserIds()
    public function createChore($famId, $ownerId, $name, $visibility, $rotation, $rotationStart) {
        $stmt = $this->pdo->prepare("INSERT INTO chores (fam_id, owner_id, name, visibility, rotation, rotation_start) VALUES(?, ?, ?, ?, ?, ?)");
        $stmt->execute([$famId, $ownerId, $name, $visibility, $rotation, $rotationStart]);
        return $this->pdo->lastInsertId();
    }

    // Feste Zustaendige (ohne Rotation) - mehrere Personen moeglich, leer = noch niemand
    public function getAssigneeIds($choreId) {
        $stmt = $this->pdo->prepare("SELECT user_id FROM chore_assignees WHERE chore_id = ?");
        $stmt->execute([$choreId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Feste Zustaendige komplett ersetzen
    public function setAssignees($choreId, array $userIds) {
        $this->pdo->prepare("DELETE FROM chore_assignees WHERE chore_id = ?")->execute([$choreId]);
        $stmt = $this->pdo->prepare("INSERT INTO chore_assignees (chore_id, user_id) VALUES (?, ?)");
        foreach (array_unique($userIds) as $userId) {
            $stmt->execute([$choreId, $userId]);
        }
    }

    public function getChoreById($choreId) {
        $stmt = $this->pdo->prepare("SELECT * FROM chores WHERE id = ?");
        $stmt->execute([$choreId]);
        return $stmt->fetch();
    }

    // Alle Ämtli der Familie, die diese Person sehen darf
    public function getChoresForUser($userId, $famId) {
        $stmt = $this->pdo->prepare("SELECT * FROM chores WHERE fam_id = ?");
        $stmt->execute([$famId]);
        $chores = $stmt->fetchAll();

        $visible = [];
        foreach ($chores as $chore) {
            if ($this->isVisibleToUser($chore, $userId)) {
                $visible[] = $chore;
            }
        }
        return $visible;
    }

    // Darf diese Person das Ämtli sehen (und damit auch abhaken)?
    // Ersteller:in und die gerade Zustaendigen sehen es immer, egal welche Sichtbarkeit eingestellt ist.
    // Bei Rotation wird die zustaendige Person live ueber getCurrentAssigneeIds() berechnet.
    public function isVisibleToUser($chore, $userId) {
        if ($chore->visibility === 'all') {
            return true;
        }
        if ($chore->owner_id == $userId) {
            return true;
        }
        if (in_array($userId, $this->getCurrentAssigneeIds($chore))) {
            return true;
        }
        if ($chore->visibility === 'selected') {
            $stmt = $this->pdo->prepare("SELECT 1 FROM chore_access WHERE chore_id = ? AND user_id = ?");
            $stmt->execute([$chore->id, $userId]);
            return (bool) $stmt->fetch();
        }
        return false;
    }

    // Erledigt-Häckchen setzen
    public function markDone($choreId, $userId) {
        $stmt = $this->pdo->prepare("UPDATE chores SET last_done_at = NOW(), done_by = ? WHERE id = ?");
        $stmt->execute([$userId, $choreId]);
    }

    // Erledigt-Status zuruecksetzen
    public function markNotDone($choreId) {
        $stmt = $this->pdo->prepare("UPDATE chores SET last_done_at = NULL, done_by = NULL WHERE id = ?");
        $stmt->execute([$choreId]);
    }

    // Rotations-Teilnehmer in der richtigen Reihenfolge holen
    public function getRotationUserIds($choreId) {
        $stmt = $this->pdo->prepare("SELECT user_id FROM chore_users WHERE chore_id = ? ORDER BY position");
        $stmt->execute([$choreId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Rotations-Teilnehmer komplett ersetzen, Reihenfolge = Reihenfolge im Array
    public function setRotationUserIds($choreId, array $userIds) {
        $this->pdo->prepare("DELETE FROM chore_users WHERE chore_id = ?")->execute([$choreId]);
        $stmt = $this->pdo->prepare("INSERT INTO chore_users (chore_id, user_id, position) VALUES(?, ?, ?)");
        foreach($userIds as $position => $userId) {
            $stmt->execute([$choreId, $userId, $position]);
        }
    }

    //Sichtbarkeit "bestimmte Personen" ersetzen
    public function setAccess($choreId, array $userIds) {
        $this->pdo->prepare("DELETE FROM chore_access WHERE chore_id = ?")->execute([$choreId]);
        $stmt = $this->pdo->prepare("INSERT INTO chore_access (chore_id, user_id) VALUES (?, ?)");
        foreach ($userIds as $userId) {
            $stmt->execute([$choreId, $userId]);
        }
    }

    // Wer ist gerade jetzt zustaendig? Gibt immer ein Array zurueck:
    // ohne Rotation = alle fest Zugeteilten (auch mehrere), mit Rotation = die eine Person,
    // die in diesem Zeitraum dran ist. Leer = noch niemand.
    public function getCurrentAssigneeIds($chore) {
        if ($chore->rotation === 'none') {
            return $this->getAssigneeIds($chore->id);
        }
        $userIds = $this->getRotationUserIds($chore->id);
        if (count($userIds) === 0) {
            return [];
        }
        $start = new DateTime($chore->rotation_start);
        $today = new DateTime('today');
        $periods = $this->periodsSince($start, $today, $chore->rotation);
        return [$userIds[$periods % count($userIds)]];
    }

    // Wie viele ganze Zeitraeume liegen zwischen Start und heute
    private function periodsSince(DateTime $start, DateTime $today, $rotation) {
        $days = $start->diff($today)->days;
        if ($rotation === 'daily') {
            return $days;
        }
        if ($rotation === 'weekly') {
            return intdiv($days, 7);
        }
        $diff = $start->diff($today);
        $months = $diff->y * 12 + $diff->m;
        if ($rotation === 'monthly') {
            return $months;
        }
        if ($rotation === 'half_yearly') {
            return intdiv($months, 6);
        }
        if ($rotation === 'yearly') {
            return intdiv($months, 12);
        }
        return 0;
    }

    // Startdatum des aktuellen Zeitraums berechnen
    private function currentPeriodStart(DateTime $start, $periods, $rotation) {
        $date = clone $start;
        if ($rotation === 'daily') {
            $date->modify("+$periods days");
        } elseif ($rotation === 'weekly') {
            $date->modify("+" . ($periods * 7) . " days");
        } elseif ($rotation === 'monthly') {
            $date->modify("+$periods months");
        } elseif ($rotation === 'half_yearly') {
            $date->modify("+" . ($periods * 6) . " months");
        } elseif ($rotation === 'yearly') {
            $date->modify("+$periods years");
        }
        return $date;
    }

    // Ist das Aemtli im aktuellen Zeitraum schon erledigt?
    public function isDoneInCurrentPeriod($chore) {
        if (!$chore->last_done_at) {
            return false;
        }
        if ($chore->rotation === 'none') {
            return true;
        }
        $start = new DateTime($chore->rotation_start);
        $today = new DateTime('today');
        $periods = $this->periodsSince($start, $today, $chore->rotation);
        $periodStart = $this->currentPeriodStart($start, $periods, $chore->rotation);
        $lastDone = new DateTime($chore->last_done_at);
        return $lastDone >= $periodStart;
    }

    // Aemtli aktualisieren
    public function updateChore($choreId, $name, $visibility, $rotation, $rotationStart) {
        $stmt = $this->pdo->prepare("UPDATE chores SET name = ?, visibility = ?, rotation = ?, rotation_start = ? WHERE id = ?");
        $stmt->execute([$name, $visibility, $rotation, $rotationStart, $choreId]);
    }

    public function deleteChore($choreId) {
        $stmt = $this->pdo->prepare("DELETE FROM chores WHERE id = ?");
        $stmt->execute([$choreId]);
    }

    // Aktuelle "bestimmte Personen"-Liste holen, um die Haken beim Bearbeiten vorauszufüllen
    public function getAccessUserIds($choreId) {
        $stmt = $this->pdo->prepare("SELECT user_id FROM chore_access WHERE chore_id = ?");
        $stmt->execute([$choreId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

}