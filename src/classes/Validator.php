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
}

?>