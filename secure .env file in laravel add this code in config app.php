<?php
'debug_blacklist' => [
        '_ENV' => [
            'APP_KEY',
            'DB_PASSWORD',
            'DB_DATABASE',
            'REDIS_PASSWORD',
            'MAIL_PASSWORD',
            'PUSHER_APP_KEY',
            'PUSHER_APP_SECRET',
            'DB_USERNAME',
            'DB_HOST',
        ],
        '_SERVER' => [
            'APP_KEY',
            'DB_PASSWORD',
            'DB_USERNAME',
            'DB_DATABASE',
            'REDIS_PASSWORD',
            'MAIL_PASSWORD',
            'PUSHER_APP_KEY',
            'PUSHER_APP_SECRET',
            'DB_HOST',
        ],
        '_POST' => [
            'password',
        ],
    ],
