<?php

// Login-Sperre nach zu vielen Fehlversuchen (Schutz gegen Passwort-Durchprobieren).
//
// Drei Regeln, die erste die greift, sperrt:
//   1. Dieselbe E-Mail von derselben IP:  3 Fehlversuche in 5 Min  -> 5 Min Sperre
//      (die "normale" Sperre - Fremde koennen damit nicht dein Konto sperren)
//   2. Dieselbe E-Mail von beliebigen IPs: 10 Fehlversuche in 15 Min -> 15 Min Sperre
//      (gegen Angriffe auf EIN Konto von vielen Rechnern aus)
//   3. Dieselbe IP auf beliebige Konten:  20 Fehlversuche in 15 Min -> 15 Min Sperre
//      (gegen das Durchprobieren vieler gestohlener Zugangsdaten)
//
// Gespeichert werden nur Fehlversuche, E-Mail und IP nur als Hash, und nur 24 Stunden.
// Es wird auch bei E-Mail-Adressen ohne Konto gezaehlt - so verraet die Sperre nicht,
// welche Adressen registriert sind.
class LoginThrottle {
    private PDO $pdo;

    // [Spalte(n), max. Fehlversuche, Zeitfenster/Sperrdauer in Minuten]
    private const RULES = [
        [['email_hash', 'ip_hash'], 3, 5],
        [['email_hash'], 10, 15],
        [['ip_hash'], 20, 15],
    ];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Wie viele Sekunden ist der Login noch gesperrt? 0 = nicht gesperrt.
    public function secondsLocked($email, $ip) {
        $values = ['email_hash' => $this->hashEmail($email), 'ip_hash' => $this->hashIp($ip)];
        $longest = 0;

        foreach (self::RULES as [$columns, $maxAttempts, $minutes]) {
            $where = implode(' AND ', array_map(fn($c) => "`$c` = ?", $columns));
            $params = array_map(fn($c) => $values[$c], $columns);

            // Wie viele Fehlversuche im Zeitfenster, und wie viele Sekunden bis
            // (letzter Fehlversuch + Sperrdauer)?
            $stmt = $this->pdo->prepare("SELECT COUNT(*) AS anzahl,
                    TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(MAX(attempted_at), INTERVAL ? MINUTE)) AS rest
                FROM login_attempts
                WHERE $where AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
            $stmt->execute(array_merge([$minutes], $params, [$minutes]));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ((int) $row['anzahl'] >= $maxAttempts) {
                $longest = max($longest, (int) $row['rest']);
            }
        }
        return $longest;
    }

    // Fehlversuch merken (und dabei alte Eintraege aufraeumen)
    public function recordFailure($email, $ip) {
        $stmt = $this->pdo->prepare("INSERT INTO login_attempts (email_hash, ip_hash) VALUES (?, ?)");
        $stmt->execute([$this->hashEmail($email), $this->hashIp($ip)]);

        $this->pdo->exec("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    }

    // Nach erfolgreichem Login: Fehlversuche dieser E-Mail von dieser IP vergessen
    public function clear($email, $ip) {
        $stmt = $this->pdo->prepare("DELETE FROM login_attempts WHERE email_hash = ? AND ip_hash = ?");
        $stmt->execute([$this->hashEmail($email), $this->hashIp($ip)]);
    }

    // Echte Besucher-IP. Hinter dem Cloudflare-Tunnel kommt jede Anfrage von cloudflared
    // auf dem Pi selbst (127.0.0.1) - die echte IP steht dann im Header CF-Connecting-IP.
    // Dem Header wird NUR vertraut, wenn die Anfrage wirklich vom Pi selbst kommt,
    // sonst koennte jeder im Heimnetz eine falsche IP mitschicken.
    public static function clientIp() {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        $cfIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
        if (in_array($remote, ['127.0.0.1', '::1'], true) && filter_var($cfIp, FILTER_VALIDATE_IP)) {
            return $cfIp;
        }
        return $remote;
    }

    private function hashEmail($email) {
        return hash('sha256', 'email|' . mb_strtolower(trim((string) $email)));
    }

    private function hashIp($ip) {
        return hash('sha256', 'ip|' . $ip);
    }
}
