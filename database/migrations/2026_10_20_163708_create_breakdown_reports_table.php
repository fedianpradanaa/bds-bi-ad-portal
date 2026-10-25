<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel utama untuk menampung baris data breakdown
        Schema::create('breakdown_reports', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('unique_id');
            $table->dateTime('event_start')->nullable();
            $table->string('event_unit')->nullable();
            $table->string('event_number')->nullable();
            $table->dateTime('event_stop')->nullable();
            $table->string('duration')->nullable();
            $table->text('eng_description')->nullable();
            $table->text('ind_description')->nullable();
            $table->string('source_file')->index();
            $table->timestamps();

            // Mencegah duplikasi baris berdasarkan file sumber dan unique_id event
            $table->unique(['source_file', 'unique_id']);
        });

        // 2. Tabel pelacakan file (jika belum ada dari modul lain)
        if (!Schema::hasTable('bds_processed_files')) {
            Schema::create('bds_processed_files', function (Blueprint $table) {
                $table->id();
                $table->string('report_type')->index(); // contoh: 'breakdown', 'briquetting'
                $table->string('file_path');
                $table->string('file_hash')->nullable();
                $table->integer('rows_count')->default(0);
                $table->timestamp('file_mtime')->nullable();
                $table->timestamps();

                $table->unique(['report_type', 'file_path']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('breakdown_reports');
        // Catatan: bds_processed_files bisa dibiarkan atau di-drop jika khusus
    }
};