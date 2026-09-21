# FamilyFlow – Lernprojekt (MVP)

Familien-Organizer-Web-App (wie Cozi/FamilyWall). Tech-Stack: PHP + MySQL.
Wird Schritt für Schritt gemeinsam erarbeitet (Übung für die LAP) – kein fertiger Code von Claude, außer explizit angefragt.

## MVP-Umfang

- [ ] Familien-Account mit mehreren Nutzern (Login pro Person)
- [ ] Registrierung: Familie anlegen + erster Admin-User
- [ ] Weitere Familienmitglieder hinzufügen
- [ ] Login-System (personenbezogen)
- [ ] Dashboard: Familienname, Mitglieder-Avatare, Platzhalter-Kacheln (Kalender, Listen, Essensplaner, Budget – später)

## Datenbank

**families**
| Spalte | Typ | Hinweis |
|---|---|---|
| id | INT, AUTO_INCREMENT, PK | |
| name | VARCHAR | |
| created_at | DATETIME | |

**users**
| Spalte | Typ | Hinweis |
|---|---|---|
| id | INT, AUTO_INCREMENT, PK | |
| fam_id | INT UNSIGNED, NULL erlaubt | FK → families.id, ON DELETE CASCADE. NULL solange User noch keine Familie hat |
| firstname | VARCHAR | |
| lastname | VARCHAR | |
| email | VARCHAR | UNIQUE |
| pw_hash | VARCHAR | nie Klartext speichern (Spalte heißt tatsächlich `pw_hash`, nicht `password_hash`) |
| avatar | VARCHAR, NULL erlaubt | Pfad/Initialen, später. Kein Standardwert bei Registrierung, daher NULL-fähig |
| role | ENUM('admin','member') | Default `'member'`. Bewusst vorerst ungenutzt: alle User bleiben `'member'`, auch wer eine Familie erstellt. Keine unterschiedlichen Rechte – jedes Mitglied darf neue Mitglieder hinzufügen |
| created_at | DATETIME | |

## Bausteine (Reihenfolge)

- [ ] 1. DB-Schema als SQL selbst schreiben (`CREATE DATABASE`, `CREATE TABLE` inkl. FK)
- [x] 2. DB-Verbindung in PHP (PDO) aufbauen
- [x] 3. Registrierung: Formular + Insert (nur User, ohne Familie – siehe Ablauf-Änderung unten)
- [x] 4. Passwort-Hashing verstehen & einbauen (`password_hash`/`password_verify`)
- [x] 5. Login: Formular + Session starten (`UserLogin.php`, getestet)
- [x] 6. Zugriffsschutz (nicht eingeloggt → Redirect zu `UserLogin.php`), in `Dashboard.php` umgesetzt
- [ ] 7. Dashboard: eigene Familie + Mitglieder laden und anzeigen (bisher nur Überschrift + Logout-Link)
- [ ] 8. Mitglied hinzufügen (jedes Mitglied darf, nicht nur Admin)
- [x] 9. Logout (`UserLogout.php`, Link im Dashboard)

## Offene Entscheidungen / Notizen

- Mitglieder hinzufügen: Admin legt Login direkt an (MVP) statt E-Mail-Einladung mit Token (kommt später, braucht Mailversand)
- Platzhalter-Kacheln für Kalender/Listen/Essensplaner/Budget – nur UI, keine Funktion im MVP
- **Verworfen (2026-09-18):** `role = 'child'` mit eigenem Theme/Kalender-Ansicht/Eltern-only-Listen war kurz angedacht, aber wieder verworfen – zu komplex für jetzt. `role` bleibt bei `ENUM('admin','member')`, und "Mitglied hinzufügen" ist nicht mehr auf Admin beschränkt, sondern darf jedes Mitglied.
- **Ablauf geändert (2026-09-18):** Registrierung und Familie-Erstellen sind zwei getrennte Schritte (nicht mehr ein kombiniertes Formular). User registriert sich zuerst ohne Familie (`fam_id` = NULL), erstellt/tritt einer Familie danach separat bei. Deshalb `fam_id` jetzt NULL-fähig. Konsequenz: nach Login muss geprüft werden, ob `fam_id` NULL ist → ggf. zu "Familie erstellen"-Seite umleiten (noch zu klären: was passiert mit `role`, wenn noch keine Familie existiert?)

## Architektur-Muster

