<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Event Record 1 (ER1)
        Schema::create('event_record_1', function (Blueprint $table) {
            $table->id();
            $table->dateTime('timestamp')->nullable();
            $table->string('position')->nullable();
            $table->string('transport_id')->nullable();
            $table->string('source_location')->nullable();
            $table->string('target_location')->nullable();
            $table->string('transport_mode')->nullable();
            $table->string('category')->nullable();
            $table->string('denomination')->nullable();
            $table->string('emission')->nullable();
            $table->string('item_number')->nullable();
            $table->string('quantity')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('tray_id')->nullable();
            $table->string('pallet_id')->nullable();
            $table->string('weight')->nullable();
            $table->string('source_client')->nullable();
            $table->timestamps();
        });

        // 2. Event Record 2 (ER2) - Struktur mirip ER1
        Schema::create('event_record_2', function (Blueprint $table) {
            $table->id();
            $table->dateTime('timestamp')->nullable();
            $table->string('position')->nullable();
            $table->string('transport_id')->nullable();
            $table->string('source_location')->nullable();
            $table->string('target_location')->nullable();
            $table->string('transport_mode')->nullable();
            $table->string('category')->nullable();
            $table->string('denomination')->nullable();
            $table->string('emission')->nullable();
            $table->string('item_number')->nullable();
            $table->string('quantity')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('tray_id')->nullable();
            $table->string('pallet_id')->nullable();
            $table->string('weight')->nullable();
            $table->string('source_client')->nullable();
            $table->timestamps();
        });

        // 3. Event Record 3 (ER3) - Sesuai header ER3 (aman jika quantity kosong)
        Schema::create('event_record_3', function (Blueprint $table) {
            $table->id();
            $table->dateTime('timestamp')->nullable();
            $table->string('sender')->nullable();
            $table->string('receiver')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('master_hu_id')->nullable();
            $table->string('master_hu_type')->nullable();
            $table->string('hu_id')->nullable();
            $table->string('hu_type')->nullable();
            $table->string('transport_mode')->nullable();
            $table->string('weight')->nullable();
            $table->string('item_number')->nullable();
            $table->string('quantity')->nullable(); // Dibuat nullable agar tidak error jika tidak ada di file
            $table->timestamps();
        });

        // 4. Event Record 4 (ER4)
        Schema::create('event_record_4', function (Blueprint $table) {
            $table->id();
            $table->dateTime('timestamp')->nullable();
            $table->string('shredder')->nullable();
            $table->string('idle_state')->nullable();
            $table->string('time')->nullable();
            $table->string('shredder_event')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_record_1');
        Schema::dropIfExists('event_record_2');
        Schema::dropIfExists('event_record_3');
        Schema::dropIfExists('event_record_4');
    }
};
