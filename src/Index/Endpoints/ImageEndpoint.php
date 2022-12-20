<?php

declare(strict_types = 1);

namespace Acme\Index\Endpoints;

use Acme\File\File;
use Acme\File\Form\ImageForm;
use Acme\File\Generated\GeneratedFileImage;
use Acme\Router\BadRequestException;
use Acme\Router\NotFoundException;
use DBLaci\Data\EtalonInstantiationException;
use ImagickException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;

class ImageEndpoint extends Endpoint
{
    /**
     * @throws NotFoundException
     * @throws BadRequestException
     * @throws ImagickException
     */
    public function run(): void
    {
        $validator = static::getValidator();

        $form = new ImageForm();
        $form->uuid = $this->pathParams[0] ?? null;
        $form->size = $_GET['size'] ?? null;

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

        if (!in_array($file->getOriginalExtension(), GeneratedFileImage::getValidConvertExtensions(), true)) {
            throw new NotFoundException();
        }

        $generatedFile = new GeneratedFileImage($file, $form->size);
        $generatedFile->createIfNotExists();
        $filePath = $generatedFile->getFilePath();
        $fileName = $generatedFile->getFileName();

        $response = new BinaryFileResponse($filePath);

        $response->setContentDisposition(HeaderUtils::DISPOSITION_INLINE, $fileName);

        if ($file->is_private === 1) {
            $response->setPrivate();
        }

        // Based on the request, it sets a few things, for example the content type in the header.
        $response->prepare(new Request());

        $response->send();
    }
}
