<?php

declare(strict_types=1);

/**
 * Loads config/database.local.php when present (created by /public/setup.php).
 * Otherwise falls back to placeholders — update via setup or edit this file.
 */
$local = __DIR__ . '/database.local.php';
if (is_file($local)) {
    /** @var array $localConfig */
    $localConfig = require $local;
    return $localConfig;
}

return [
    'host'      => getenv('DB_HOST') ?: 'localhost',
    'port'      => getenv('DB_PORT') ?: '3306',
    'dbname'    => getenv('DB_NAME') ?: 'kravdund_kraved',
    'username'  => getenv('DB_USER') ?: 'kravdund_kraved',
    'password'  => getenv('DB_PASS') ?: 'kravdund_kraved',
    'charset'   => 'utf8mb4',
];
