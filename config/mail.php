<?php

return [

    'default' => env('MAIL_MAILER', 'smtp'),

    'mailers' => [
        'smtp' => [
            'transport'    => 'smtp',
            'host'         => env('MAIL_HOST', '127.0.0.1'),
            'port'         => env('MAIL_PORT', 587),
            'encryption'   => env('MAIL_ENCRYPTION', 'tls'),
            'username'     => env('MAIL_USERNAME'),
            'password'     => env('MAIL_PASSWORD'),
            'timeout'      => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),

            // El SMTP de Hostalia se presenta con un certificado generico
            // (www.dominioabsoluto.net) que jamas casara con nuestro host.
            // Con MAIL_VERIFY_PEER=false la conexion SIGUE cifrada con TLS;
            // solo se deja de exigir que el nombre del certificado coincida.
            'verify_peer'  => env('MAIL_VERIFY_PEER', true),
        ],

        'log' => [
            'transport' => 'log',
            'channel'   => env('MAIL_LOG_CHANNEL'),
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hola@holaprivet.com'),
        'name'    => env('MAIL_FROM_NAME', 'holaprivet'),
    ],

];
