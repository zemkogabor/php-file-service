<?php

declare(strict_types = 1);

namespace Acme\Amqp\Jobs;

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
     */
    public function process(array $data): void
    {
        $file = File::getInstanceByID($data['fileId']);
        $file->combineChunks();
    }
}
