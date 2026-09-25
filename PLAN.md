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
| firstname | VARCHAR | |
| lastname | VARCHAR | |
| email | VARCHAR | UNIQUE |
| pw_hash | VARCHAR | nie Klartext speichern (Spalte heißt tatsächlich `pw_hash`, nicht `password_hash`) |
| avatar | VARCHAR, NULL erlaubt | Pfad/Initialen. Aktuell ungenutzt (Initialen werden zur Laufzeit aus firstname/lastname berechnet, nicht gespeichert) |
| created_at | DATETIME | |

**Entfernt (2026-09-25):** `fam_id` und `role` gehören nicht mehr zu `users` – die Familienzuordnung läuft komplett über `family_members` (siehe unten), `role` war ungenutzt.

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
8. **Neu (2026-09-23):** `bdconfig.php` zu `dbconfig.php` umbenannt (Konsistenz mit Bakershop/gameshop). `Database.php` und `UEBERSICHT.txt` angepasst; `.gitignore` bereits korrekt auf `dbconfig.php`. Verbindung getestet, funktioniert wieder.
9. **Neu (2026-09-23):** `Data.txt` mit Demo-Zugangsdaten für die Lehrer-Vorführung angelegt (4 Demo-Familien, 7 Demo-User, Passwort `123456543`, über die echten Klassen `createUser`/`createFamily`/`addMember` eingefügt – nicht per Hand-SQL). Lisa und Sara sind bewusst in je zwei Familien, um `SelectFamily.php` vorzuführen. **Nicht committen/pushen** – enthält Klartext-Passwort, gehört nicht ins Repo
10. **Neu (2026-09-23), Zukunftsidee "Mitglied einladen":** Statt dass ein Mitglied direkt einen Login für eine andere Person anlegt (aktueller Stand `AddMember.php`), soll es wahlweise eine **Einladung per Email** geben – Einladungslink-Muster: neue Tabelle `invitations` (Email, `fam_id`, Token, erstellt_am, evtl. Ablaufdatum), Formular "Mitglied einladen" (nur Email), neue Seite `AcceptInvite.php?token=...` die je nach `findByEmail()`-Treffer zu Login oder Registrierung leitet und danach automatisch per `Family::addMember()` der Familie aus der Einladung zuordnet. Offene Frage: echter Email-Versand (PHP `mail()`/Bibliothek + Mailserver-Konfig) oder vereinfacht nur der Link auf dem Bildschirm zum Kopieren (einfacher, reicht evtl. für die Vorführung). **Noch nicht begonnen**
11. ~~Neu offen: Wechsel-Link im Dashboard~~ – erledigt, Teil der Navbar (siehe unten)
13. **Neu (2026-09-25), Zukunftsidee "Persönliche Spitznamen":** Jede Person kann für jedes andere Familienmitglied einen **eigenen** Spitznamen vergeben, der **nur ihr selbst** angezeigt wird (z.B. Ehemann heißt für die eine Person "Schatzi", für die Tochter "Papa" – gleichzeitig, unabhängig voneinander). Kein Feld an `users` selbst, sondern eine **Beziehung zwischen zwei Usern** – strukturell wie `family_members`, nur User-zu-User statt User-zu-Familie.
    - **Geplante Tabelle `nicknames`:** `id` (PK), `owner_id` (FK → users.id, wer den Spitznamen vergeben hat/sieht), `target_id` (FK → users.id, für wen er gilt), `nickname` (VARCHAR), UNIQUE (`owner_id`, `target_id`)
    - **Anzeige:** In der Mitgliederliste (`MyFamily.php`) für jeden Member prüfen, ob der eingeloggte User (`owner_id` = `$_SESSION['uid']`) einen Spitznamen für diese Person hat – wenn ja, den zeigen, sonst echten Vor-/Nachnamen
    - **Bearbeiten:** Ein "Bearbeiten"-**Link** rechts neben jedem Namen (kein Dropdown-pro-Mitglied, bewusst einfacher gehalten) → führt zu einem kleinen Formular zum Setzen/Ändern des Spitznamens (INSERT falls noch keiner existiert, sonst UPDATE – ähnliches Prinzip wie `Family::isMember()` + `addMember()`)
    - **Noch nicht begonnen**, erster Schritt wäre die `nicknames`-Tabelle in HeidiSQL

