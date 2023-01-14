<?php

declare(strict_types = 1);

namespace Acme\Http;

class BadRequestException extends \Exception
{
    public array $publicMessages = [];
}
