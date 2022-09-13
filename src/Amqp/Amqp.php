<?php

declare(strict_types = 1);

namespace Acme\Amqp;

use Acme\Amqp\Jobs\Job;
use Acme\Base\Base;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Amqp-php lib wrapper component
 */
class Amqp
{
    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;

    /**
     * Default exchange
     */
    protected const EXCHANGE_DEFAULT = 'default';

    public function __construct(AMQPStreamConnection $connection)
    {
        $this->connection = $connection;
        $this->channel = $connection->channel();
    }

    /**
     * Close channel and connection
     *
     * @return void
     */
    public function close(): void
    {
        $this->channel->close();

        if ($this->connection->isConnected()) {
            try {
                $this->connection->close();
            } catch (Exception $e) {
                Base::getLogger()->error($e);
            }
        }
    }

    /**
     * @return void
     */
    protected function exchangeDeclare(): void
    {
        /// We use only one exchange in the application.
        $this->channel->exchange_declare(
            static::EXCHANGE_DEFAULT,
            AMQPExchangeType::DIRECT,
            false,
            false,
            false
        );
    }

    /**
     * @param string $name
     * @return void
     */
    protected function queueDeclare(string $name): void
    {
        // Creating durable queue.
        // When RabbitMQ quits or crashes it will forget the queues and messages unless you tell it not to.
        $this->channel->queue_declare(
            $name,
            false,
            true,
            false,
            false
        );
    }

    /**
     * @param string $name
     * @param array $data
     * @return void
     * @throws \JsonException
     */
    public function publish(string $name, array $data = []): void
    {
        // Declare the exchange and queue here.
        $this->exchangeDeclare();
        $this->queueDeclare($name);

        // We need to mark our messages as persistent.
        $msg = new AMQPMessage(json_encode($data, JSON_THROW_ON_ERROR), [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        $this->channel->basic_publish($msg, static::EXCHANGE_DEFAULT, $name);
    }

    /**
     * @return void
     */
    public function consume(): void
    {
        // We declare the exchange and queue here, as well. Because we might start the consumer before the publisher,
        // we want to make sure the queue exists before we try to consume messages from it.
        $this->exchangeDeclare();

        foreach (Job::getAll() as $job) {
            $this->queueDeclare($job::getName());

            // This will guarantee that the job types are sent to the correct processor.
            $this->channel->queue_bind(
                $job::getName(),
                static::EXCHANGE_DEFAULT,
                $job::getName()
            );

            // This tells RabbitMQ not to give more than one message to a worker at a time.
            $this->channel->basic_qos(null, 1, null);

            // Each job has its own queue to support job-by-job parallel processing.
            $this->channel->basic_consume(
                $job::getName(),
                'consumer_' . $job::getName(),
                false,
                false,
                false,
                false,
                [$job, 'handle'],
            );
        }

        try {
            $this->channel->consume();
        } catch (\ErrorException $e) {
            Base::getLogger()->error($e);
        }
    }
}
