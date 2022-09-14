<?php

declare(strict_types = 1);

namespace Acme\File\Form;

use Symfony\Component\Validator\Constraints as Assert;

class IdentifyForm
{
    #[Assert\NotBlank(message: 'UUID should not be blank.')]
    #[Assert\Uuid(message: 'UUID parameter is not a valid UUID.')]
    public mixed $uuid;
}
