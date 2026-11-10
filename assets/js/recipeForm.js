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
        input.focus();
    });
}
