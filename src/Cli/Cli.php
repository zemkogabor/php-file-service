<?php

declare(strict_types = 1);

namespace Acme\Cli;

use Acme\Cli\Actions\FileGenerateAction;
use Acme\Cli\Actions\MigrationAction;

class Cli
{
    public const ACTION_MIGRATE = 'migrate';
    public const ACTION_FILE_GENERATE = 'file-generate';

    public static function processAction(): void
    {
        $actionName = $_SERVER['argv'][1] ?? null;

        switch ($actionName) {
            case self::ACTION_MIGRATE:
                $action = new MigrationAction();
                break;
            case self::ACTION_FILE_GENERATE:
                $action = new FileGenerateAction();
                break;
            default:
                echo "Action not found.\n";
                exit(1);
        }

        $action->run();
    }
}
