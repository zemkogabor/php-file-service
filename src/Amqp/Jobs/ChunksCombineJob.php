<?php

declare(strict_types = 1);

namespace Acme\Amqp\Jobs;

use Acme\Base\Base;
use Acme\File\File;

class ChunksCombineJob extends Job
{
    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'chunks_combine';
    }

    /**
     * @inheritdoc
     * @throws \DBLaci\Data\EtalonInstantiationException
     * @throws \JsonException
     */
    public function process(array $data): void
    {
        $file = File::getInstanceByID($data['fileId']);
        $file->combineChunks();

        if (getenv('STATUS_CHANGE_WEBHOOK') !== false) {
            Base::getAmqp()->publish(StatusChangeWebhookJob::getName(), [
                'fileId' => $file->id,
            ]);
        }
    }
}
