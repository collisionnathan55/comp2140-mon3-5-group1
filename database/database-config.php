<?php

/**
 * This file generally reads the .env config for sql users etc
 * It is generally called by other sql focused files
 */

function pdo()
{
    $envPath = dirname(__DIR__) . '/.env';
    $env = parse_ini_file($envPath);

    if ($env === false) {
        throw new RuntimeException("Could not load the .env file.");
    }

    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $name = $env['DB_NAME'] ?? 'yaad_goods';
    $user = $env['DB_USER'] ?? 'root';
    $password = $env['DB_PASSWORD'] ?? '';

    try {
        return new PDO(
            "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );

    } catch (PDOException $e) {
        error_log($e->getMessage());
        throw new RuntimeException("Database connection failed.");
    }

    return null;
}

?>
