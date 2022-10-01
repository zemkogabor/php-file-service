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

        $response = new JsonResponse($file->getDetailsForClient());

        $response->send();
    }
}
