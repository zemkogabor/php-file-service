#!/usr/bin/env php
<?php

declare(strict_types = 1);

use Acme\Base\Base;
use Monolog\ErrorHandler;

require __DIR__ . '/vendor/autoload.php';

ErrorHandler::register(Base::getLogger());

$amqp = Base::getAmqp();

register_shutdown_function(function() use($amqp) {
    $amqp->close();
});

echo 'Start listening...', "\n";

$amqp->consume();