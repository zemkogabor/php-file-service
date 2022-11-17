<?php

declare(strict_types = 1);

namespace Acme\Index;

use Acme\Index\Endpoints\ChunkedUploadCompleteEndpoint;
use Acme\Index\Endpoints\ChunkedUploadEndpoint;
use Acme\Index\Endpoints\DownloadEndpoint;
use Acme\Index\Endpoints\Endpoint;
use Acme\Index\Endpoints\DetailsEndpoint;
use Acme\Index\Endpoints\UploadEndpoint;
use Acme\Router\BadRequestException;
use Acme\Router\MethodNotAllowedException;
use Acme\Router\NotFoundException;
use Acme\Router\Router;
use Acme\Router\UnauthorizedException;
use Symfony\Component\HttpFoundation\JsonResponse;

class Index
{
    /**
     * @return void
     */
    public static function processIndex(): void
    {
        Endpoint::setCorsHeaders();

        $router = new Router();

        $router->match(['POST'], '/upload', new UploadEndpoint());
        $router->match(['POST'], '/chunked-upload', new ChunkedUploadEndpoint());
        $router->match(['PUT'], '/chunked-upload-complete', new ChunkedUploadCompleteEndpoint());
        $router->match(['GET'], '/download/([a-zA-Z0-9-]+)', new DownloadEndpoint());
        $router->match(['GET'], '/details/([a-zA-Z0-9-]+)', new DetailsEndpoint());

        try {
            $router->run();
        } catch (MethodNotAllowedException) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed', true, 405);
        } catch (UnauthorizedException) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized', true, 401);
        } catch (NotFoundException) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
        } catch (BadRequestException $e) {
            if ($e->publicMessages !== []) {
                $response = new JsonResponse(['messages' => $e->publicMessages], 400);
                $response->send();
            }
        }
    }
}
