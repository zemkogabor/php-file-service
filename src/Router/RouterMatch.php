<?php

declare(strict_types = 1);

namespace Acme\Router;

use Acme\Index\Endpoints\Endpoint;

class RouterMatch
{
    private array $methods;
    private string $pattern;
    private Endpoint $endpoint;

    public function __construct(array $methods, string $pattern, Endpoint $endpoint)
    {
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->endpoint = $endpoint;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return Endpoint
     */
    public function getEndpoint(): Endpoint
    {
        return $this->endpoint;
    }
}
