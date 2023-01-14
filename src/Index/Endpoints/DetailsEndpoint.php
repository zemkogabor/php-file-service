<?php

declare(strict_types = 1);

namespace Acme\Index\Endpoints;

use Acme\Auth\Auth;
use Acme\Base\Base;
use Acme\File\File;
use Acme\File\Form\IdentifyForm;
use Acme\Http\BadRequestException;
use Acme\Http\ForbiddenException;
use Acme\Http\NotFoundException;
use Acme\Http\UnauthorizedException;
use DBLaci\Data\EtalonInstantiationException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\JsonResponse;

class DetailsEndpoint extends Endpoint
{
    /**
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws UnauthorizedException
     * @throws ForbiddenException
     * @throws GuzzleException
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

        if ($file->isPrivate()) {
            $accessToken = $_GET['accessToken'] ?? null;

            if ($accessToken === null) {
                throw new UnauthorizedException();
            }

            Base::getAuth()->run([
                Auth::REQUEST_PARAMETER_ACCESS_TOKEN => $accessToken,
                Auth::REQUEST_PARAMETER_METHOD => Auth::METHOD_DOWNLOAD,
                Auth::REQUEST_PARAMETER_FILE_UUID => $file->download_uuid,
            ]);
        }

        $response = new JsonResponse($file->getDetailsForClient());

        $response->send();
    }
}
