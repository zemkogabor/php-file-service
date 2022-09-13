<?php

declare(strict_types = 1);

namespace Acme\Index;

use Acme\Index\Endpoints\ChunkedUploadCompleteEndpoint;
use Acme\Index\Endpoints\ChunkedUploadEndpoint;
use Acme\Index\Endpoints\DownloadEndpoint;
use Acme\Index\Endpoints\Endpoint;
use Acme\Router\BadRequestException;
use Acme\Router\NotFoundException;
use Acme\Router\Router;

class Index
{
    /**
     * @return void
     */
    public static function processIndex(): void
    {
        Endpoint::setCorsHeaders();

        $router = new Router();

        $router->match(['POST'], '/chunked-upload', new ChunkedUploadEndpoint());
        $router->match(['PUT'], '/chunked-upload-complete', new ChunkedUploadCompleteEndpoint());
        $router->match(['GET'], '/download/([a-zA-Z0-9-]+)', new DownloadEndpoint());

        try {
            $router->run();
        } catch (NotFoundException) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
        } catch (BadRequestException $e) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request', true, 400);

            if ($e->publicMessages !== []) {
                header('Content-Type: application/json');

                echo json_encode([
                    'messages' => $e->publicMessages,
                ], JSON_UNESCAPED_UNICODE);
            }
        }
    }
}
