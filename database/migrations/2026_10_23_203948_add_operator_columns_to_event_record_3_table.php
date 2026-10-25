<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('event_record_3')) {
            Schema::table('event_record_3', function (Blueprint $table) {
                // Daftar kolom baru yang akan ditambahkan jika belum ada
                $newColumns = [
                    'first_operator',
                    'second_operator',
                    'third_operator',
                    'teamleader',
                    'shift_name',
                ];

                foreach ($newColumns as $column) {
                    if (!Schema::hasColumn('event_record_3', $column)) {
                        $table->string($column)->nullable();
                    }
                }
            });

            // Cek dan tambahkan Unique Index jika belum ada untuk menghindari duplikasi data
            // (Opsional menggunakan try-catch agar tidak error jika index sudah pernah dibuat)
            try {
                Schema::table('event_record_3', function (Blueprint $table) {
                    $table->unique(['timestamp', 'batch_number', 'hu_id'], 'event_record_3_unique_idx');
                });
            } catch (\Exception $e) {
                // Index sudah ada, abaikan
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_record_3', function (Blueprint $table) {
            $table->dropUnique('event_record_3_unique_idx');
            $table->dropColumn([
                'first_operator',
                'second_operator',
                'third_operator',
                'teamleader',
                'shift_name',
            ]);
        });
    }
};