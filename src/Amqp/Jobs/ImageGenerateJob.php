<?php

declare(strict_types = 1);

namespace Acme\Amqp\Jobs;

use Acme\File\File;
use Acme\File\Generated\GeneratedFileImage;

class ImageGenerateJob extends Job
{
    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'image_generate';
    }

    /**
     * @inheritdoc
     * @throws \DBLaci\Data\EtalonInstantiationException
     * @throws \ImagickException
     */
    public function process(array $data): void
    {
        $file = File::getInstanceByID($data['fileId']);
        $sizeKey = $data['sizeKey'];

        $generatedFile = new GeneratedFileImage($file, $sizeKey);
        $generatedFile->createIfNotExists();
    }
}
