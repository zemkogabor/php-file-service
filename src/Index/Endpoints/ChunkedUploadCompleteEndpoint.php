<?php

declare(strict_types = 1);

namespace Acme\Index\Endpoints;

use Acme\Amqp\Jobs\ChunksCombineJob;
use Acme\Base\Base;
use Acme\Router\BadRequestException;
use Acme\File\File;
use Acme\File\Form\ChunkedUploadCompleteForm;
use DBLaci\Data\EtalonInstantiationException;

class ChunkedUploadCompleteEndpoint extends Endpoint
{
    /**
     * @throws BadRequestException
     * @throws \JsonException
     */
    public function run(): void
    {
        $validator = static::getValidator();
        $form = new ChunkedUploadCompleteForm();
        $form->uuid = static::getRequestJson()['uuid'] ?? null;

        $errors = $validator->validate($form);

        if ($errors->count() > 0) {
            static::throwValidationError($errors);
        }

        try {
            $file = File::getInstanceByUploadUuid($form->uuid);
        } catch (EtalonInstantiationException) {
            throw new BadRequestException();
        }

        $file->status = File::STATUS_READY_TO_COMBINE;
        $file->save();

        Base::getAmqp()->publish(ChunksCombineJob::getName(), [
            'fileId' => $file->id,
        ]);
    }
}
