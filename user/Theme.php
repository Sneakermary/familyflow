<?php
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/components/guard.php';

$themeGroups = [
    '' => ['standard' => 'Standard'],
    'Farbkombinationen' => [
        'kirschbluete' => 'Kirschblüte',
        'meerjungfrau' => 'Meerjungfrau',
        'drachenfrucht' => 'Drachenfrucht',
        'zuckerwatte' => 'Zuckerwatte',
        'gerbera' => 'Gerbera',
        'velvetaurora' => 'Velvet Aurora',
    ],
    'Specials' => [
        'halloween' => 'Halloween',
        'weihnachten' => 'Weihnachten',
        'valentinstag' => 'Valentinstag',
    ],
];

$themes = array_merge(...array_values($themeGroups));

$db = new Database;
$pdo = $db->connect();

if (isset($_POST['themeBtn']) && isset($themes[$_POST['theme'] ?? ''])) {
    $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
    $stmt->execute([$_POST['theme'], $_SESSION['uid']]);
    header("Location: /Dashboard.php");
    exit();
}

$stmt = $pdo->prepare("SELECT theme FROM users WHERE id = ?");
$stmt->execute([$_SESSION['uid']]);
$currentTheme = $stmt->fetchColumn();

include_once __DIR__ . '/../src/components/head.php';
include_once __DIR__ . '/../src/components/navbar.php';
?>

<a href="/Dashboard.php" class="backLink">&larr;</a>

<h2>Layout ändern</h2>

<div class="contentcontainer">
    <form action="" method="POST">
        <select name="theme">
            <?php foreach ($themeGroups as $groupLabel => $groupThemes): ?>
                <?php if ($groupLabel === ''): ?>
                    <?php foreach ($groupThemes as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $key === $currentTheme ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <optgroup label="<?= htmlspecialchars($groupLabel) ?>">
                        <?php foreach ($groupThemes as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $key === $currentTheme ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="themeBtn">Speichern</button>
    </form>
</div>

<?php include_once __DIR__ . '/../src/components/footer.php'; ?>
