# FamilyFlow – Lernprojekt (MVP)

Familien-Organizer-Web-App (wie Cozi/FamilyWall). Tech-Stack: PHP + MySQL.
Wird Schritt für Schritt gemeinsam erarbeitet (Übung für die LAP) – kein fertiger Code von Claude, außer explizit angefragt.

## MVP-Umfang

- [x] Familien-Account mit mehreren Nutzern (Login pro Person), inkl. mehrere Familien pro Person (n:m über `family_members`)
- [x] Registrierung (nur User; Familie wird danach separat erstellt)
- [x] Weitere Familienmitglieder hinzufügen – `AddMember.php` fertig: neuer User wird direkt der aktuellen Familie zugeordnet. Bestehende Person per Email hinzufügen fehlt noch
- [x] Login-System (personenbezogen) – leitet jetzt zu `SelectFamily.php`, setzt selbst kein `fam_id` mehr
- [~] Dashboard: Familienname und Mitglieder werden angezeigt; Avatare und Platzhalter-Kacheln (Kalender, Listen, Essensplaner, Budget) fehlen; Link zurück zu `SelectFamily.php` (Familie wechseln) fehlt noch

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

## Geplante Schema-Änderung: mehrere Familien pro Person (Entscheidung 2026-09-21)

Eine Person soll in mehreren Familien sein können. Deshalb wandert die Zuordnung aus `users` in eine Zwischentabelle (n:m).

**family_members** (neu)
| Spalte | Typ | Hinweis |
|---|---|---|
| id | INT, AUTO_INCREMENT, PK | |
| user_id | INT (signed, wie `users.id`), FK → users.id | ON DELETE CASCADE |
| fam_id | INT UNSIGNED, FK → families.id | ON DELETE CASCADE |
| role | ENUM('admin','member') | Rolle pro Familie, Default `'member'` |
| created_at | TIMESTAMP | |
| | UNIQUE (`user_id`, `fam_id`) | niemand doppelt in derselben Familie |

`users.fam_id` und `users.role` fallen danach weg (erst **am Ende** entfernen, wenn aller Code umgestellt ist).

**Ablauf (fertig, Stand 2026-09-23):** Login (setzt nur `uid`) → `SelectFamily.php` (Liste der eigenen Familien via `Family::findFamiliesByUserId` + Link pro Familie) → Klick prüft mit `isMember()` die Mitgliedschaft, setzt erst dann `$_SESSION['fam_id']` → Redirect zum Dashboard. Kompletter Ablauf im Browser getestet (eigene Familie wählen funktioniert, fremde Id in der URL wird von `isMember()` abgelehnt).

**Umbau-Reihenfolge:**
1. ~~Zwischentabelle in HeidiSQL anlegen (mit FKs + UNIQUE)~~ – erledigt (2026-09-21): `family_members` mit `fk_members_family`, `kf_members_user` (Tippfehler im Namen, harmlos), `uq_user_family` UNIQUE (`user_id`, `fam_id`), `idx_fam_id` (KEY) für den FK, `role` NOT NULL Default `'member'`
2. ~~Testdaten umziehen (`INSERT ... SELECT` aus `users`)~~ – erledigt: hanna → Familie 2, herbert → Familie 1
3. ~~Klassen: `Family::findByUser`, `Family::addMember`, `Family::isMember`, `User::findByFamilyId` als JOIN, `createFamily` legt Mitgliedschaft an~~ – erledigt (2026-09-21/22)
4. ~~Seiten: `SelectFamily.php` (neu), Login-Redirect, `CreateFamily.php` (Guard "hat schon Familie" entfällt), Dashboard-Guard, `AddMember.php`~~ – erledigt (2026-09-23), Dashboard-Guard war schon vorher richtig (prüft nur `isset`)
5. Spalten `users.fam_id`/`users.role` entfernen – **noch offen**, siehe Aufräumliste unten
6. **Neu offen:** Wechsel-Link im Dashboard zurück zu `SelectFamily.php`
7. **Neu offen:** Bei genau EINER Familie automatisch durchleiten (kein Klick auf der Auswahlseite nötig) – aktuell landet man immer erst auf `SelectFamily.php`, auch mit nur einer Familie
8. **Neu (2026-09-23):** `Data.txt` mit Demo-Zugangsdaten für die Lehrer-Vorführung angelegt (4 Demo-Familien, 7 Demo-User, Passwort `123456543`, über die echten Klassen `createUser`/`createFamily`/`addMember` eingefügt – nicht per Hand-SQL). Lisa und Sara sind bewusst in je zwei Familien, um `SelectFamily.php` vorzuführen. **Nicht committen/pushen** – enthält Klartext-Passwort, gehört nicht ins Repo

## Bausteine (Reihenfolge)

- [x] 1. DB-Schema (in HeidiSQL angelegt: `families`, `users`, später `family_members`)
- [x] 2. DB-Verbindung in PHP (PDO) aufbauen
- [x] 3. Registrierung: Formular + Insert (nur User, ohne Familie – siehe Ablauf-Änderung unten)
- [x] 4. Passwort-Hashing verstehen & einbauen (`password_hash`/`password_verify`)
- [x] 5. Login: Formular + Session starten (`UserLogin.php`, getestet)
- [x] 6. Zugriffsschutz (nicht eingeloggt → Redirect zu `UserLogin.php`), in `Dashboard.php` umgesetzt
- [x] 7. Dashboard: Familienname + Mitglieder laden und anzeigen (mit `htmlspecialchars`)
- [x] 7a. Familie erstellen (`CreateFamily.php`, schreibt jetzt in `family_members`)
- [ ] 7b. Mehrere Familien pro Person: Klassen (`Family` fertig, `User::findUserByFamilyId` als JOIN fertig), dann Seiten: `FamilySelect.php` (neu), Login-Redirect, Dashboard-Guard, Familienwechsel-Link
- [ ] 8. Mitglied hinzufügen (jedes Mitglied darf, nicht nur Admin): neuen User anlegen (`createUser` mit `$famId`/Mitgliedschaft) und bestehende Person per E-Mail hinzufügen
- [x] 9. Logout (`UserLogout.php`, Link im Dashboard)

