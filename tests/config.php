<?php
/**
 * You can override configuration values by creating a `config.local.php` file
 * and manipulate the `$config` variable.
 */
$config = [
    'mysql' => [
        'dsn' => 'mysql:host=localhost;dbname=podiumtest',
        'username' => 'podium',
        'password' => 'podium',
        'charset' => 'utf8mb4',
    ],
];
if (is_file(__DIR__ . '/config.local.php')) {
    include __DIR__ . '/config.local.php';
}
return $config;