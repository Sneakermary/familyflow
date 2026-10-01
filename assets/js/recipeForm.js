const modePhoto = document.getElementById('modePhoto');
const modeWritten = document.getElementById('modeWritten');
const photoFields = document.getElementById('photoFields');
const writtenFields = document.getElementById('writtenFields');

if (modePhoto && modeWritten) {
    modePhoto.addEventListener('change', function () {
        photoFields.classList.remove('hidden');
        writtenFields.classList.add('hidden');
    });

    modeWritten.addEventListener('change', function () {
        photoFields.classList.add('hidden');
        writtenFields.classList.remove('hidden');
    });
}

const stepsContainer = document.getElementById('stepsContainer');
const addStepBtn = document.getElementById('addStepBtn');

if (addStepBtn) {
    addStepBtn.addEventListener('click', function () {
        const stepCount = stepsContainer.querySelectorAll('input').length + 1;
        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'steps[]';
        input.placeholder = 'Schritt ' + stepCount;
        stepsContainer.appendChild(input);
    });
}
