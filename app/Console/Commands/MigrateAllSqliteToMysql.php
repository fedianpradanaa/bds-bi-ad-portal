<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MigrateAllSqliteToMysql extends Command
{
    protected $signature = 'db:migrate-all-sqlite';
    protected $description = 'Migrate ALL tables and data from SQLite to MySQL dynamically, excluding cache and session tables';

    public function handle()
    {
        $this->info('Starting full migration from SQLite to MySQL...');

        // 1. Ambil daftar semua nama tabel dari database SQLite
        $tablesQuery = DB::connection('sqlite_prod')->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        
        if (empty($tablesQuery)) {
            $this->warn('No tables found in SQLite database.');
            return;
        }

        // Daftar tabel sistem yang tidak perlu dimigrasikan datanya
        $excludedTables = ['sessions', 'cache', 'cache_locks'];

        foreach ($tablesQuery as $tableObj) {
            $tableName = $tableObj->name;

            // Lewati tabel sistem/cache/session
            if (in_array($tableName, $excludedTables)) {
                $this->info("Skipping system/cache table: {$tableName}");
                continue;
            }

            $this->info("Processing table: {$tableName}");

            // Ambil seluruh data dari tabel tersebut di SQLite
            $rows = DB::connection('sqlite_prod')->table($tableName)->get();

            if ($rows->isEmpty()) {
                $this->info("Table {$tableName} is empty. Skipping data insertion.");
                continue;
            }

            // 2. Buat struktur tabel di MySQL secara otomatis jika belum ada
            if (!Schema::connection('mysql')->hasTable($tableName)) {
                $this->info("Creating table {$tableName} structure in MySQL...");
                
                $firstRow = (array) $rows->first();
                
                Schema::connection('mysql')->create($tableName, function ($table) use ($firstRow, $tableName) {
                    foreach ($firstRow as $columnName => $value) {
                        if ($columnName === 'id' && $tableName !== 'failed_jobs') {
                            $table->id();
                        } elseif (str_contains($columnName, '_at') || str_contains($columnName, 'time')) {
                            $table->dateTime($columnName)->nullable();
                        } elseif (is_int($value)) {
                            $table->bigInteger($columnName)->nullable();
                        } elseif (is_float($value)) {
                            $table->decimal($columnName, 15, 4)->nullable();
                        } else {
                            $table->text($columnName)->nullable();
                        }
                    }
                });
            }

            // 3. Masukkan data dengan chunking (per 500 baris) agar aman untuk memori
            $this->info("Inserting data into MySQL for {$tableName}...");
            
            $chunks = $rows->chunk(500);
            foreach ($chunks as $chunk) {
                $dataToInsert = $chunk->map(function ($item) {
                    return (array) $item;
                })->toArray();

                $firstItem = reset($dataToInsert);
                $uniqueKeys = array_key_exists('id', $firstItem) ? ['id'] : [array_key_first($firstItem)];

                DB::connection('mysql')->table($tableName)->upsert(
                    $dataToInsert, 
                    $uniqueKeys, 
                    array_keys($firstItem)
                );
            }

            $this->info("Table {$tableName} successfully migrated!");
        }

        $this->info('All essential tables and data successfully migrated from SQLite to MySQL!');
    }
}
