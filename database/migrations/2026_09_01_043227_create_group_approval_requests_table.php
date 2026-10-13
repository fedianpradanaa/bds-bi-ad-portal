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
        Schema::create('group_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requester_name');
            $table->string('target_username', 100);
            $table->text('target_user_dn')->nullable();
            $table->string('target_group_name', 100);
            $table->text('target_group_dn')->nullable();
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
        Schema::dropIfExists('group_approval_requests');
    }
};
