// Dropdown fuer das 3-Punkte-Menue einer einzelnen Liste (gleiches Prinzip wie navbar.js)
const listMenuBtn = document.getElementById('listMenuBtn');
const listMenuList = document.getElementById('listMenuList');

if (listMenuBtn) {
    listMenuBtn.addEventListener('click', function () {
        listMenuList.classList.toggle('hidden');
    });

    // Menue schliessen, wenn irgendwo ausserhalb geklickt wird
    document.addEventListener('click', function (event) {
        if (!listMenuList.classList.contains('hidden') && event.target !== listMenuBtn && !listMenuList.contains(event.target)) {
            listMenuList.classList.add('hidden');
        }
    });
}

// "Erledigte ausblenden": rein im Browser, keine Datenbank noetig.
// Toggelt die Klasse "hideDone" am body - das Verstecken selbst macht CSS.
const hideDoneBtn = document.getElementById('hideDoneBtn');

if (hideDoneBtn) {
    hideDoneBtn.addEventListener('click', function (event) {
        event.preventDefault(); // verhindert, dass der Link-Klick die Seite neu laedt
        document.body.classList.toggle('hideDone');
        listMenuList.classList.add('hidden');
        hideDoneBtn.textContent = document.body.classList.contains('hideDone') ? 'Erledigte einblenden' : 'Erledigte ausblenden';
    });
}