14. **Erledigt (2026-09-25): Kacheln + Avatar.** Dashboard zeigt jetzt 2×2-Kachel-Grid (Listen, Kalender, Meine Familie, Rezepte) nach FamilyWall-Vorbild, per CSS-Grid (`repeat(2, 1fr)`, automatisch erweiterbar durch einfaches Hinzufügen weiterer `.tile`-Divs). Initialen-Avatar (z.B. "TB") im Kreis, in der Navbar oben rechts (klickbar, öffnet das Dropdown – ersetzt den alten "Menü"-Button) und in der neuen Mitgliederliste. Gemeinsame Funktion `initials()` in `src/functions.php` ausgelagert (DRY, statt doppelten Code in `navbar.php` und der Mitgliederliste). Neue Seite `MyFamily.php` (Familienname + Mitgliederliste mit Avatar, vorher direkt auf dem Dashboard). "Meine Familie"-Kachel ist klickbar über `data-href`-Attribut + `assets/js/tiles.js` (kein `<a>`-Tag, bewusst für spätere Erweiterbarkeit z.B. Popups statt Seitenwechsel). Mit PHPs Testserver durchgetestet.
    - **Gelernt/Debugging:** `//`-Kommentare sind in CSS ungültig und können eine **ganze folgende Regel** unbrauchbar machen (der Selektor "verschmilzt" mit dem nächsten gültigen Selektor zu einem ungültigen Gesamt-Selektor) – nur `/* ... */` benutzen. `require_once`-Pfade müssen von der Position der **aktuellen** Datei aus gedacht werden (`__DIR__` ist relativ zur Datei, in der es steht, nicht zum Projekt-Root)

15. **Erledigt (2026-09-23): Navbar mit Klick-Dropdown.** Drei neue Komponenten `src/components/guard.php` (Session + `uid`-Check, kein HTML), `head.php` (HTML-Kopf + CSS-Link), `footer.php` (schließt `</body></html>` + bindet `navbar.js` ein), dazu `src/components/navbar.php` (Logo, Menü-Button, Linkliste: Familie wechseln/erstellen, Mitglied hinzufügen, Logout). Auf allen vier geschützten Seiten eingebunden, `CreateFamily.php`/`AddMember.php` dabei umsortiert (POST-Verarbeitung jetzt vor jedem HTML, sonst Risiko "headers already sent"). CSS in `assets/css/style.css`, JS in `assets/js/navbar.js` (Klick toggelt Klasse `hidden`). Mit PHPs eingebautem Testserver durchgetestet (Login, alle vier Seiten, Familienwechsel) – keine Fehler. Zwei CSS-Stolpersteine unterwegs: Spezifität (`#id` schlägt `.klasse`, deshalb `#menuList.hidden` statt nur `.hidden`), und dass man beim Kombinieren von Selektoren nicht alle Eigenschaften in einen Block packen darf (sonst gilt der ganze Block nur noch, wenn beide Selektor-Teile zutreffen) – zwei getrennte Regeln nötig: `#menuList {...}` (Basis-Layout, immer) und `#menuList.hidden { display: none; }` (nur die Sichtbarkeit überschreiben)

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
- **Design-Vorbild (2026-09-23):** FamilyWall-Screenshot als Referenz – Grid aus quadratischen/rechteckigen Kacheln, je Icon + Titel + kleine Zeile Statustext (z.B. "4 lists / 37 items", "Nothing planned this week"), abgerundete Ecken, hellgrauer Hintergrund. Kacheln u.a.: Lists, Calendar, Meal Planner, Messages, Location, Gallery, Contacts, My Family (Mitgliederliste als eigene Kachel unten, nicht wie aktuell einfach als Text im Dashboard)
- **Neu (2026-09-23): Avatar des eingeloggten Users in der Navbar anzeigen** (wie bei FamilyWall neben dem Familiennamen). Nutzt die schon vorhandene, bisher ungenutzte Spalte `users.avatar`. Zwei Varianten besprochen: (a) echter Foto-Upload – braucht `multipart/form-data`, Datei-Validierung, Speicherort (später); (b) **Initialen-Avatar** (wie Slack/Teams, z.B. "TB" in farbigem Kreis, aus Vor-/Nachname generiert) – kein Upload nötig, empfohlener Start. Braucht `navbar.php` PHP-fähig zu machen (aktuell reines HTML) + `User::findById($_SESSION['uid'])`, um Namen/Avatar des eingeloggten Users zu holen. Zusammen mit den Dashboard-Kacheln als nächstes großes Thema geplant, noch nicht begonnen
- **Später: Ordnerstruktur.** Seiten liegen bewusst noch im Hauptordner (wie bei Bakershop/gameshop). Umbau nach Bereich (`auth/`, `family/`) erst bei ca. 15 Dateien oder neuem großen Bereich (Kalender/Listen). Dann als eigener Commit, danach alle Wege im Browser testen (Redirects, Links, `require_once`-Pfade). Wiederkehrende Teile später in `src/components/`.
- **Verworfen (2026-09-18):** `role = 'child'` mit eigenem Theme/Kalender-Ansicht/Eltern-only-Listen war kurz angedacht, aber wieder verworfen – zu komplex für jetzt. `role` bleibt bei `ENUM('admin','member')`, und "Mitglied hinzufügen" ist nicht mehr auf Admin beschränkt, sondern darf jedes Mitglied.
- **Ablauf geändert (2026-09-18):** Registrierung und Familie-Erstellen sind zwei getrennte Schritte (nicht mehr ein kombiniertes Formular). User registriert sich zuerst ohne Familie (`fam_id` = NULL), erstellt/tritt einer Familie danach separat bei. Deshalb `fam_id` jetzt NULL-fähig. Konsequenz: nach Login muss geprüft werden, ob `fam_id` NULL ist → ggf. zu "Familie erstellen"-Seite umleiten (noch zu klären: was passiert mit `role`, wenn noch keine Familie existiert?)

