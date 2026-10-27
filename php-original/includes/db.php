<?php
function db_connect(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/../config/config.php';
    $db = $config['db'];
    $dsn = 'mysql:host=' . $db['host'] . ';dbname=' . $db['name'] . ';charset=' . $db['charset'];

    try {
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        throw new RuntimeException('Database connection failed.');
    }

    return $pdo;
}
