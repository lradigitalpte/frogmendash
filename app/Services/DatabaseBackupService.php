<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Streams a SQL dump of the current database using the app's own PDO connection
 * (works against MySQL 8 / caching_sha2 with no external mysqldump binary).
 *
 * The dump is streamed straight to the requesting admin as a file download — it
 * is NOT stored on S3, because the media bucket has a public-read policy and a
 * database dump (PII + password hashes) must never be publicly readable. For
 * automated off-site backups, use a dedicated PRIVATE bucket (separate from the
 * public media bucket) — see TENANCY_REDESIGN_PLAN.md follow-ups.
 */
class DatabaseBackupService
{
    /**
     * Write a full SQL dump to the given writable stream resource.
     *
     * @param  resource  $handle
     */
    public function streamTo($handle): void
    {
        $pdo = DB::connection()->getPdo();
        $dbName = DB::connection()->getDatabaseName();

        fwrite($handle, "-- Backup of `{$dbName}` — ".now()->toDateTimeString()." UTC\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");

        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $meta = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);

            if (isset($meta['Create View'])) {
                fwrite($handle, "\nDROP VIEW IF EXISTS `{$table}`;\n".$meta['Create View'].";\n\n");

                continue;
            }

            $ddl = $meta['Create Table'] ?? null;
            if (! $ddl) {
                continue;
            }

            fwrite($handle, "\nDROP TABLE IF EXISTS `{$table}`;\n{$ddl};\n\n");

            $rows = $pdo->query("SELECT * FROM `{$table}`");
            while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                $columns = '`'.implode('`, `', array_keys($row)).'`';
                $values = implode(', ', array_map(
                    fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value),
                    array_values($row),
                ));
                fwrite($handle, "INSERT INTO `{$table}` ({$columns}) VALUES ({$values});\n");

                if (function_exists('fflush')) {
                    fflush($handle);
                }
            }
        }

        fwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");
    }

    /**
     * Suggested filename for a download.
     */
    public function filename(): string
    {
        $dbName = DB::connection()->getDatabaseName();

        return $dbName.'-backup-'.now()->format('Y-m-d_His').'.sql';
    }
}
