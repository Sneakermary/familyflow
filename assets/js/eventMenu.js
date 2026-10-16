// Popup-Menü je Kachel (Termine in Day.php, Ämtli in Chores.php, Mitglieder in
// MyFamily.php): Klick auf die Kachel oeffnet/schliesst nur das eigene Menü,
// die anderen Kacheln bleiben unberuehrt.
document.querySelectorAll('.eventTile, .choreTile, .memberTile').forEach(function (tile) {
    const menu = tile.querySelector('.eventMenu');
    if (!menu) {
        return;
    }

    tile.addEventListener('click', function (event) {
        if (menu.contains(event.target)) {
            return; // Klick auf Bearbeiten/Loeschen soll den Link normal ausloesen
        }
        menu.classList.toggle('hidden');
    });
});
