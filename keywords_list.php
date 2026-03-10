<?php
$weights = require __DIR__ . '/keywords_weights.php';

$arabic = [];
$english = [];

foreach ($weights as $word => $weight) {
    if (preg_match('/\p{Arabic}/u', $word)) {
        $arabic[] = $word;
    } else {
        $english[] = $word;
    }
}

return [
    'ar' => array_values(array_unique($arabic)),
    'en' => array_values(array_unique($english)),
];
