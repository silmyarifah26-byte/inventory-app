<?php
// helpers/validation.php

function validate_int($value) {
    $filtered = filter_var($value, FILTER_VALIDATE_INT);
    return $filtered !== false ? $filtered : null;
}

function validate_positive_decimal($value) {
    $filtered = filter_var($value, FILTER_VALIDATE_FLOAT);
    return ($filtered !== false && $filtered >= 0) ? $filtered : null;
}