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
            $table->string('team_leader_name', 150)->nullable()->after('supervisor_nip');
            $table->string('team_leader_nip', 100)->nullable()->after('team_leader_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bds_batches', function (Blueprint $table) {
            $table->dropColumn(['team_leader_name', 'team_leader_nip']);
        });
    }
};
