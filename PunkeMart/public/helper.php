<?php
// Same helper-function approach used in the class CRUD examples.
function clean($key, $type = 'string')
{
    $value = $_POST[$key] ?? '';
    if (is_array($value)) return '';
    $value = trim($value);

    if ($type === 'int') return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    if ($type === 'float') return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    if ($type === 'email') return filter_var($value, FILTER_SANITIZE_EMAIL);
    return trim($value);
}

function esc($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
