<?php

declare(strict_types = 1);

namespace Acme\Database;

use DBLaci\Data\Etalon2;
use DBLaci\Data\EtalonInstantiationException;
use LogicException;
use PDO;

class MigrationProvider extends Etalon2
{
    public const TABLE = 'migration';
    protected const DEFAULT_MIGRATION = 'm20220901_01_create_migration_table';

    /**
     * @var string[] $dbColumns
     */
    public static array $dbColumns = [
        'id',
        'key',
        'created_at',
    ];

    /**
     * @var int $id
     */
    public int $id;

    /**
     * Migration key, e.g.: "m20220901_02_create_default_file_tables"
     *
     * @var string
     */
    public string $key;

    /**
     * @return PDO
     */
    public static function getDB(): PDO
    {
        return Database::getPdo();
    }

    /**
     * Return migration class by key.
     *
     * @param string $key
     * @return string
     */
    public static function getClassByKey(string $key): string
    {
        return "Acme\\Database\\Migrations\\" . $key;
    }

    /**
     * @var ?string $created_at
     */
    public ?string $created_at;

    /**
     * @param string $key
     * @return MigrationProvider
     * @throws EtalonInstantiationException
     */
    public static function getByKey(string $key): MigrationProvider
    {
        $db = static::getDB();

        $sql = 'SELECT * FROM ' . static::TABLE . ' WHERE key = :key';
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'key' => $key,
        ]);

        $row = $stmt->fetch();

        if ($row === false) {
            throw new EtalonInstantiationException('Migration not found: ' . $key);
        }

        return static::getInstanceFromRow($row);
    }

    /**
     * Migration is completed or not on database.
     *
     * @param string $key
     * @return bool
     */
    public static function isDone(string $key): bool
    {
        $db = static::getDB();

        // In first migration check table exists.
        if ($key === static::DEFAULT_MIGRATION) {
            $res = $db->query('SELECT EXISTS (SELECT FROM pg_tables WHERE schemaname = ' . $db->quote('public') . ' AND tablename  = ' . $db->quote(static::TABLE) . ')')->fetch();
            if ($res['exists']) {
                return true;
            }

            return false;
        }

        try {
            static::getByKey($key);
            return true;
        } catch (EtalonInstantiationException) {
            return false;
        }
    }

    /**
     * Run migration in transaction.
     *
     * @param string $key
     */
    public static function run(string $key): void
    {
        $db = static::getDB();
        $class = static::getClassByKey($key);
        $upgrade = new $class();

        $db->exec('START TRANSACTION');

        if (!$upgrade->run()) {
            throw new LogicException('Migration run failed: ' . $key);
        }

        $migration = new self();
        $migration->key = $key;
        $migration->save(true);

        $db->exec('COMMIT');
    }
}
