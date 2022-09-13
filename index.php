<?php

declare(strict_types = 1);

use Acme\Base\Base;
use Acme\ErrorHandler\ErrorHandler;
use Acme\Index\Index;
use Monolog\ErrorHandler as MonologErrorHandler;

require __DIR__ . '/vendor/autoload.php';

MonologErrorHandler::register(Base::getLogger());
(new ErrorHandler())->register(); // Custom error handler

Index::processIndex();