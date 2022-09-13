<?php

declare(strict_types = 1);

namespace Acme\Database\Migrations;

use Acme\Database\Database;
use Acme\Database\MigrationInterface;

class m20220901_01_create_migration_table implements MigrationInterface
{
    public function run(): bool
    {
        $db = Database::getPdo();

        $db->query(
            "CREATE TABLE migration (" .
            "id serial PRIMARY KEY, " .
            "key text NOT NULL, " .
            "created_at timestamp with time zone, " .
            "UNIQUE(key))"
        );

        return true;
    }
}
