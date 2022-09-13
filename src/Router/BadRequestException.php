<?php

declare(strict_types = 1);

namespace Acme\Router;

class BadRequestException extends \Exception
{
    public array $publicMessages = [];
}
