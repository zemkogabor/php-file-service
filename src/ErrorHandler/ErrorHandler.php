<?php

declare(strict_types = 1);

namespace Acme\ErrorHandler;

class ErrorHandler
{
    /**
     * Whether this instance has been registered using `register()`
     *
     * @var bool
     */
    private bool $_registered = false;

    /**
     * Register error handler.
     *
     * @return void
     */
    public function register(): void
    {
        if ($this->_registered) {
            return;
        }

        register_shutdown_function([$this, 'handleFatalError']);

        $this->_registered = true;
    }

    /**
     * @return void
     */
    public function handleFatalError(): void
    {
        $error = error_get_last();
        if ($error !== null) {
            if (PHP_SAPI === 'cli') {
                echo($error['message'] . "\n");
            } else {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
            }
        }
    }
}
