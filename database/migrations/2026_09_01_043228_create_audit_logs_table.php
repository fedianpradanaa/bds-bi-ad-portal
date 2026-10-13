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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_approval_request_id')->nullable()->constrained('group_approval_requests')->nullOnDelete();
            $table->foreignId('user_creation_request_id')->nullable();
            $table->string('action', 50); // REQUEST_CREATED, APPROVED, REJECTED, LDAP_SUCCESS, LDAP_FAILED
            $table->string('actor', 255);
            $table->string('ip_address', 45)->nullable();
            $table->longText('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
