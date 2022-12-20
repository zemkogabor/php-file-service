<?php

declare(strict_types = 1);

namespace Acme\File\Generated;

use Acme\Amqp\Jobs\ImageGenerateJob;
use Acme\Base\Base;
use Acme\File\File;
use Imagick;
use ImagickException;

class GeneratedFileImage extends GeneratedFile
{
    public const IMAGE_SIZE_KEY_XS = 'xs'; // Extra Small
    public const IMAGE_SIZE_KEY_SM = 'sm'; // Small
    public const IMAGE_SIZE_KEY_MD = 'md'; // Medium
    public const IMAGE_SIZE_KEY_LG = 'lg'; // Large
    public const IMAGE_SIZE_KEY_XL = 'xl'; // Extra Large

    public const SIZE_KEYS = [
        GeneratedFileImage::IMAGE_SIZE_KEY_XS,
        GeneratedFileImage::IMAGE_SIZE_KEY_SM,
        GeneratedFileImage::IMAGE_SIZE_KEY_MD,
        GeneratedFileImage::IMAGE_SIZE_KEY_LG,
        GeneratedFileImage::IMAGE_SIZE_KEY_XL,
    ];

    /**
     * All generated images with same extension.
     */
    public const GENERATED_EXTENSION = 'jpg';

    protected string $sizeKey;

    public function __construct(File $file, string $sizeKey)
    {
        $this->sizeKey = $sizeKey;
        parent::__construct($file);
    }

    /**
     * @inheritdoc
     */
    public function getFilePath(): string
    {
        return Base::getRootDir()
            . '/generated/'
            . date('Y', strtotime($this->originalFile->created_at)) . '/'
            . date('m', strtotime($this->originalFile->created_at))
            . '/' . $this->getFileName();
    }

    /**
     * Return dynamic file name.
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->originalFile->download_uuid
            . '-' . $this->sizeKey
            . '-' . static::getImageSizeVersions()[$this->sizeKey]
            . '.' . static::GENERATED_EXTENSION;
    }

    /**
     * Return image widths by sizes in pixel.
     *
     * @return int[]
     */
    public static function getImagesWidths(): array
    {
        return [
            self::IMAGE_SIZE_KEY_XS => 180,
            self::IMAGE_SIZE_KEY_SM => 360,
            self::IMAGE_SIZE_KEY_MD => 1000,
            self::IMAGE_SIZE_KEY_LG => 1600,
            self::IMAGE_SIZE_KEY_XL => 2500,
        ];
    }

    /**
     * Image size versions.
     * When one of the dimensions changes, the version must be increased.
     *
     * @return array
     */
    public static function getImageSizeVersions(): array
    {
        return [
            self::IMAGE_SIZE_KEY_XS => 1,
            self::IMAGE_SIZE_KEY_SM => 1,
            self::IMAGE_SIZE_KEY_MD => 1,
            self::IMAGE_SIZE_KEY_LG => 1,
            self::IMAGE_SIZE_KEY_XL => 1,
        ];
    }

    /**
     * @inheritdoc
     * @throws ImagickException
     */
    public function createIfNotExists(): void
    {
        // If the file already exists, it will not be regenerated.
        if (file_exists($this->getFilePath())) {
            return;
        }

        // Create dir if didn't exist before.
        $dir = dirname($this->getFilePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $imagick = new Imagick($this->originalFile->getFilePath());

        // Fixed width and proportional height.
        $imagick->thumbnailImage(static::getImagesWidths()[$this->sizeKey], 0);

        file_put_contents($this->getFilePath(), $imagick->getImageBlob());
    }

    /**
     * @inheritdoc
     * @throws \JsonException
     */
    public function createIfNotExistsAsync(): void
    {
        Base::getAmqp()->publish(ImageGenerateJob::getName(), [
            'fileId' => $this->originalFile->id,
            'sizeKey' => $this->sizeKey,
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getValidConvertExtensions(): array
    {
        return  [
            'jpeg',
            'jpg',
            'png',
        ];
    }
}
