<?php

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'admins', // Ganti dari 'users' ke 'admins'
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'admins', // Ganti dari 'users' ke 'admins'
        ],
    ],

    'providers' => [
        'admins' => [ // Ganti dari 'users' ke 'admins'
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],
    ],

    'passwords' => [
        'admins' => [ // Ganti dari 'users' ke 'admins'
            'provider' => 'admins',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];