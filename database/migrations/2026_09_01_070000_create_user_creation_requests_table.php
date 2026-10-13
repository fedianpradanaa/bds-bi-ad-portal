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
        Schema::create('user_creation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requester_name');
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('full_name', 150);
            $table->string('display_name', 150); // e.g. BDS Ahmad Syahputra
            $table->string('username', 100);     // e.g. BDS-Syahputra
            $table->string('user_principal_name', 150); // e.g. BDS-Syahputra@cmc.mhe.local
            $table->string('initial_password', 255);
            $table->string('initial_group_name', 100)->default('BDS_Technician');
            $table->text('business_justification');
            $table->string('approver_email', 255)->nullable();
            $table->string('approval_token', 64)->unique();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'EXPIRED'])->default('PENDING');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->string('actioned_by_ip', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_creation_requests');
    }
};
