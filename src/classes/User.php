<?php

class User{
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }


    public function findByEmail($email){
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findUserByFamilyId($famId) {
        // Korrektur: Die Familie steht nicht mehr in users.fam_id, sondern in der Zwischentabelle family_members.
        // JOIN verbindet users.id mit family_members.user_id, gefiltert wird ueber family_members.fam_id.
        // users.* liefert nur die Spalten des Users (id, firstname, lastname, ...), nicht die der Zwischentabelle.
        $stmt = $this->pdo->prepare("SELECT users.* FROM users
            JOIN family_members ON users.id = family_members.user_id
            WHERE family_members.fam_id = ?");
        $stmt->execute([$famId]);
        return $stmt->fetchAll();
    }

    public function createUser($firstname, $lastname, $email, $pw_hash) {
        $stmt = $this->pdo->prepare("INSERT INTO users (firstname, lastname, email, pw_hash) VALUES (?, ?, ?, ?)");
        $stmt->execute([$firstname, $lastname, $email, $pw_hash]);
        // Neue User-Id zurückgeben, wie bei Family::createFamily
        return $this->pdo->lastInsertId();
    }

    // E-Mail-Adresse als bestaetigt markieren (Klick auf den Link in der Bestaetigungsmail)
    public function markEmailVerified($userId) {
        $stmt = $this->pdo->prepare("UPDATE users SET email_verified_at = NOW() WHERE id = ? AND email_verified_at IS NULL");
        $stmt->execute([$userId]);
    }

    // Neues Passwort (schon gehasht) speichern - z.B. nach "Passwort vergessen"
    public function updatePassword($userId, $pwHash) {
        $stmt = $this->pdo->prepare("UPDATE users SET pw_hash = ? WHERE id = ?");
        $stmt->execute([$pwHash, $userId]);
    }

    // Eigene Terminfarbe im Familienkalender (nicht zu verwechseln mit calendar_family_settings,
    // das ist die Farbe pro Familie in "Mein Kalender" - hier geht's um die Farbe pro Person
    // innerhalb EINER Familie, damit man die eigenen Termine von denen anderer unterscheiden kann)
    public function setCalendarColor($userId, $color) {
        $stmt = $this->pdo->prepare("UPDATE users SET calendar_color = ? WHERE id = ?");
        $stmt->execute([$color, $userId]);
    }

    public function updateProfile($userId, $firstname, $lastname, $email, $birthdate, $avatar = null) {
    if ($avatar !== null) {
        $stmt = $this->pdo->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, birthdate = ?, avatar = ? WHERE id = ?");
        $stmt->execute([$firstname, $lastname, $email, $birthdate, $avatar, $userId]);
    } else {
        $stmt = $this->pdo->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, birthdate = ? WHERE id = ?");
        $stmt->execute([$firstname, $lastname, $email, $birthdate, $userId]);
    }
}

}
?>