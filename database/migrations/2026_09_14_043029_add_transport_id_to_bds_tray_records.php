<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('bds_tray_records') && ! Schema::hasColumn('bds_tray_records', 'transport_id')) {
            Schema::table('bds_tray_records', function (Blueprint $table) {
                $table->string('transport_id', 50)->nullable()->after('pallet_id');
            });
        }

        // Backfill transport_id from bds_transfer_records if available
        if (Schema::hasTable('bds_transfer_records') && Schema::hasColumn('bds_transfer_records', 'transport_id')) {
            $transfers = DB::table('bds_transfer_records')
                ->whereNotNull('transport_id')
                ->where('transport_id', '!=', '')
                ->select('pallet_id', 'transport_id')
                ->distinct()
                ->get();

            foreach ($transfers as $tr) {
                DB::table('bds_tray_records')
                    ->where('pallet_id', $tr->pallet_id)
                    ->whereNull('transport_id')
                    ->update(['transport_id' => $tr->transport_id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bds_tray_records') && Schema::hasColumn('bds_tray_records', 'transport_id')) {
            Schema::table('bds_tray_records', function (Blueprint $table) {
                $table->dropColumn('transport_id');
            });
        }
    }
};
