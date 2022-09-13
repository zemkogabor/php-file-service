<?php

declare(strict_types = 1);

namespace Acme\Database\Migrations;

use Acme\Database\Database;
use Acme\Database\MigrationInterface;

class m20220911_01_create_failed_job_table implements MigrationInterface
{
    public function run(): bool
    {
        $db = Database::getPdo();

        $db->query(
            "CREATE TABLE failed_job (" .
            "id serial PRIMARY KEY, " .
            "name text NOT NULL, " .
            "data jsonb NOT NULL, " .
            "message text NOT NULL, " .
            "retry_attempt_count int NOT NULL, " .
            "created_at timestamp with time zone)"
        );

        return true;
    }
}
