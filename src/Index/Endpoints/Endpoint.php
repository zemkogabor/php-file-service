<?php

declare(strict_types = 1);

namespace Acme\Index\Endpoints;

use Acme\Base\Base;
use Acme\Router\BadRequestException;
use Acme\Router\NotFoundException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use JsonException;
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
     * @return bool
     */
    public function validateAuth(): bool
    {
        // The accessToken must exist as a header OR get parameter.
        if (array_key_exists('HTTP_ACCESSTOKEN', $_SERVER)) {
            $accessToken = $_SERVER['HTTP_ACCESSTOKEN'];
        } else if (array_key_exists('accessToken', $_GET)) {
            $accessToken = $_GET['accessToken'];
        } else {
            return false;
        }

        $authUrl = getenv('AUTH_URL');

        if ($authUrl === false) {
            throw new \LogicException('Auth url not set.');
        }

        $client = new Client();

        $request = [
            'accessToken' => $accessToken,
        ];

        try {
            $response = $client->post($authUrl, [
                RequestOptions::JSON => $request,
                RequestOptions::TIMEOUT => 15,
            ]);
        } catch (GuzzleException $e) {
            Base::getLogger()->error($e->getMessage(), [
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return  false;
        }

        return true;
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