## Architektur-Muster

Orientiert an zwei früheren Projekten:
- Bakershop (`c:\laragon\www\Bakershop`): `src/classes/Database.php` (`connect()` liefert PDO), `src/classes/User.php` (`User(PDO $pdo)`, `createUser()`, `loginUser()`), Seiten im Root kombinieren Formular + Logik, prüfen `isset($_POST['submitBtnName'])`, binden Klassen per `require_once __DIR__ . '/src/classes/...'` ein
- gameshop (`c:\laragon\www\gameshop`, Vorlage vom Lehrer): `Validator`/`Validation`-Klasse (`required`, `email`, `minlength`, `matches`) + Fehler-Array + Post/Redirect/Get-Muster (Fehler in `$_SESSION['errors']`, Redirect zurück zum Formular), `User::findByEmail()` für Duplikat-Check und Login; `head.php`/`navbar.php`/`footer.php`-Aufteilung als Vorbild, bei uns zusätzlich `guard.php` (Session + Login-Pflicht, getrennt von `head.php`, weil `head.php` sofort HTML ausgibt und ein `header()`-Redirect vor jeglicher Ausgabe passieren muss)

**Seiten-Grundgerüst (Stand 2026-09-23), für alle vier geschützten Seiten gleich:**
```
requires (Klassen)
guard.php            -- session_start() + uid-Check, KEIN HTML
[ggf. weitere Checks, z.B. fam_id]
POST-Verarbeitung    -- kann noch redirecten, deshalb VOR jedem HTML
head.php + navbar.php
HTML-Inhalt
footer.php            -- bindet auch navbar.js ein
```

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
  - **Aufräumliste (Stand 2026-09-25): komplett erledigt.** `dbconfig.example.php` angelegt (nicht ignoriert, im Repo). `User::setFamily()` gelöscht (+ veralteter Kommentar in `CreateFamily.php` aufgeräumt). FK-Name war entgegen der alten Notiz schon korrekt `fk_members_user`. Alte Spalten `users.fam_id`/`users.role` entfernt – dafür erst den alten FK `fam_id` auf `users` gelöscht (Fehler 1828, gleiches Prinzip wie bei `family_members`), dann `User::createUser()` INSERT angepasst (keine Werte mehr für die beiden Spalten), erst danach die Spalten in HeidiSQL gelöscht. Doppelte Validierung in `Validator::validateRegistrationFields()` zusammengeführt, `UserRegister.php`/`AddMember.php` rufen jetzt nur noch diese eine Methode auf. Alles mit PHPs Testserver durchgetestet (leeres Formular, gültige Registrierung, gültiges Mitglied hinzufügen), Testuser danach gelöscht. Offen bleibt nur: Ordnerstruktur (siehe Notiz oben, bewusst zurückgestellt)
  - **Nächster Schritt:** `FamilySelect.php`
