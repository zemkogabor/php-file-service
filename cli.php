#!/usr/bin/env php
<?php

declare(strict_types = 1);

use Acme\Base\Base;
use Acme\Cli\Cli;
use Monolog\ErrorHandler;

require __DIR__ . '/vendor/autoload.php';

ErrorHandler::register(Base::getLogger());

Cli::processAction();