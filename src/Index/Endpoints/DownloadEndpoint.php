<?php

declare(strict_types = 1);

namespace Acme\Index\Endpoints;

use Acme\File\File;
use Acme\File\Form\IdentifyForm;
use Acme\Router\BadRequestException;
use Acme\Router\NotFoundException;
use DBLaci\Data\EtalonInstantiationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;

class DownloadEndpoint extends Endpoint
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

        if (!$file->isCompleted()) {
            throw new NotFoundException();
        }

        $response = new BinaryFileResponse($file->getFilePath());

        $response->setContentDisposition(HeaderUtils::DISPOSITION_ATTACHMENT);

        if ($file->is_private === 1) {
            $response->setPrivate();
        }

        // Based on the request, it sets a few things, for example the content type in the header.
        $response->prepare(new Request());

        $response->send();
    }
}