- 2026-09-22: `AddMember.php` fertiggebaut (war unfertig kopiert, Button-Check zeigte auf falschen Namen, `createUser()` gab keine Id zurück). `createUser()` gibt jetzt per `lastInsertId()` die neue Id zurück, `AddMember.php` trägt den neuen User direkt per `Family::addMember()` in die aktuelle Familie ein. Tote/kaputte Kopie von `findFamiliesByUserId` in `User.php` gelöscht. `UEBERSICHT.txt` angelegt (kurze Datei-Referenz: was macht was, woher kommen die Daten) – gleiche Idee auch für das Projekt `temperatur2209` erstellt.
- 2026-09-23: `SelectFamily.php` gebaut (Name bewusst so, nicht `FamilySelect.php` – passt zu `CreateFamily.php`). Zeigt Familien der Person (`findFamiliesByUserId`), Klick prüft `isMember()` und setzt erst dann `$_SESSION['fam_id']`, dann Redirect zum Dashboard. `UserLogin.php` setzt kein `fam_id` mehr selbst, leitet zu `SelectFamily.php`. Login-Link auf `index.php` ergänzt (fehlte). Kompletter Ablauf (Login → Familie wählen → Dashboard) im Browser getestet, inkl. Sicherheitscheck (fremde Familien-Id in der URL wird abgelehnt). Nächster Schritt: Wechsel-Link im Dashboard zu `SelectFamily.php`, danach Aufräumliste (siehe 2026-09-21).
- 2026-09-23 (Nachmittag): `dbconfig.php`-Umbenennung repariert (siehe Session zuvor), Demo-Daten für Lehrer-Vorführung angelegt (`Data.txt`, nicht im Repo). Idee "Mitglied per Email einladen" als Zukunftspunkt notiert (noch nicht umgesetzt).
- 2026-09-23 (Abend): Navbar mit Klick-Dropdown gebaut. Neue Komponenten `guard.php`/`head.php`/`footer.php`/`navbar.php`, auf allen vier geschützten Seiten eingebunden; `CreateFamily.php`/`AddMember.php` dabei umsortiert (POST-Verarbeitung vor jedem HTML). CSS (`assets/css/style.css`) und JS (`assets/js/navbar.js`, Klick toggelt Klasse `hidden`). Zwei CSS-Bugs unterwegs gefunden und behoben: Spezifität (`#id` schlägt `.klasse`) und dass kombinierte Selektoren (`#menuList.hidden`) nur die *gemeinsam* genannten Eigenschaften betreffen dürfen, nicht das ganze Basis-Layout mit hineinziehen. Mit PHPs eingebautem Testserver durchgetestet (Login, alle vier Seiten, Familienwechsel), keine Fehler. Nächster Schritt: committen, danach "Mitglied einladen" oder Aufräumliste.
- 2026-09-24/25 (Navbar/Responsive/Public Pages): Avatar-Initialen gebaut und getestet. Viewport-Meta-Tag ergänzt (fehlte komplett) + `flex-wrap: wrap` für die Navbar (sonst zoomte die ganze Seite bei schmalen Bildschirmen raus). `h2, h3`-Farbregel überschrieb die weiße Navbar-Schrift, behoben mit spezifischerer `.navbarLogo h3`-Regel. `UserLogin.php`/`UserRegister.php`/`index.php` auf `head.php`/`footer.php` umgestellt (waren komplett ungestylt), POST-Verarbeitung dabei vor das HTML verschoben. Avatar wurde zum Klick-Ziel fürs Dropdown (ersetzt den Menü-Button).
- 2026-09-25 (Kacheln): Dashboard-Kacheln gebaut (2×2-CSS-Grid: Listen, Kalender, Meine Familie, Rezepte). Hartnäckiger CSS-Bug: `//`-Kommentar (JS-Syntax statt `/* */`) machte eine ganze Regel ungültig. `initials()`-Funktion nach `src/functions.php` ausgelagert (DRY). Mitgliederliste + Familienname von `Dashboard.php` auf neue Seite `MyFamily.php` verschoben, "Meine Familie"-Kachel führt per `data-href` + `assets/js/tiles.js` dorthin (kein `<a>`-Tag, bewusst erweiterbar). Zukunftsidee "Persönliche Spitznamen pro Person" besprochen und notiert (Punkt 13 oben), noch nicht begonnen.
- 2026-09-25 (Aufräumliste, komplett erledigt): `dbconfig.example.php` angelegt. `User::setFamily()` gelöscht. Alte Spalten `users.fam_id`/`users.role` entfernt (samt zugehörigem FK `fam_id` auf `users`, `User::createUser()` INSERT vorher angepasst). Doppelte Formular-Validierung in `Validator::validateRegistrationFields()` zusammengeführt, von `UserRegister.php` und `AddMember.php` benutzt. Alles mit PHPs Testserver durchgetestet (leeres Formular, echte Registrierung, echtes Mitglied hinzufügen), Testdaten wieder entfernt. Nächster Schritt: committen, danach "Persönliche Spitznamen" oder restliche Kacheln mit Funktion füllen.
- 2026-09-19: `role = 'child'` kurz diskutiert und wieder verworfen (siehe oben). gameshop-Vorlage (vom Lehrer) als Referenz für Validierung entdeckt. `Validation`-Klasse (`src/classes/Validation.php`) mit `required()`, `email()`, `minlength()`, `matches()` fertig nachgebaut. Nächster Schritt: `User::findByEmail()` ergänzen, dann `UserRegister.php` auf Fehler-Array + Post/Redirect/Get umbauen, danach Login.
