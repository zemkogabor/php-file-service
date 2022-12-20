<?php

declare(strict_types = 1);

namespace Acme\Cli\Actions;

use Acme\File\File;

class FileGenerateAction extends Action
{
    /**
     * @throws \JsonException
     */
    public function run(): void
    {
        echo("Generate files (asnyc) ... \n");

        foreach (File::listAllCompleted() as $file) {
            $file->generateAll();
        }

        echo("Done!\n");
    }
}
