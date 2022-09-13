<?php

declare(strict_types = 1);

namespace Acme\Database;

interface MigrationInterface
{
    public function run(): bool;
}
