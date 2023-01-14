<?php

declare(strict_types = 1);

namespace Acme\Index\Endpoints;

use Acme\Auth\Auth;
use Acme\Base\Base;
use Acme\Http\BadRequestException;
use Acme\File\ChunkedFile;
use Acme\File\File;
use Acme\File\Form\ChunkedUploadForm;
use Acme\Http\ForbiddenException;
use Acme\Http\UnauthorizedException;
use DBLaci\Data\EtalonInstantiationException;
use GuzzleHttp\Exception\GuzzleException;
use Ramsey\Uuid\Uuid;

class ChunkedUploadEndpoint extends Endpoint
{
    /**
     * @throws BadRequestException
     * @throws UnauthorizedException
     * @throws ForbiddenException
     * @throws GuzzleException
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

        $form = new ChunkedUploadForm();
        $form->uuid = $_POST['uuid'] ?? null;
        $form->name = $_POST['name'] ?? null;
        $form->chunkSize = $_POST['chunkSize'] ?? null;
        $form->totalSize = $_POST['totalSize'] ?? null;
        $form->totalChunkCount = $_POST['totalChunkCount'] ?? null;
        $form->currentChunkIndex = $_POST['currentChunkIndex'] ?? null;
        $form->currentChunkSize = $_POST['currentChunkSize'] ?? null;
        $form->isPrivate = $_POST['isPrivate'] ?? null;
        $form->file = $_FILES['file']['tmp_name'] ?? null;

        $errors = $validator->validate($form);

        if ($errors->count() > 0) {
            static::throwValidationError($errors);
        }

        try {
            $file = File::getInstanceByUploadUuid($form->uuid);
        } catch (EtalonInstantiationException) {
            $file = new File();
            $file = $form->fillToFile($file);
            $file->download_uuid = Uuid::uuid4()->toString();
            // The file base name is the same as the download_uuid, but it could be something else, just be unique.
            $extension = $file->getOriginalExtension();
            $file->name = $file->download_uuid . ($extension !== null ? '.' . $extension : '');
            $file->status = File::STATUS_UPLOADING;

            $file->save(true);
        }

        try {
            ChunkedFile::getInstanceByFileIdAndIndex($file->id, (int) $form->currentChunkIndex);
            return; // Chunk already exists (maybe client error), ignoring re-upload.
        } catch (EtalonInstantiationException) {
            // Normal case.
        }

        $chunkedFile = new ChunkedFile();
        $chunkedFile = $form->fillToChunkedFile($chunkedFile);
        $chunkedFile->file_id = $file->id;

        $chunkedFile->save(true);
        $chunkedFile->upload($form->file);
    }
}
