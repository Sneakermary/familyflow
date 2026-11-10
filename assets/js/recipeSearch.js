// Lupe auf Recipes.php: oeffnet ein Suchfeld, das die Rezept-/Ordner-Kacheln
// live nach dem eingegebenen Text filtert (rein im Browser, keine Datenbankabfrage).
const recipeSearchBtn = document.getElementById('recipeSearchBtn');
const recipeSearchInput = document.getElementById('recipeSearchInput');

if (recipeSearchBtn && recipeSearchInput) {
    recipeSearchBtn.addEventListener('click', function () {
        recipeSearchInput.classList.toggle('hidden');

        if (recipeSearchInput.classList.contains('hidden')) {
            recipeSearchInput.value = '';
            recipeSearchInput.dispatchEvent(new Event('input'));
        } else {
            recipeSearchInput.focus();
        }
    });

    recipeSearchInput.addEventListener('input', function () {
        const query = recipeSearchInput.value.trim().toLowerCase();

        document.querySelectorAll('#recipeTiles .tile').forEach(function (tile) {
            const title = tile.querySelector('.tile_title');
            const text = title ? title.textContent.toLowerCase() : '';
            tile.classList.toggle('searchHidden', query !== '' && !text.includes(query));
        });
    });
}
