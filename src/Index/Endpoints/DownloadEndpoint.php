<?php

declare(strict_types = 1);

namespace Acme\Index\Endpoints;

use Acme\File\File;
use Acme\File\Form\DownloadForm;
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

        $form = new DownloadForm();
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

        $response = new BinaryFileResponse($file->getFilePath());

        // Inline disposition is the preferred. The browser displays if it can.
        $response->setContentDisposition(HeaderUtils::DISPOSITION_INLINE);

        // todo: depend of accesskey existing
        // $response->setPrivate();

        // Based on the request, it sets a few things, for example the content type in the header.
        $response->prepare(new Request());

        $response->send();
    }
}
