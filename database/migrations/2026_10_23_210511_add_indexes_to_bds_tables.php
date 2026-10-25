<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Indexing untuk bds_processed_files
        if (Schema::hasTable('bds_processed_files')) {
            try { DB::statement('ALTER TABLE bds_processed_files ADD INDEX idx_bds_file_hash (file_hash)'); } catch (\Exception $e) {}
            try { DB::statement('ALTER TABLE bds_processed_files ADD INDEX idx_rows_count (rows_count)'); } catch (\Exception $e) {}
        }

        // 2. Indexing untuk Event Record 1 & 2 (menggunakan prefix index panjang 50 untuk menghindari batas 3072 bytes)
        foreach (['event_record_1', 'event_record_2'] as $tableName) {
            if (Schema::hasTable($tableName)) {
                try {
                    DB::statement("ALTER TABLE {$tableName} ADD UNIQUE INDEX {$tableName}_composite_idx (timestamp(50), transport_id(50), batch_number(50), tray_id(50), pallet_id(50))");
                } catch (\Exception $e) {}
            }
        }

        // 3. Indexing untuk Event Record 3
        if (Schema::hasTable('event_record_3')) {
            try {
                DB::statement('ALTER TABLE event_record_3 ADD UNIQUE INDEX event_record_3_unique_idx (timestamp(50), batch_number(50), hu_id(50))');
            } catch (\Exception $e) {}
        }

        // 4. Indexing untuk Event Record 4
        if (Schema::hasTable('event_record_4')) {
            try {
                DB::statement('ALTER TABLE event_record_4 ADD UNIQUE INDEX event_record_4_timestamp_idx (timestamp(50))');
            } catch (\Exception $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bds_processed_files')) {
            try { DB::statement('ALTER TABLE bds_processed_files DROP INDEX idx_bds_file_hash'); } catch (\Exception $e) {}
            try { DB::statement('ALTER TABLE bds_processed_files DROP INDEX idx_rows_count'); } catch (\Exception $e) {}
        }
    }
};