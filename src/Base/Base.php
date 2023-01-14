<?php

declare(strict_types = 1);

namespace Acme\Base;

use Acme\Amqp\Amqp;
use Acme\Auth\Auth;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class Base
{
    private static Logger $_logger;
    private static Amqp $_amqp;
    private static Auth $_auth;

    /**
     * Return root dir, without "/" in the end of string
     *
     * @return string
     */
    public static function getRootDir(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * Application default logger.
     *
     * @return Logger
     */
    public static function getLogger(): Logger
    {
        if (isset(static::$_logger)) {
            return static::$_logger;
        }

        $logger = new Logger('general');
        $logger->pushHandler(new StreamHandler('php://stdout'));

        return static::$_logger = $logger;
    }

    /**
     * @return Amqp
     */
    public static function getAmqp(): Amqp
    {
        if (isset(static::$_amqp)) {
            return static::$_amqp;
        }

        return static::$_amqp = new Amqp(new AMQPStreamConnection(
            getenv('RABBITMQ_HOST'),
            getenv('RABBITMQ_PORT'),
            getenv('RABBITMQ_USER'),
            getenv('RABBITMQ_PASSWORD'),
        ));
    }

    /**
     * @return Auth
     */
    public static function getAuth(): Auth
    {
        if (isset(static::$_auth)) {
            return static::$_auth;
        }

        return static::$_auth = new Auth();
    }
}
