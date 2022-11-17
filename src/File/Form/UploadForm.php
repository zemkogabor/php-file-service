<?php

declare(strict_types = 1);

namespace Acme\File\Form;

use Acme\File\File;
use Symfony\Component\Validator\Constraints as Assert;

class UploadForm
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

    #[Assert\NotBlank(message: 'File should not be blank.')]
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
        $file->is_private = (int) $this->isPrivate;

        return $file;
    }
}
