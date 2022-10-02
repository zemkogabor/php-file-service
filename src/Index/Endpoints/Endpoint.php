<?php

declare(strict_types = 1);

namespace Acme\Index\Endpoints;

use Acme\Router\BadRequestException;
use Acme\Router\NotFoundException;
use Acme\Router\UnauthorizedException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use JsonException;
use Symfony\Component\Mime\Exception\LogicException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class Endpoint
{
    /**
     * Url path params, e.g.: "/download/:uuid" -> "$params[0]"
     * @var array
     */
    public array $pathParams;

    private static ValidatorInterface $_validator;

    /**
     * Calling auth service
     *
     * @return void
     * @throws UnauthorizedException|BadRequestException
     */
    public function validateAuth(): void
    {
        // The accessToken must exist as a header OR get parameter.
        if (array_key_exists('HTTP_ACCESSTOKEN', $_SERVER)) {
            $accessToken = $_SERVER['HTTP_ACCESSTOKEN'];
        } else if (array_key_exists('accessToken', $_GET)) {
            $accessToken = $_GET['accessToken'];
        } else {
            throw new UnauthorizedException();
        }

        $authUrl = getenv('AUTH_URL');

        if ($authUrl === false) {
            throw new \LogicException('Auth url not set.');
        }

        $client = new Client();

        $request = [
            'accessToken' => $accessToken,
        ];

        $response = $client->post($authUrl, [
            RequestOptions::JSON => $request,
            RequestOptions::TIMEOUT => 10,
        ]);

        switch ($response->getStatusCode()) {
            case 200:
                return;
            case 401:
                throw new UnauthorizedException();
            default:
                throw new LogicException('Unexpected error during validation, status code: "' . $response->getStatusCode() . '"');
        }
    }

    /**
     * @return void
     * @throws BadRequestException
     * @throws NotFoundException
     */
    abstract public function run(): void;

    /**
     * @return void
     */
    public static function setCorsHeaders(): void
    {
        header('Access-Control-Max-Age: 86400');

        if (array_key_exists('HTTP_ORIGIN', $_SERVER)) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            header('Access-Control-Allow-Credentials: true');
        }

        if (array_key_exists('REQUEST_METHOD', $_SERVER) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            if (array_key_exists('HTTP_ACCESS_CONTROL_REQUEST_METHOD', $_SERVER)) {
                header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS, DELETE');
            }
            if (array_key_exists('HTTP_ACCESS_CONTROL_REQUEST_HEADERS', $_SERVER)) {
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            }
            exit(0);
        }
    }

    /**
     * @return array
     * @throws BadRequestException
     */
    public static function getRequestJson(): array
    {
        try {
            return json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new BadRequestException('php://input is not valid json.');
        }
    }

    /**
     * @return ValidatorInterface
     */
    public static function getValidator(): ValidatorInterface
    {
        if (isset(static::$_validator)) {
            return static::$_validator;
        }

        $validatorBuilder = Validation::createValidatorBuilder();
        $validatorBuilder->enableAnnotationMapping();

        return static::$_validator = $validatorBuilder->getValidator();
    }

    /**
     * @throws BadRequestException
     */
    public static function throwValidationError(ConstraintViolationListInterface $constraintViolationList): void
    {
        $messages = [];
        foreach ($constraintViolationList as $constraintViolation) {
            $messages[] = $constraintViolation->getMessage();
        }

        $exception = new BadRequestException();
        $exception->publicMessages = $messages;

        throw $exception;
    }
}
