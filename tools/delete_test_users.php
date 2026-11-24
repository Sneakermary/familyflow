<?php
// ---------------------------------------------------------------
// Demo- und Testkonten loeschen (aufraeumen vor echtem Betrieb).
//
//   php tools/delete_test_users.php            -> zeigt NUR an, was passieren wuerde
//   php tools/delete_test_users.php --execute  -> loescht wirklich
//
// Geloescht werden Konten mit E-Mail-Adressen auf den Test-Domains unten.
// Dabei wird automatisch mitgeloescht, was diesen Konten gehoert (Termine, Rezepte,
// Listen, Aemtli, Notizen, ...). Danach:
//   - Familien ohne Mitglieder werden geloescht
//   - Familien ohne Admin bekommen das am laengsten dabei gewesene Mitglied als Admin
//   - Fotos/Profilbilder, auf die nichts mehr verweist, werden geloescht
// Das Konto aus 'admin_email' (mailconfig.php) wird NIE geloescht.
// ---------------------------------------------------------------

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Nur auf der Kommandozeile ausfuehrbar.');
}

// Adressen auf diesen Domains gelten als Test-/Demo-Konten
$testDomains = [
    'familyflow.test',
    'anna.at',
    'hanna.at',
    'hannnna.at',
    'hohl.at',
    'mama.at',
    'müller.at',
    'xn--mller-kva.at',
    'stili.at',
];

$execute = in_array('--execute', $argv, true);

$config = include __DIR__ . '/../src/config/dbconfig.php';
$pdo = new PDO("mysql:host={$config['dbhost']};dbname={$config['dbname']};charset=utf8mb4", $config['dbuser'], $config['dbpw']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

$mailConfigFile = __DIR__ . '/../src/config/mailconfig.php';
$mailConfig = is_file($mailConfigFile) ? include $mailConfigFile : [];
$protectedEmail = strtolower(trim($mailConfig['admin_email'] ?? ''));

// --- Welche Konten? -------------------------------------------------
$allUsers = $pdo->query("SELECT id, firstname, lastname, email FROM users ORDER BY id")->fetchAll();
$toDelete = [];
$toKeep = [];
foreach ($allUsers as $u) {
    $domain = strtolower(substr(strrchr($u->email, '@'), 1));
    $isTest = in_array($domain, $testDomains, true) && strtolower($u->email) !== $protectedEmail;
    if ($isTest) {
        $toDelete[] = $u;
    } else {
        $toKeep[] = $u;
    }
}

echo $execute ? "=== LOESCHEN ===\n\n" : "=== PROBELAUF (es wird nichts geloescht) ===\n\n";

echo "Bleiben (" . count($toKeep) . "):\n";
foreach ($toKeep as $u) {
    echo "  #{$u->id}  {$u->firstname} {$u->lastname} <{$u->email}>\n";
}
echo "\nWerden geloescht (" . count($toDelete) . "):\n";
foreach ($toDelete as $u) {
    echo "  #{$u->id}  {$u->firstname} {$u->lastname} <{$u->email}>\n";
}

if (count($toDelete) === 0) {
    exit("\nNichts zu tun.\n");
}

$ids = array_map(fn($u) => (int) $u->id, $toDelete);
$in = implode(',', $ids);

// Was haengt an diesen Konten dran (wird automatisch mitgeloescht)?
echo "\nWird mitgeloescht (gehoert diesen Konten):\n";
foreach (['events' => 'Termine', 'recipes' => 'Rezepte', 'lists' => 'Listen', 'chores' => 'Aemtli', 'feedback' => 'Feedback-Eintraege'] as $table => $label) {
    $col = $table === 'feedback' ? 'user_id' : 'owner_id';
    $n = $pdo->query("SELECT COUNT(*) FROM `$table` WHERE `$col` IN ($in)")->fetchColumn();
    echo "  $label: $n\n";
}

if (!$execute) {
    echo "\nZum wirklichen Loeschen: php tools/delete_test_users.php --execute\n";
    exit;
}

// --- Loeschen (alles oder nichts) ---------------------------------------
$pdo->beginTransaction();
try {
    $pdo->exec("DELETE FROM users WHERE id IN ($in)");

    // Familien ohne Mitglieder loeschen (Termine/Listen/... der Familie gehen per CASCADE mit)
    $emptyFamilies = $pdo->query("SELECT f.id, f.name FROM families f
        LEFT JOIN family_members m ON m.fam_id = f.id WHERE m.id IS NULL")->fetchAll();
    foreach ($emptyFamilies as $f) {
        $pdo->prepare("DELETE FROM families WHERE id = ?")->execute([$f->id]);
        echo "  Familie geloescht (leer): {$f->name}\n";
    }

    // Familien ohne Admin: das am laengsten dabei gewesene Mitglied wird Admin
    $noAdmin = $pdo->query("SELECT f.id, f.name FROM families f
        WHERE NOT EXISTS (SELECT 1 FROM family_members m WHERE m.fam_id = f.id AND m.role = 'admin')")->fetchAll();
    foreach ($noAdmin as $f) {
        $first = $pdo->prepare("SELECT m.id, u.firstname FROM family_members m JOIN users u ON u.id = m.user_id
            WHERE m.fam_id = ? ORDER BY m.created_at, m.id LIMIT 1");
        $first->execute([$f->id]);
        $member = $first->fetch();
        if ($member) {
            $pdo->prepare("UPDATE family_members SET role = 'admin' WHERE id = ?")->execute([$member->id]);
            echo "  Neuer Admin in \"{$f->name}\": {$member->firstname}\n";
        }
    }

    $pdo->commit();
    echo "\n" . count($toDelete) . " Konten geloescht.\n";
} catch (Exception $e) {
    $pdo->rollBack();
    exit("\nFEHLER, es wurde nichts geloescht: " . $e->getMessage() . "\n");
}

// --- Verwaiste Bilder aufraeumen ------------------------------------
$used = array_merge(
    $pdo->query("SELECT photo FROM recipes WHERE photo IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN),
    $pdo->query("SELECT avatar FROM users WHERE avatar IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN)
);
$used = array_flip($used);
$removed = 0;
foreach (['recipes', 'avatars'] as $folder) {
    foreach (glob(__DIR__ . "/../uploads/$folder/*") as $file) {
        $name = basename($file);
        if ($name === '.gitkeep' || isset($used[$name])) {
            continue;
        }
        if (unlink($file)) {
            $removed++;
        }
    }
}
echo "$removed nicht mehr verwendete Bilder geloescht.\nFertig.\n";
