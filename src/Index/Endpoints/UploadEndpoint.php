<?php

declare(strict_types = 1);

namespace Acme\Index\Endpoints;

use Acme\Database\Database;
use Acme\File\Form\UploadForm;
use Acme\Router\BadRequestException;
use Acme\File\File;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;

class UploadEndpoint extends Endpoint
{
    /**
     * @throws BadRequestException
     */
    public function run(): void
    {
        $validator = static::getValidator();

        $form = new UploadForm();
        $form->uuid = $_POST['uuid'] ?? null;
        $form->name = $_POST['name'] ?? null;
        $form->isPrivate = $_POST['isPrivate'] ?? null;
        $form->file = $_FILES['file']['tmp_name'] ?? null;

        $errors = $validator->validate($form);

        if ($errors->count() > 0) {
            static::throwValidationError($errors);
        }

        $file = new File();
        $file = $form->fillToFile($file);
        $file->download_uuid = Uuid::uuid4()->toString();
        $extension = $file->getOriginalExtension();
        $file->name = $file->download_uuid . ($extension !== null ? '.' . $extension : '');
        $file->total_chunk_count = 1;
        $file->total_size = filesize($form->file);
        $file->chunk_size = $file->total_size;
        $file->status = File::STATUS_UPLOADING;

        Database::getPdo()->exec('START TRANSACTION');

        // We need to save before upload, because the file path is calculated from the creation date.
        $file->save(true);

        $file->upload($form->file);
        $file->status = File::STATUS_COMPLETE;
        $file->save();

        Database::getPdo()->exec('COMMIT');

        $response = new JsonResponse($file->getDetailsForClient());

        $response->send();
    }
}
