<?php

declare(strict_types = 1);

namespace Acme\Amqp;

use Acme\Database\Database;
use DBLaci\Data\Etalon2;
use PDO;

class FailedJob extends Etalon2
{
    public int $id;

    public string $name;

    public string $data;

    public string $message;

    public int $retry_attempt_count;

    public ?string $created_at;

    public const TABLE = 'failed_job';

    public static array $dbColumns = [
        'id',
        'name',
        'data',
        'message',
        'retry_attempt_count',
        'created_at',
    ];

    protected static function getDB(): PDO
    {
        return Database::getPdo();
    }
}
