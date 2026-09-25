<?php

function initials($firstname, $lastname) {
    return mb_strtoupper(mb_substr($firstname, 0, 1) . mb_substr($lastname, 0, 1));
}
