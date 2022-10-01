<?php

declare(strict_types = 1);

namespace Acme\File\Form;

use Acme\File\ChunkedFile;
use Acme\File\File;
use Symfony\Component\Validator\Constraints as Assert;

class ChunkedUploadForm
{
    #[Assert\NotBlank(message: 'UUID should not be blank.')]
    #[Assert\Uuid(message: 'UUID parameter is not a valid UUID.')]
    public mixed $uuid;

    #[Assert\NotBlank(message: 'Name should not be blank.')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Name is too long. It should have {{ limit }} character or less.'
    )]
    public mixed $name;

    #[Assert\NotBlank(message: 'Chunk size should not be blank.')]
    #[Assert\Range(
        notInRangeMessage: 'Chunk size value should be between {{ min }} byte and {{ max }} byte.',
        min: 1_000_000, // 1MB
        max: 100_000_000 // 100MB
    )]
    public mixed $chunkSize;

    #[Assert\NotBlank(message: 'Total size should not be blank.')]
    #[Assert\Positive(message: 'Total size value should be positive.')]
    public mixed $totalSize;

    #[Assert\NotBlank(message: 'Total chunk count should not be blank.')]
    #[Assert\Positive(message: 'Total chunk count value should be positive.')]
    public mixed $totalChunkCount;

    #[Assert\NotBlank(message: 'Current chunk index should not be blank.')]
    #[Assert\PositiveOrZero(message: 'Current chunk index value should be positive or zero.')]
    public mixed $currentChunkIndex;

    #[Assert\NotBlank(message: 'Current chunk size should not be blank.')]
    #[Assert\Positive(message: 'Current chunk size value should be positive.')]
    public mixed $currentChunkSize;

    #[Assert\NotBlank(message: 'File should not be blank.')]
    #[Assert\File(
        maxSize: '100m',
    )]
    public mixed $file;

    #[Assert\NotNull(message: 'Private flag should not be blank.')]
    #[Assert\PositiveOrZero]
    public mixed $isPrivate;

    /**
     * @param File $file
     * @return File
     */
    public function fillToFile(File $file): File
    {
        $file->upload_uuid = (string) $this->uuid;
        $file->original_name = (string) $this->name;
        $file->chunk_size = (int) $this->chunkSize;
        $file->total_size = (int) $this->totalSize;
        $file->total_chunk_count = (int) $this->totalChunkCount;
        $file->is_private = (int) $this->isPrivate;

        return $file;
    }

    /**
     * @param ChunkedFile $chunkedFile
     * @return ChunkedFile
     */
    public function fillToChunkedFile(ChunkedFile $chunkedFile): ChunkedFile
    {
        $chunkedFile->index = (int) $this->currentChunkIndex;
        $chunkedFile->size = (int) $this->currentChunkSize;

        return $chunkedFile;
    }
}
