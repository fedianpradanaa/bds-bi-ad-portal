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
        Schema::table('user_creation_requests', function (Blueprint $table) {
            $table->text('initial_password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_creation_requests', function (Blueprint $table) {
            $table->string('initial_password', 255)->nullable(false)->change();
        });
    }
};
