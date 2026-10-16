<?php

function initials($firstname, $lastname) {
    return mb_strtoupper(mb_substr($firstname, 0, 1) . mb_substr($lastname, 0, 1));
}

// CSRF-Schutz: ein zufaelliger Token pro Session, der bei jedem Formular mitgeschickt
// und beim Verarbeiten geprueft wird. Eine fremde Seite kennt diesen Token nicht und
// kann daher keine Aktion in unserem Namen auslösen.
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}