## Offene Entscheidungen / Notizen

- Mitglieder hinzufügen: Admin legt Login direkt an (MVP) statt E-Mail-Einladung mit Token (kommt später, braucht Mailversand)
- Platzhalter-Kacheln für Kalender/Listen/Essensplaner/Budget – nur UI, keine Funktion im MVP
- **Später: Ordnerstruktur.** Seiten liegen bewusst noch im Hauptordner (wie bei Bakershop/gameshop). Umbau nach Bereich (`auth/`, `family/`) erst bei ca. 15 Dateien oder neuem großen Bereich (Kalender/Listen). Dann als eigener Commit, danach alle Wege im Browser testen (Redirects, Links, `require_once`-Pfade). Wiederkehrende Teile später in `src/components/`.
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
- 2026-09-21 (abends): Design-Änderung: eine Person kann in mehreren Familien sein (n:m über `family_members`), Auswahlseite nach dem Login. `AddMember.php` (Formular steht, Logik fehlt) wird erst nach dem Umbau fertiggebaut. Nächster Schritt: Zwischentabelle in HeidiSQL anlegen.
- 2026-09-21 (spät): Zwischentabelle `family_members` fertig (Indizes, FKs, `role`), Testdaten per `INSERT ... SELECT` umgezogen. Alte Spalten `users.fam_id`/`users.role` bleiben vorerst stehen (App läuft weiter). Nächster Schritt: Klassen umbauen (`Family::findByUser`, `addMember`, `isMember`, `User::findByFamilyId` als JOIN, `createFamily` legt Mitgliedschaft an).
- 2026-09-21 (spät): `Family::findFamiliesByUserId($userId)` fertig und getestet (erster eigener JOIN). Danach `Family::addMember($userId, $famId)` und `Family::isMember($userId, $famId)` fertig und getestet. Offen bei den Klassen: `User::findByFamilyId` als JOIN, `createFamily` legt Mitgliedschaft an (mit `addMember`).
- 2026-09-21 (Tagesabschluss): Aufgeräumt: `tests/dbTest.php` entfernt (das Skript rief `connect()` nie auf und meldete trotzdem "funktioniert", getestet wird im Browser). PLAN.md auf Stand gebracht. **Zwischenstand:** Login und Dashboard arbeiten noch mit der alten Spalte `users.fam_id`, `CreateFamily.php` schreibt schon in `family_members`. Wer eine Familie erstellt und sich neu einloggt, landet wieder bei "Familie erstellen", bis die Auswahlseite steht. `User::findUserByFamilyId` liest schon über die Zwischentabelle.
  - **Aufräumliste offen:** `bdconfig.example.php` als Vorlage anlegen; `User::setFamily()` löschen (keine Aufrufer mehr); FK-Name `kf_members_user` → `fk_members_user` (kosmetisch); nach der Auswahlseite alte Spalten `users.fam_id`/`users.role` entfernen; doppelten Validierungscode aus `UserRegister.php`/`AddMember.php` zusammenführen; Ordnerstruktur (siehe Notiz oben)
  - **Nächster Schritt:** `FamilySelect.php`
- 2026-09-22: `AddMember.php` fertiggebaut (war unfertig kopiert, Button-Check zeigte auf falschen Namen, `createUser()` gab keine Id zurück). `createUser()` gibt jetzt per `lastInsertId()` die neue Id zurück, `AddMember.php` trägt den neuen User direkt per `Family::addMember()` in die aktuelle Familie ein. Tote/kaputte Kopie von `findFamiliesByUserId` in `User.php` gelöscht. `UEBERSICHT.txt` angelegt (kurze Datei-Referenz: was macht was, woher kommen die Daten) – gleiche Idee auch für das Projekt `temperatur2209` erstellt.
- 2026-09-23: `SelectFamily.php` gebaut (Name bewusst so, nicht `FamilySelect.php` – passt zu `CreateFamily.php`). Zeigt Familien der Person (`findFamiliesByUserId`), Klick prüft `isMember()` und setzt erst dann `$_SESSION['fam_id']`, dann Redirect zum Dashboard. `UserLogin.php` setzt kein `fam_id` mehr selbst, leitet zu `SelectFamily.php`. Login-Link auf `index.php` ergänzt (fehlte). Kompletter Ablauf (Login → Familie wählen → Dashboard) im Browser getestet, inkl. Sicherheitscheck (fremde Familien-Id in der URL wird abgelehnt). Nächster Schritt: Wechsel-Link im Dashboard zu `SelectFamily.php`, danach Aufräumliste (siehe 2026-09-21).
- 2026-09-19: `role = 'child'` kurz diskutiert und wieder verworfen (siehe oben). gameshop-Vorlage (vom Lehrer) als Referenz für Validierung entdeckt. `Validation`-Klasse (`src/classes/Validation.php`) mit `required()`, `email()`, `minlength()`, `matches()` fertig nachgebaut. Nächster Schritt: `User::findByEmail()` ergänzen, dann `UserRegister.php` auf Fehler-Array + Post/Redirect/Get umbauen, danach Login.
