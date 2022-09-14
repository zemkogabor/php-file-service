<?php

declare(strict_types = 1);

namespace Acme\File;

use Acme\Base\Base;
use Acme\Database\Database;
use DBLaci\Data\Etalon2;
use DBLaci\Data\EtalonInstantiationException;
use PDO;

class File extends Etalon2
{
    /**
     * Primary key, only for internal usage.
     *
     * @var int
     */
    public int $id;

    /**
     * This ID used for uploading, it is filled in by the client.
     *
     * @var string
     */
    public string $upload_uuid;

    /**
     * This ID user for downloading files, filled by this application.
     *
     * @var string
     */
    public string $download_uuid;

    /**
     * @var int
     */
    public int $chunk_size;

    /**
     * @var int
     */
    public int $total_size;

    /**
     * Generated name
     *
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $original_name;

    /**
     * @var int
     */
    public int $total_chunk_count;

    /**
     * @var string
     */
    public string $status;

    /**
     * @var string|null
     */
    public ?string $created_at = null;

    /**
     * @var string|null
     */
    public ?string $updated_at = null;

    /**
     * @var string|null
     */
    public ?string $deleted_at = null;

    public static array $dbColumns = [
        'id',
        'upload_uuid',
        'download_uuid',
        'chunk_size',
        'total_size',
        'name',
        'original_name',
        'total_chunk_count',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public const TABLE = 'file';

    public const STATUS_UPLOADING = 'uploading';
    public const STATUS_READY_TO_COMBINE = 'ready_to_combine';
    public const STATUS_COMBINE = 'combine';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_FAILED = 'failed';

    public const STATUS_FOR_CLIENT_PROCESSING = 'processing';
    public const STATUS_FOR_CLIENT_COMPLETE = 'complete';
    public const STATUS_FOR_CLIENT_FAILED = 'failed';

    protected static function getDB(): PDO
    {
        return Database::getPdo();
    }

    /**
     * @param string $uuid
     * @return File
     * @throws EtalonInstantiationException
     */
    public static function getInstanceByUploadUuid(string $uuid): File
    {
        $schema = static::getDatabaseSchema();
        $sql = 'SELECT * FROM ' . $schema->quoteTableName(static::TABLE)
            . ' WHERE ' . $schema->quoteColumnName('upload_uuid') . ' = :upload_uuid';
        $stmt = static::getDB()->prepare($sql);
        $stmt->execute(['upload_uuid' => $uuid]);

        $row = $stmt->fetch();

        if ($row === false) {
            throw new EtalonInstantiationException('upload_uuid = "' . $uuid . '"');
        }

        return static::getInstanceFromRow($row);
    }

    /**
     * @param string $uuid
     * @return File
     * @throws EtalonInstantiationException
     */
    public static function getInstanceByDownloadUuid(string $uuid): File
    {
        $schema = static::getDatabaseSchema();
        $sql = 'SELECT * FROM ' . $schema->quoteTableName(static::TABLE)
            . ' WHERE ' . $schema->quoteColumnName('download_uuid') . ' = :download_uuid';
        $stmt = static::getDB()->prepare($sql);
        $stmt->execute(['download_uuid' => $uuid]);

        $row = $stmt->fetch();

        if ($row === false) {
            throw new EtalonInstantiationException('download_uuid = "' . $uuid . '"');
        }

        return static::getInstanceFromRow($row);
    }

    /**
     * Absolut file path
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return Base::getRootDir()
            . '/uploads/'
            . date('Y', strtotime($this->created_at)) . '/'
            . date('m', strtotime($this->created_at)) . '/'
            . $this->name;
    }

    /**
     * Combine chunks
     *
     * @return void
     */
    public function combineChunks(): void
    {
        if ($this->status !== File::STATUS_READY_TO_COMBINE) {
            throw new \LogicException('Bad file status: ' . $this->status);
        }

        $this->status = File::STATUS_COMBINE;
        $this->save();

        $chunks = ChunkedFile::listByFile($this);
        $chunkCount = count($chunks);

        if ($chunkCount !== $this->total_chunk_count) {
            $this->status = File::STATUS_FAILED;
            $this->save();

            throw new \LogicException('Expected chunks number is ' . $this->total_chunk_count . ' There are ' . $chunkCount);
        }

        // Create dir if didn't exist before.
        $dir = dirname($this->getFilePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        foreach ($chunks as $chunk) {
            file_put_contents($this->getFilePath(), file_get_contents($chunk->getFilePath()), FILE_APPEND);
        }

        $fileSize = filesize($this->getFilePath());

        if ($fileSize !== $this->total_size) {
            $this->status = File::STATUS_FAILED;
            $this->save();

            // Delete failed merged chunk.
            unlink($this->getFilePath());

            throw new \LogicException('Expected file size is ' . $this->total_size . ' byte, instead it is ' . $fileSize . ' byte');
        }

        $this->status = File::STATUS_COMPLETE;
        $this->save();

        // Cleanup chunks
        foreach ($chunks as $chunk) {
            unlink($chunk->getFilePath());
            $chunk->deleteFromDB();
        }
    }

    /**
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === static::STATUS_COMPLETE;
    }

    /**
     * @return string
     */
    public function getStatusForClient(): string
    {
        return match ($this->status) {
            File::STATUS_UPLOADING,
            File::STATUS_READY_TO_COMBINE,
            File::STATUS_COMBINE => File::STATUS_FOR_CLIENT_PROCESSING,
            File::STATUS_COMPLETE => File::STATUS_FOR_CLIENT_COMPLETE,
            File::STATUS_FAILED => File::STATUS_FOR_CLIENT_FAILED,
            default => throw new \LogicException('Not supported status: "' . $this->status . '"'),
        };
    }
}
