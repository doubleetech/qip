<?php
/**
 * QIP — Database Connection (PDO / MySQL)
 * ------------------------------------------------------------
 * Returns a singleton PDO instance.
 * All queries MUST use prepared statements.
 */

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        if (QIP_DEBUG) {
            die('Database connection failed: ' . $e->getMessage());
        }
        http_response_code(500);
        die('A server error occurred. Please try again later.');
    }

    return $pdo;
}