Orientiert an zwei früheren Projekten:
- Bakershop (`c:\laragon\www\Bakershop`): `src/classes/Database.php` (`connect()` liefert PDO), `src/classes/User.php` (`User(PDO $pdo)`, `createUser()`, `loginUser()`), Seiten im Root kombinieren Formular + Logik, prüfen `isset($_POST['submitBtnName'])`, binden Klassen per `require_once __DIR__ . '/src/classes/...'` ein
- gameshop (`c:\laragon\www\gameshop`, Vorlage vom Lehrer): `Validator`/`Validation`-Klasse (`required`, `email`, `minlength`, `matches`) + Fehler-Array + Post/Redirect/Get-Muster (Fehler in `$_SESSION['errors']`, Redirect zurück zum Formular), `User::findByEmail()` für Duplikat-Check und Login

## Session-Log

- 2026-09-18: Projektstart, Struktur/Plan besprochen. Nächster Schritt: DB-Schema (Punkt 1) selbst schreiben.
- 2026-09-18: DB-Schema in HeidiSQL angelegt (`families`, `users`). Spalte heißt bewusst `fam_id` (nicht `family_id`), FK auf `families.id` mit ON DELETE CASCADE. Nächster Schritt: DB-Verbindung in PHP (PDO), Punkt 2.
- 2026-09-18: `Database`-Klasse (`src/classes/Database.php`) mit `connect()` fertig, Config in `src/config/bdconfig.php`. Testskript `tests/dbTest.php` erfolgreich ausgeführt (`php tests/dbTest.php`) – Verbindung läuft. Nächster Schritt: Registrierung (Punkt 3).
- 2026-09-18: Design-Entscheidung: Registrierung ohne Familie (User zuerst, Familie später separat) – analog zu Slack. `fam_id` NULL-fähig gemacht. Struktur an Bakershop-Projekt angelehnt (`User`-Klasse + Seiten mit Formular+Logik kombiniert). `name`-Spalte zu `firstname`/`lastname` geändert, `avatar` NULL-fähig gemacht (kein Standardwert bei Registrierung). `User::createUser()` und `UserRegister.php` fertig, erfolgreich getestet (echter User in DB angelegt, Passwort gehasht, `fam_id` NULL, `role` 'member'). Bekannte Lücke: kein Error-Handling bei doppelter Email. `UserLogin.php` existiert noch nicht (Redirect nach Registrierung zeigt aktuell 404). Nächster Schritt: Login (Punkt 5) – vorher ggf. Baustein-Reihenfolge in dieser Datei anpassen, da Ablauf sich geändert hat.
- 2026-09-19/20: Validierung (`Validator`-Klasse, Fehler-Array + PRG) in `UserRegister.php` fertig und getestet. Login (`UserLogin.php`) fertig und getestet. DB-Spalte heißt `pw_hash`. Nächster Schritt: Zugriffsschutz + Logout mit neuer `Dashboard.php`.
- 2026-09-20: Zugriffsschutz (`Dashboard.php`) und Logout (`UserLogout.php`) fertig und getestet. Nächster Schritt: "Familie erstellen" (Login soll bei `fam_id` = NULL dorthin leiten), danach Dashboard mit Familiendaten.
- 2026-09-20: Projekt auf GitHub gepusht (https://github.com/Sneakermary/familyflow, Branch `main`, HTTPS). `src/config/bdconfig.php` steht in `.gitignore` und ist nicht im Repo. `CreateFamily.php` und `Family.php`/`User::setFamily()` sind angelegt, in `CreateFamily.php` fehlt noch das Speichern (Fehlerbehandlung + Family anlegen + setFamily + Session).
- 2026-09-21: "Familie erstellen" fertig und getestet (`CreateFamily.php`, `Family::createFamily`, `User::setFamily`, Session `fam_id`). Nächster Schritt: Dashboard mit Familiendaten (Familienname, Mitglieder), danach Mitglied hinzufügen.
- 2026-09-21 (später): Dashboard zeigt Familienname und Mitglieder (`Family::findById`, `User::findByFamilyId`, `htmlspecialchars` gegen XSS). Nächster Schritt: Mitglied hinzufügen, danach Platzhalter-Kacheln und Avatare.
- 2026-09-19: `role = 'child'` kurz diskutiert und wieder verworfen (siehe oben). gameshop-Vorlage (vom Lehrer) als Referenz für Validierung entdeckt. `Validation`-Klasse (`src/classes/Validation.php`) mit `required()`, `email()`, `minlength()`, `matches()` fertig nachgebaut. Nächster Schritt: `User::findByEmail()` ergänzen, dann `UserRegister.php` auf Fehler-Array + Post/Redirect/Get umbauen, danach Login.
