// Holt sich die zwei Elemente ueber ihre id (genau wie #id in CSS)
const menuBtn = document.getElementById('menuBtn');
const menuList = document.getElementById('menuList');
const familySwitchBtn = document.getElementById('familySwitchBtn');
const familySwitchList = document.getElementById('familySwitchList');

// addEventListener: "wenn auf menuBtn geklickt wird, fuehre diese Funktion aus"
menuBtn.addEventListener('click', function () {
    // toggle = an/aus schalten: Klasse "hidden" wird entfernt, falls vorhanden,
    // oder hinzugefuegt, falls nicht vorhanden - dadurch klappt das Menue auf/zu
    menuList.classList.toggle('hidden');
});

if(familySwitchBtn) {
    familySwitchBtn.addEventListener('click', function() {
        familySwitchList.classList.toggle('hidden');
    });
}

// Beide Menues schliessen, wenn irgendwo ausserhalb geklickt wird
document.addEventListener('click', function (event) {
    if (!menuList.classList.contains('hidden') && event.target !== menuBtn && !menuList.contains(event.target)) {
        menuList.classList.add('hidden');
    }

    if (familySwitchBtn && !familySwitchList.classList.contains('hidden') && event.target !== familySwitchBtn && !familySwitchList.contains(event.target)) {
        familySwitchList.classList.add('hidden');
    }
});

