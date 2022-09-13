<?php

declare(strict_types = 1);

namespace Acme\Cli\Actions;

abstract class Action
{
    abstract public function run(): void;
}
