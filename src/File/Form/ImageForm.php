<?php

declare(strict_types = 1);

namespace Acme\File\Form;

use Acme\File\Generated\GeneratedFileImage;
use Symfony\Component\Validator\Constraints as Assert;

class ImageForm
{
    #[Assert\NotBlank(message: 'UUID should not be blank.')]
    #[Assert\Uuid(message: 'UUID parameter is not a valid UUID.')]
    public mixed $uuid;

    #[Assert\NotBlank(message: 'Size should not be blank.')]
    #[Assert\Choice(choices: GeneratedFileImage::SIZE_KEYS, message: 'Not valid size value.')]
    public mixed $size;
}
