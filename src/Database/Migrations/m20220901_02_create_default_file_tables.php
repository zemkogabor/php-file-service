<?php

declare(strict_types = 1);

namespace Acme\Database\Migrations;

use Acme\Database\Database;
use Acme\Database\MigrationInterface;

class m20220901_02_create_default_file_tables implements MigrationInterface
{
    public function run(): bool
    {
        $db = Database::getPdo();

        $db->query(
            "CREATE TABLE file (" .
            "id serial PRIMARY KEY, " .
            "upload_uuid uuid NOT NULL, " .
            "download_uuid uuid NOT NULL, " .
            "chunk_size bigint NOT NULL, " .
            "total_size bigint NOT NULL, " .
            "name text NOT NULL, " .
            "original_name text NOT NULL, " .
            "total_chunk_count int NOT NULL, " .
            "is_private bool NOT NULL, " .
            "status text NOT NULL, " .
            "created_at timestamp with time zone, " .
            "updated_at timestamp with time zone, " .
            "deleted_at timestamp with time zone)"
        );

        $db->query(
            "CREATE TABLE chunked_file (" .
            "id serial PRIMARY KEY, " .
            "file_id int NOT NULL REFERENCES file (id), " .
            "index int NOT NULL, " .
            "size bigint NOT NULL, " .
            "created_at timestamp with time zone)"
        );

        return true;
    }
}
