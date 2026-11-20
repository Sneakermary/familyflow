// Popup-Menü je Kachel (Termine in Day.php, Ämtli in Chores.php, Mitglieder in
// MyFamily.php, eigene Notizfelder in Profile.php): Klick auf die Kachel oeffnet/
// schliesst nur das eigene Menü, die anderen Kacheln bleiben unberuehrt.
document.querySelectorAll('.eventTile, .choreTile, .memberTile, .noteTile').forEach(function (tile) {
    const menu = tile.querySelector('.eventMenu');
    if (!menu) {
        return;
    }

    tile.addEventListener('click', function (event) {
        if (menu.contains(event.target)) {
            return; // Klick auf Bearbeiten/Loeschen soll den Link normal ausloesen
        }
        if (event.target.closest('.avatarLink')) {
            return; // Klick aufs Profilbild soll zum Profil fuehren, nicht das Menue oeffnen
        }
        menu.classList.toggle('hidden');
    });

    // Menue schliessen, wenn irgendwo ausserhalb dieser Kachel geklickt wird
    document.addEventListener('click', function (event) {
        if (!menu.classList.contains('hidden') && !tile.contains(event.target)) {
            menu.classList.add('hidden');
        }
    });
});
