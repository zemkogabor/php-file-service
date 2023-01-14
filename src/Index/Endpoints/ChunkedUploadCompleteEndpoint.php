<?php

declare(strict_types = 1);

namespace Acme\Index\Endpoints;

use Acme\Amqp\Jobs\ChunksCombineJob;
use Acme\Auth\Auth;
use Acme\Base\Base;
use Acme\Http\BadRequestException;
use Acme\File\File;
use Acme\File\Form\ChunkedUploadCompleteForm;
use Acme\Http\ForbiddenException;
use Acme\Http\UnauthorizedException;
use DBLaci\Data\EtalonInstantiationException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\JsonResponse;

class ChunkedUploadCompleteEndpoint extends Endpoint
{
    /**
     * @throws BadRequestException
     * @throws UnauthorizedException
     * @throws ForbiddenException
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function run(): void
    {
        $accessToken = $_SERVER['HTTP_ACCESSTOKEN'] ?? null;

        if ($accessToken === null) {
            throw new UnauthorizedException();
        }

        Base::getAuth()->run([
            Auth::REQUEST_PARAMETER_ACCESS_TOKEN => $accessToken,
            Auth::REQUEST_PARAMETER_METHOD => Auth::METHOD_UPLOAD,
        ]);

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

        $response = new JsonResponse($file->getDetailsForClient());

        $response->send();
    }
}
