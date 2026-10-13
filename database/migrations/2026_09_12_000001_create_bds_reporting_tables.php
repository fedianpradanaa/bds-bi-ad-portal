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
        // 1. Log Sinkronisasi Sumber Data
        Schema::create('bds_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 50)->default('LOCAL_PATH');
            $table->integer('files_processed')->default(0);
            $table->integer('rows_inserted')->default(0);
            $table->string('status', 50)->default('SUCCESS');
            $table->text('message')->nullable();
            $table->string('triggered_by', 100)->default('SCHEDULER');
            $table->timestamps();
        });

        // 2. Master Pengawas Resmi BDS (Untuk Dropdown Lembar Sertifikat)
        Schema::create('bds_supervisors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('nip', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Master Denominasi & Emisi
        Schema::create('bds_denominations', function (Blueprint $table) {
            $table->string('code', 10)->primary();
            $table->decimal('value', 14, 2);
            $table->string('currency_type', 20)->default('BANKNOTE');
            $table->timestamps();
        });

        Schema::create('bds_emissions', function (Blueprint $table) {
            $table->string('code', 10)->primary();
            $table->integer('year');
            $table->timestamps();
        });

        // 4. Data Batch Utama (Agregasi Seluruh Unit)
        Schema::create('bds_batches', function (Blueprint $table) {
            $table->string('batch_id', 50)->primary();
            $table->date('operational_date')->index();
            $table->string('transport_mode', 50)->default('AUTOMATIC');
            $table->string('operator_name', 100)->default('CCS_BDS');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('duration_seconds')->default(0);
            $table->integer('total_bags')->default(0);
            $table->decimal('total_weight_kg', 12, 2)->default(0.00);
            $table->decimal('throughput_ktg_j', 10, 2)->default(0.00);
            $table->integer('idle_duration_seconds')->default(0);
            $table->timestamps();
        });

        // 5. Detail Pergerakan Unit Transfer (ER1)
        Schema::create('bds_transfer_records', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 50)->index();
            $table->string('pallet_id', 50)->index();
            $table->string('transport_id', 50)->nullable();
            $table->string('item_code', 50);
            $table->string('transport_mode', 50)->default('AUTOMATIC');
            $table->integer('quantity')->default(1);
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('duration_seconds')->default(0);
            $table->decimal('throughput', 10, 2)->default(0.00);
            $table->decimal('weight_kg', 10, 2)->default(0.00);
            $table->string('source_client', 50)->nullable();
            $table->timestamps();

            $table->foreign('batch_id')->references('batch_id')->on('bds_batches')->onDelete('cascade');
        });

        // 6. Detail Unit Depalletisasi (ER2) & Pengumpan (ER3)
        Schema::create('bds_tray_records', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 50)->index();
            $table->string('pallet_id', 50)->index();
            $table->string('tray_id', 50)->index();
            $table->string('item_code', 50);
            $table->string('transport_mode', 50)->default('AUTOMATIC');
            $table->dateTime('depalletize_time')->nullable();
            $table->dateTime('feeder_start_time');
            $table->dateTime('feeder_end_time');
            $table->integer('duration_seconds')->default(0);
            $table->decimal('throughput', 10, 2)->default(0.00);
            $table->decimal('net_weight_gm', 12, 2)->default(0.00);
            $table->decimal('weight_kg', 10, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('batch_id')->references('batch_id')->on('bds_batches')->onDelete('cascade');
        });

        // 7. Log Event Shredder (ER4)
        Schema::create('bds_shredder_events', function (Blueprint $table) {
            $table->id();
            $table->dateTime('event_time')->index();
            $table->string('shredder_id', 50)->default('ONLINE');
            $table->string('idle_state', 50);
            $table->string('shredder_event', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bds_shredder_events');
        Schema::dropIfExists('bds_tray_records');
        Schema::dropIfExists('bds_transfer_records');
        Schema::dropIfExists('bds_batches');
        Schema::dropIfExists('bds_emissions');
        Schema::dropIfExists('bds_denominations');
        Schema::dropIfExists('bds_supervisors');
        Schema::dropIfExists('bds_sync_logs');
    }
};
