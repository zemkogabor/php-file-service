<?php

declare(strict_types = 1);

namespace Acme\Amqp\Jobs;

use Acme\Amqp\FailedJob;
use Acme\Base\Base;
use PhpAmqpLib\Message\AMQPMessage;

abstract class Job
{
    /**
     * @return string
     */
    abstract public static function getName(): string;

    /**
     * @param array $data
     * @return void
     */
    abstract protected function process(array $data): void;

    /**
     * @param AMQPMessage $message
     * @return void
     * @throws \JsonException
     */
    public function handle(AMQPMessage $message): void
    {
        $data = json_decode($message->body, true, 512, JSON_THROW_ON_ERROR);

        try {
            Base::getLogger()->info('Job started: "' . $this::getName() . '"');
            $this->process($data);
            Base::getLogger()->info('Job finished: "' . $this::getName() . '"');
        } catch (\Throwable $e) {
            // Failed, but acknowledge message (there will be no retry).
            Base::getLogger()->error($e);

            // Saving failed job into database.
            $failedJob = new FailedJob();
            $failedJob->name = static::getName();
            $failedJob->data = json_encode($data, JSON_THROW_ON_ERROR);
            $failedJob->retry_attempt_count = 0;
            $failedJob->message = $e->getMessage();
            $failedJob->save(true);
        }

        $message->ack();
    }

    /**
     * @return Job[]
     */
    public static function getAll(): array
    {
        return  [
            new ChunksCombineJob(),
            new StatusChangeWebhookJob(),
        ];
    }
}
