<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bds_processed_files', function (Blueprint $table) {
            $table->string('report_type')->default('bds')->after('id');
            $table->timestamp('file_mtime')->nullable()->after('rows_count');
        });
    }

    public function down(): void
    {
        Schema::table('bds_processed_files', function (Blueprint $table) {
            $table->dropColumn(['report_type', 'file_mtime']);
        });
    }
};
