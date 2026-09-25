// Alle Kacheln mit data-href suchen (querySelectorAll findet ALLE passenden Elemente, nicht nur eins)
const clickableTiles = document.querySelectorAll('.tile[data-href]');

// Fuer jede gefundene Kachel einen Klick-Handler einrichten
clickableTiles.forEach(function (tile) {
    tile.addEventListener('click', function () {
        window.location.href = tile.dataset.href;
    });
});