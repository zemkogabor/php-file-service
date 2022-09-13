<?php

declare(strict_types = 1);

namespace Acme\File;

use Acme\Base\Base;
use Acme\Database\Database;
use DBLaci\Data\Etalon2;
use DBLaci\Data\EtalonInstantiationException;
use PDO;

class ChunkedFile extends Etalon2
{
    /**
     * @var int
     */
    public int $id;

    /**
     * @var int
     */
    public int $file_id;

    /**
     * Current chunk order index
     *
     * @var int
     */
    public int $index;

    /**
     * Size in bytes
     *
     * @var int
     */
    public int $size;

    /**
     * @var string|null
     */
    public ?string $created_at = null;

    public static array $dbColumns = [
        'id',
        'file_id',
        'index',
        'size',
        'created_at',
    ];

    public const TABLE = 'chunked_file';

    protected static function getDB(): PDO
    {
        return Database::getPdo();
    }

    /**
     * @param int $fileId
     * @param int $index
     * @return ChunkedFile
     * @throws EtalonInstantiationException
     */
    public static function getInstanceByFileIdAndIndex(int $fileId, int $index): ChunkedFile
    {
        $schema = static::getDatabaseSchema();
        $sql = 'SELECT * FROM ' . $schema->quoteTableName(static::TABLE)
            . ' WHERE ' . $schema->quoteColumnName('file_id') . ' = :file_id'
            . ' AND ' . $schema->quoteColumnName('index') . ' = :index';
        $stmt = static::getDB()->prepare($sql);
        $stmt->execute([
            'file_id' => $fileId,
            'index' => $index,
        ]);

        $row = $stmt->fetch();

        if ($row === false) {
            throw new EtalonInstantiationException('file_id = "' . $fileId . '"; index = "' . $index . '"');
        }

        return static::getInstanceFromRow($row);
    }

    /**
     * @param File $file
     * @return ChunkedFile[]
     */
    public static function listByFile(File $file): array
    {
        $schema = static::getDatabaseSchema();
        $sql = 'SELECT * FROM ' . $schema->quoteTableName(static::TABLE)
            . ' WHERE ' . $schema->quoteColumnName('file_id') . ' = :file_id'
            . ' ORDER BY ' . $schema->quoteColumnName('index') . ' ASC, ' . $schema->quoteColumnName('id') . ' ASC';
        $stmt = static::getDB()->prepare($sql);
        $stmt->execute([
            'file_id' => $file->id,
        ]);

        $ret = [];

        while ($row = $stmt->fetch()) {
            $ret[] = static::getInstanceFromRow($row);
        }

        return $ret;
    }

    /**
     * Absolute file path
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return Base::getRootDir() . '/uploads/chunks/' . $this->file_id . '_' . $this->id;
    }

    /**
     * Upload chunk
     *
     * @param string $tmpFileName
     * @return void
     */
    public function upload(string $tmpFileName): void
    {
        // Create dir if didn't exist before.
        $dir = dirname($this->getFilePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        rename($tmpFileName, $this->getFilePath());
    }
}
