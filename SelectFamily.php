<?php
require_once __DIR__ .'/src/classes/Database.php';
require_once __DIR__ .'/src/classes/Family.php';

// guard.php: session_start() + Login-Pflicht (kein HTML) - kein fam_id-Check hier,
// diese Seite entscheidet ja gerade erst, welche Familie aktuell ist
require_once __DIR__ . '/src/components/guard.php';

$db = new Database;
$pdo = $db->connect();
$family = new Family($pdo);

// Aeusseres if: wurde ueberhaupt eine Familie ausgewaehlt? (Klick auf einen Link unten mit ?fam_id=...)
// Beim ersten Aufruf der Seite (noch kein Klick) ist $_GET['fam_id'] nicht gesetzt, der Block wird uebersprungen.
if(isset($_GET['fam_id'])) {
    // Inneres if, Sicherheitscheck: gehoert die eingeloggte Person (uid) wirklich zu dieser Familie (fam_id)?
    // Ohne diesen Check koennte jemand die Zahl in der URL aendern und sich selbst einer fremden Familie zuordnen.
    if($family->isMember($_SESSION['uid'], $_GET['fam_id'])) {
        // Auswahl ist erlaubt: die gewaehlte Familie wird zur "aktuellen" Familie in der Session
        $_SESSION['fam_id'] = $_GET['fam_id'];
        // Weiter zum Dashboard, exit() stoppt den Rest des Skripts (u.a. die Anzeige der Liste unten)
        header("Location: Dashboard.php");
        exit();
    }
    // Kein else noetig: war die Auswahl ungueltig (isMember == false), läuft der Code einfach weiter
    // nach unten durch und zeigt wieder die normale Familienliste an - kein Redirect, keine Fehlermeldung.
}

$families = $family->findFamiliesByUserId($_SESSION['uid']);

// Ab hier nur noch Anzeige, keine Redirects mehr moeglich
include_once __DIR__ . '/src/components/head.php';
include_once __DIR__ . '/src/components/navbar.php';
?>

<h2>Meine Familien</h2>

<?php foreach($families as $fam): ?>
    <p><a href="?fam_id=<?= $fam->id ?>"><?= htmlspecialchars($fam->name) ?></a></p>
<?php endforeach; ?>

<?php include_once __DIR__ . '/src/components/footer.php'; ?>
