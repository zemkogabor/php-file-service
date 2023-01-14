<?php

declare(strict_types = 1);

namespace Acme\File;

use Acme\Amqp\Jobs\StatusChangeWebhookJob;
use Acme\Base\Base;
use Acme\Database\Database;
use Acme\File\Generated\GeneratedFileImage;
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
     * This ID used for uploading, it is filled by the client.
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
     * Generated name of original file
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
     * @var int
     */
    public int $is_private;

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
        'is_private',
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
            . ' WHERE ' . $schema->quoteColumnName('upload_uuid') . ' = :upload_uuid'
            . ' AND ' . $schema->quoteColumnName('deleted_at') . ' IS NULL';

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
            . ' WHERE ' . $schema->quoteColumnName('download_uuid') . ' = :download_uuid'
            . ' AND ' . $schema->quoteColumnName('deleted_at') . ' IS NULL';

        $stmt = static::getDB()->prepare($sql);
        $stmt->execute(['download_uuid' => $uuid]);

        $row = $stmt->fetch();

        if ($row === false) {
            throw new EtalonInstantiationException('download_uuid = "' . $uuid . '"');
        }

        return static::getInstanceFromRow($row);
    }

    /**
     * List all completed files.
     *
     * @return File[]
     */
    public static function listAllCompleted(): array
    {
        $schema = static::getDatabaseSchema();
        $sql = 'SELECT * FROM ' . $schema->quoteTableName(static::TABLE)
            . ' WHERE ' . $schema->quoteColumnName('status') . ' = :status';
        $stmt = static::getDB()->prepare($sql);
        $stmt->execute([
            'status' => static::STATUS_COMPLETE,
        ]);

        $ret = [];

        while ($row = $stmt->fetch()) {
            $ret[] = static::getInstanceFromRow($row);
        }

        return $ret;
    }

    /**
     * Absolute file path of original file.
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
     * @inheritdoc
     * @throws \JsonException
     */
    public function onChangeAfterSave(array $changeList)
    {
        if (array_key_exists('status', $changeList) && $this->status === static::STATUS_COMPLETE) {
            $this->generateAll();
        }

        parent::onChangeAfterSave($changeList);
    }

    /**
     * Generate all supported files. (e.g.: images)
     * @throws \JsonException
     */
    public function generateAll(): void
    {
        if (in_array($this->getOriginalExtension(), GeneratedFileImage::getValidConvertExtensions(), true)) {
            foreach (GeneratedFileImage::SIZE_KEYS as $sizeKey) {
                (new GeneratedFileImage($this, $sizeKey))->createIfNotExistsAsync();
            }
        }
    }

    /**
     * Combine chunks
     *
     * @return void
     * @throws \JsonException
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
            $this->statusChangeWebhook();

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
            $this->statusChangeWebhook();

            // Delete failed merged chunk.
            unlink($this->getFilePath());

            throw new \LogicException('Expected file size is ' . $this->total_size . ' byte, instead it is ' . $fileSize . ' byte');
        }

        $this->status = File::STATUS_COMPLETE;
        $this->save();
        $this->statusChangeWebhook();

        // Cleanup chunks
        foreach ($chunks as $chunk) {
            unlink($chunk->getFilePath());
            $chunk->deleteFromDB();
        }
    }

    /**
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->is_private === 1;
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

    /**
     * @return array
     */
    public function getDetailsForClient(): array
    {
        return [
            'uuid' => $this->download_uuid,
            'status' => $this->getStatusForClient(),
            'name' => $this->name,
            'originalName' => $this->original_name,
            'createdAt' => strtotime($this->created_at),
            'updatedAt' => strtotime($this->updated_at),
            'deletedAt' => $this->deleted_at !== null ? strtotime($this->deleted_at) : null,
        ];
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function statusChangeWebhook(): void
    {
        if (getenv('STATUS_CHANGE_WEBHOOK') === false) {
            return;
        }

        Base::getAmqp()->publish(StatusChangeWebhookJob::getName(), [
            'fileId' => $this->id,
        ]);
    }

    /**
     * Original extension
     *
     * @return string|null
     */
    public function getOriginalExtension(): ?string
    {
        $extension = mb_strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));

        if ($extension === '') {
            return null;
        }

        return $extension;
    }

    /**
     * Upload file
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
