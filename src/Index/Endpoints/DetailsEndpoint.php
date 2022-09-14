<?php

declare(strict_types = 1);

namespace Acme\Index\Endpoints;

use Acme\File\File;
use Acme\File\Form\IdentifyForm;
use Acme\Router\BadRequestException;
use Acme\Router\NotFoundException;
use DBLaci\Data\EtalonInstantiationException;
use Symfony\Component\HttpFoundation\JsonResponse;

class DetailsEndpoint extends Endpoint
{
    /**
     * @throws NotFoundException
     * @throws BadRequestException
     */
    public function run(): void
    {
        $validator = static::getValidator();

        $form = new IdentifyForm();
        $form->uuid = $this->pathParams[0] ?? null;

        $errors = $validator->validate($form);

        if ($errors->count() > 0) {
            static::throwValidationError($errors);
        }

        try {
            $file = File::getInstanceByDownloadUuid($form->uuid);
        } catch (EtalonInstantiationException) {
            throw new NotFoundException();
        }

        $response = new JsonResponse([
            'uuid' => $file->download_uuid,
            'status' => $file->getStatusForClient(),
            'name' => $file->name,
            'originalName' => $file->original_name,
            'createdAt' => strtotime($file->created_at),
            'updatedAt' => strtotime($file->updated_at),
            'deletedAt' => $file->deleted_at !== null ? strtotime($file->deleted_at) : null,
        ]);

        $response->send();
    }
}
