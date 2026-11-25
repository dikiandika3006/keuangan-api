<?php

// Login dashboard admin (/admin). Isi di .env; jika ADMIN_PASSWORD kosong, dashboard tidak bisa dimasuki.
return [
    'username' => env('ADMIN_USERNAME', 'admin'),
    'password' => env('ADMIN_PASSWORD', ''),
];
