<?php

declare(strict_types = 1);

namespace Acme\Router;

use Acme\Index\Endpoints\Endpoint;

class Router
{
    /**
     * @var RouterMatch[]
     */
    private array $matches = [];

    /**
     * @param array $methods
     * @param string $pattern
     * @param Endpoint $endpoint
     * @return void
     */
    public function match(array $methods, string $pattern, Endpoint $endpoint): void
    {
        $this->matches[] = new RouterMatch($methods, $pattern, $endpoint);
    }

    /**
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws MethodNotAllowedException
     * @throws UnauthorizedException
     */
    public function run(): void
    {
        $uri = static::getCurrentUri();
        foreach ($this->matches as $match) {
            $isMatch = boolval(preg_match('#^' . $match->getPattern() . '$#', $uri, $patternMatches));

            if (!$isMatch) {
                continue;
            }

            if (!in_array(strtoupper($_SERVER['REQUEST_METHOD']), $match->getMethods(), true)) {
                throw new MethodNotAllowedException();
            }

            $pathParams = $patternMatches;
            unset($pathParams[0]); // 0. value is the full path
            $pathParams = array_values($pathParams); // Reindex values

            $endpoint = $match->getEndpoint();
            $endpoint->pathParams = $pathParams;

            $endpoint->validateAuth();

            $endpoint->run();
            return;
        }

        throw new NotFoundException();
    }

    /**
     * Define the current relative URI.
     *
     * @return string
     */
    private static function getCurrentUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        // Don't take query params into account on the URL
        if (str_contains($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        // Remove trailing slash + enforce a slash at the start
        return '/' . trim($uri, '/');
    }
}
