<?php

declare(strict_types = 1);

namespace Acme\Auth;

use Acme\Http\BadRequestException;
use Acme\Http\ForbiddenException;
use Acme\Http\UnauthorizedException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

class Auth
{
    private string $authUrl;
    private Client $_client;

    public const REQUEST_PARAMETER_ACCESS_TOKEN = 'accessToken';
    public const REQUEST_PARAMETER_METHOD = 'method';
    public const REQUEST_PARAMETER_FILE_UUID = 'fileUuid';

    public const METHOD_DOWNLOAD = 'download';
    public const METHOD_UPLOAD = 'upload';

    public function __construct()
    {
        $authUrl = getenv('AUTH_URL');

        if ($authUrl === false) {
            throw new \LogicException('Auth url not set.');
        }

        $this->authUrl = $authUrl;
    }

    /**
     * @return Client
     */
    private function getClient(): Client
    {
        if (isset($this->_client)) {
            return $this->_client;
        }

        return $this->_client = new Client();
    }

    /**
     * @throws ForbiddenException
     * @throws GuzzleException
     * @throws UnauthorizedException
     * @throws BadRequestException
     */
    public function run(array $requestParams): void
    {
        try {
            $this->getClient()->post($this->authUrl, [
                RequestOptions::JSON => $requestParams,
                RequestOptions::TIMEOUT => 5,
            ]);
        } catch (ClientException $e) {
            if (!$e->hasResponse()) {
                throw $e;
            }

            match ($e->getResponse()->getStatusCode()) {
                400 => throw new BadRequestException(),
                401 => throw new UnauthorizedException(),
                403 => throw new ForbiddenException(),
                default => throw $e,
            };
        }
    }
}
