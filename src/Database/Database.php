<?php

declare(strict_types = 1);

namespace Acme\Database;

use PDO;

class Database
{
    private static PDO $_pdo;

    /**
     * @return PDO
     */
    public static function getPdo(): PDO
    {
        if (isset(static::$_pdo)) {
            return static::$_pdo;
        }

        $pdo = new PDO(
            'pgsql:host=' . getenv('POSTGRES_HOST')
            . ';port=' . (getenv('POSTGRES_PORT') ?: '5432')
            . ';dbname=' . getenv('POSTGRES_DB'),
            getenv('POSTGRES_USER'),
            getenv('POSTGRES_PASSWORD'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        return static::$_pdo = $pdo;
    }
}
