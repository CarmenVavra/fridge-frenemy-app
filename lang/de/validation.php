<?php

return [
    'confirmed' => 'Die Bestätigung von :attribute stimmt nicht überein.',
    'current_password' => 'Das Passwort ist falsch.',
    'email' => ':attribute muss eine gültige E-Mail-Adresse sein.',
    'enum' => 'Der gewählte Wert für :attribute ist ungültig.',
    'exists' => 'Der gewählte Wert für :attribute ist ungültig.',
    'integer' => ':attribute muss eine ganze Zahl sein.',
    'lowercase' => ':attribute darf nur Kleinbuchstaben enthalten.',
    'max' => [
        'numeric' => ':attribute darf maximal :max sein.',
        'string' => ':attribute darf maximal :max Zeichen haben.',
    ],
    'min' => [
        'numeric' => ':attribute muss mindestens :min sein.',
        'string' => ':attribute muss mindestens :min Zeichen haben.',
    ],
    'password' => [
        'letters' => ':attribute muss mindestens einen Buchstaben enthalten.',
        'mixed' => ':attribute muss Groß- und Kleinbuchstaben enthalten.',
        'numbers' => ':attribute muss mindestens eine Zahl enthalten.',
        'symbols' => ':attribute muss mindestens ein Sonderzeichen enthalten.',
        'uncompromised' => ':attribute ist in einem Datenleck aufgetaucht. Bitte wähle ein anderes.',
    ],
    'regex' => 'Das Format von :attribute ist ungültig.',
    'required' => ':attribute muss ausgefüllt werden.',
    'string' => ':attribute muss ein Text sein.',
    'unique' => ':attribute ist bereits vergeben.',

    'attributes' => [
        'name' => 'Name',
        'email' => 'E-Mail',
        'password' => 'Passwort',
        'barcode' => 'Barcode',
        'tone' => 'Tonalität',
    ],
];
