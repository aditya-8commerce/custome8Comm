<?php

return [

   'default' => 'mysql',
   'fetch' => PDO::FETCH_CLASS,
   'connections' => [
        'mysql' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST'),
            'port'      => env('DB_PORT'),
            'database'  => env('DB_DATABASE'),
            'username'  => env('DB_USERNAME'),
            'password'  => env('DB_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
            'options'  => array(
                            PDO::MYSQL_ATTR_SSL_CA => "/var/ssl/azzure.pem"
                        )
         ],
    ],
];