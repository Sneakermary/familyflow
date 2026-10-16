const allDayCheckbox = document.getElementById('allDayCheckbox');
const timeFields = document.getElementById('timeFields');
const dateField = document.getElementById('dateField');

if (allDayCheckbox) {
    allDayCheckbox.addEventListener('change', function () {
        timeFields.classList.toggle('hidden', allDayCheckbox.checked);
        dateField.classList.toggle('hidden', !allDayCheckbox.checked);
    });
}

// Ende springt automatisch eine Stunde nach Start mit, solange niemand das Ende
// von Hand angefasst hat - danach bleibt es stehen, wie vom Menschen gewollt.
const startAtInput = document.getElementById('startAtInput');
const endAtInput = document.getElementById('endAtInput');

if (startAtInput && endAtInput) {
    let endTouchedByUser = false;

    endAtInput.addEventListener('input', function () {
        endTouchedByUser = true;
    });

    startAtInput.addEventListener('input', function () {
        if (endTouchedByUser || !startAtInput.value) {
            return;
        }

        const start = new Date(startAtInput.value);
        start.setHours(start.getHours() + 1);

        const pad = (n) => String(n).padStart(2, '0');
        endAtInput.value = start.getFullYear() + '-' + pad(start.getMonth() + 1) + '-' + pad(start.getDate())
            + 'T' + pad(start.getHours()) + ':' + pad(start.getMinutes());
    });
}
