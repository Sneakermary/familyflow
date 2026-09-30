const allDayCheckbox = document.getElementById('allDayCheckbox');
const timeFields = document.getElementById('timeFields');
const dateField = document.getElementById('dateField');

if (allDayCheckbox) {
    allDayCheckbox.addEventListener('change', function () {
        timeFields.classList.toggle('hidden', allDayCheckbox.checked);
        dateField.classList.toggle('hidden', !allDayCheckbox.checked);
    });
}
