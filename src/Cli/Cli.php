<?php

declare(strict_types = 1);

namespace Acme\Cli;

use Acme\Cli\Actions\MigrationAction;

class Cli
{
    public const ACTION_MIGRATE = 'migrate';

    public static function processAction(): void
    {
        $actionName = $_SERVER['argv'][1] ?? null;

        switch ($actionName) {
            case self::ACTION_MIGRATE:
                $action = new MigrationAction();
                break;
            default:
                echo "Action not found.\n";
                exit(1);
        }

        $action->run();
    }
}
