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
        Schema::table('bds_batches', function (Blueprint $table) {
            $table->string('supervisor_name', 150)->nullable()->after('operator_name');
            $table->string('supervisor_nip', 100)->nullable()->after('supervisor_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bds_batches', function (Blueprint $table) {
            $table->dropColumn(['supervisor_name', 'supervisor_nip']);
        });
    }
};
