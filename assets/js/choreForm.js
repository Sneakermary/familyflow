// Felder je nach Rotation und Sichtbarkeit ein-/ausblenden (CreateChore.php)
const rotationSelect = document.getElementById('rotationSelect');
if (rotationSelect) {
    const assignedField = document.getElementById('assignedField');
    const rotationFields = document.getElementById('rotationFields');

    function updateRotationFields() {
        const hasRotation = rotationSelect.value !== 'none';
        assignedField.classList.toggle('hidden', hasRotation);
        rotationFields.classList.toggle('hidden', !hasRotation);
    }

    rotationSelect.addEventListener('change', updateRotationFields);
    updateRotationFields();
}

const visibilitySelect = document.getElementById('visibilitySelect');
if (visibilitySelect) {
    const accessFields = document.getElementById('accessFields');

    function updateAccessFields() {
        accessFields.classList.toggle('hidden', visibilitySelect.value !== 'selected');
    }

    visibilitySelect.addEventListener('change', updateAccessFields);
    updateAccessFields();
}
