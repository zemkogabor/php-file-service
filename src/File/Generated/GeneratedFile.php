<?php

declare(strict_types = 1);

namespace Acme\File\Generated;

use Acme\File\File;

abstract class GeneratedFile
{
    protected File $originalFile;

    public function __construct(File $file)
    {
        $this->originalFile = $file;
    }

    /**
     * Generated file absolute path.
     *
     * @return string
     */
    abstract public function getFilePath(): string;

    /**
     * Generating file if not exists yet.
     *
     * @return void
     */
    abstract public function createIfNotExists(): void;

    /**
     * Generating file if not exists yet (async).
     *
     * @return void
     */
    abstract public function createIfNotExistsAsync(): void;

    /**
     * Valid convert extensions, e.g.: jpg => Image generate
     * @return string[]
     */
    abstract public static function getValidConvertExtensions(): array;
}
