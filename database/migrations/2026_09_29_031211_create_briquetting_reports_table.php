<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('briquetting_reports', function (Blueprint $table) {
            $table->id();
            // Data Header / Requested Time Frame
            $table->string('report_printed_time')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->string('system_id')->nullable();
            $table->text('user_id')->nullable();
            $table->string('user_id_offline_shredding')->nullable();
            $table->dateTime('start_of_day_or_shift')->nullable();
            $table->dateTime('end_of_day_or_shift')->nullable();
            
            // Data Presses (Detail per Press)
            $table->string('press_id'); 
            $table->string('duration')->nullable();
            $table->decimal('throughput_kg_per_h', 10, 2)->default(0);
            $table->integer('briquettes_count')->default(0);
            $table->integer('weight_per_briquette_g')->default(0);
            $table->decimal('briquette_weight_kg', 10, 2)->default(0);
            
            // Log / Metadata file
            $table->string('source_file')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('briquetting_reports');
    }
};