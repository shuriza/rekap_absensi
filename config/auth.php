<?php

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users', // Ganti dari 'users' ke 'admins'
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users', // Ganti dari 'users' ke 'admins'
        ],
    ],

    'providers' => [
        'admins' => [ // Ganti dari 'users' ke 'admins'
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],

    'passwords' => [
        'admins' => [ // Ganti dari 'users' ke 'admins'
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];