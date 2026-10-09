<?php
declare(strict_types=1);

function ems_db(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    // Native XAMPP defaults; Docker Compose explicitly sets EMS_DB_HOST=db for the PHP container.
    $host = getenv('EMS_DB_HOST') ?: '127.0.0.1';
    $port = getenv('EMS_DB_PORT') ?: '3306';
    $database = getenv('EMS_DB_NAME') ?: 'ems_db';
    $username = getenv('EMS_DB_USER') ?: 'root';
    $password = getenv('EMS_DB_PASSWORD');
    $password = $password === false ? '' : $password;
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $database
    );

    $connection = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ]);

    return $connection;
}
