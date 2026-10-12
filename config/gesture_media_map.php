<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gesture Media Map
    |--------------------------------------------------------------------------
    | Pemetaan manual antara nama media di folder sumber (video_hasil)
    | dengan identitas gesture (slug, word, atau indonesian) di database.
    |
    | Format kunci yang didukung:
    |   1. "Folder/BaseName" -> misal: 'Daily/Good', 'Angka/1', 'Days/Monday'
    |   2. "BaseName"        -> misal: 'Good', '1', 'Monday'
    |
    | Nilai berupa slug / word / indonesian kartu di database.
    |
    */
    'map' => [
        // Angka: digit ke slug kartu angka
        'Angka/1' => 'one',
        'Angka/2' => 'two',
        'Angka/3' => 'three',
        'Angka/4' => 'four',
        'Angka/5' => 'five',
        'Angka/6' => 'six',
        'Angka/7' => 'seven',
        'Angka/8' => 'eight',
        'Angka/9' => 'nine',
        'Angka/10' => 'ten',

        // Alphabet: huruf ke slug kartu abjad
        'Alphabet/A' => 'letter-a',
        'Alphabet/B' => 'letter-b',
        'Alphabet/C' => 'letter-c',
        'Alphabet/D' => 'letter-d',
        'Alphabet/E' => 'letter-e',
        'Alphabet/F' => 'letter_f',
        'Alphabet/G' => 'letter_g',
        'Alphabet/H' => 'letter_h',
        'Alphabet/I' => 'letter-i',
        'Alphabet/J' => 'letter-j',
        'Alphabet/K' => 'letter-k',
        'Alphabet/L' => 'letter-l',
        'Alphabet/M' => 'letter-m',
        'Alphabet/N' => 'letter-n',
        'Alphabet/O' => 'letter-o',
        'Alphabet/P' => 'letter-p',
        'Alphabet/Q' => 'letter-q',
        'Alphabet/R' => 'letter-r',
        'Alphabet/S' => 'letter-s',
        'Alphabet/T' => 'letter-t',
        'Alphabet/U' => 'letter-u',
        'Alphabet/V' => 'letter-v',
        'Alphabet/W' => 'letter-w',
        'Alphabet/X' => 'letter-x',
        'Alphabet/Y' => 'letter-y',
        'Alphabet/Z' => 'letter-z',

        // Greetings (Salam & Sapaan)
        'Greetings/Hello' => 'hello',
        'Greetings/Thank-you' => 'thank-you',
        'Greetings/Help' => 'help',

        // Daily (Percakapan sehari-hari)
        'Daily/Good' => 'good',
        'Daily/How-are-you' => 'how-are-you',
        'Daily/Name' => 'name',
        'Daily/No' => 'no',
        'Daily/Today' => 'today',
        'Daily/Yes' => 'yes',

        // Days (Nama Hari)
        'Days/Monday' => 'monday',
        'Days/Tuesday' => 'tuesday',
        'Days/Wednesday' => 'wednesday',
        'Days/Thursday' => 'thursday',
        'Days/Friday' => 'friday',
        'Days/Saturday' => 'saturday',
        'Days/Sunday' => 'sunday',

        // Verbs (Kata Kerja)
        'Verbs/Come' => 'come',
        'Verbs/Drink' => 'drink',
        'Verbs/Eat' => 'eat',
        'Verbs/Sleep' => 'sleep',
        'Verbs/Study' => 'study',
        'Verbs/Wake-up' => 'wake-up',
    ],
];
