<?php

/*
|--------------------------------------------------------------------------
| Legal notice (Impressum) and privacy policy
|--------------------------------------------------------------------------
|
| Who runs this installation. Kept in .env so personal details stay out of
| the repository. Shown on /impressum and /datenschutz.
|
*/

return [
    'operator' => [
        'name' => env('LEGAL_NAME'),
        'street' => env('LEGAL_STREET'),
        'city' => env('LEGAL_CITY'),
        'country' => env('LEGAL_COUNTRY', 'Österreich'),
        'email' => env('LEGAL_EMAIL'),
    ],

    'updated' => 'Oktober 2026',
];
