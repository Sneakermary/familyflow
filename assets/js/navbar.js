// Holt sich die zwei Elemente ueber ihre id (genau wie #id in CSS)
const menuBtn = document.getElementById('menuBtn');
const menuList = document.getElementById('menuList');

// addEventListener: "wenn auf menuBtn geklickt wird, fuehre diese Funktion aus"
menuBtn.addEventListener('click', function () {
    // toggle = an/aus schalten: Klasse "hidden" wird entfernt, falls vorhanden,
    // oder hinzugefuegt, falls nicht vorhanden - dadurch klappt das Menue auf/zu
    menuList.classList.toggle('hidden');
});
