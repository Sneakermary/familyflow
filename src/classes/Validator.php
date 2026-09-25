<?php

class Validator {
    public function required(string $value) 
    {
        return trim($value) !== '';
    }

    public function email(string $value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function minlength(string $value, int $length)
    {
        return mb_strlen($value) >= $length;
    }

    public function matches(string $value1, string $value2)
    {
        return $value1 === $value2;
    }

    public function validateRegistrationFields($firstname, $lastname, $email, $password, $pwConfirm) {
        $errors = [];

        if (!$this->required($email)) {
            $errors['email'] = 'E-Mail ist erforderlich!';
        } elseif (!$this->email($email)) {
            $errors['email'] = 'Bitte gib eine gültige E-Mail ein!';
        }

        if (!$this->required($password)) {
            $errors['password'] = 'Passwort ist erforderlich!';
        } elseif (!$this->minlength($password, 8)) {
            $errors['password'] = 'Passwort muss mindestens 8 Zeichen lang sein.';
        }

        if (!$this->required($pwConfirm)) {
            $errors['pwConfirm'] = 'Bitte Passwort wiederholen!';
        } elseif (!$this->matches($password, $pwConfirm)) {
            $errors['pwConfirm'] = 'Die Passwörter stimmen nicht überein!';
        }

        if (!$this->required($firstname)) {
            $errors['firstname'] = 'Vorname ist erforderlich!';
        }

        if (!$this->required($lastname)) {
            $errors['lastname'] = 'Nachname ist erforderlich!';
        }

        return $errors;
    }
}

?>