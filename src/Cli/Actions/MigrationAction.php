<?php

declare(strict_types = 1);

namespace Acme\Cli\Actions;

use Acme\Base\Base;
use Acme\Database\MigrationProvider;

class MigrationAction extends Action
{
    public function run(): void
    {
        echo("Run migrations ... \n");

        $migrationFiles = [];
        foreach (glob(Base::getRootDir() . '/src/Database/Migrations/*.php') as $file) {
            $migrationFiles[] = pathinfo($file, PATHINFO_FILENAME);
        }

        foreach ($migrationFiles as $name) {
            if (MigrationProvider::isDone($name)) {
                continue;
            }

            echo($name . " ... ");
            MigrationProvider::run($name);
            echo("Done\n");
        }

        echo("All done (" . count($migrationFiles) . ")\n");
    }
}
