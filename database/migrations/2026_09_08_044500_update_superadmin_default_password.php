<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $superadmin = User::where('email', 'superadmin@cmc.mhe.local')
            ->orWhere('username', 'superadmin')
            ->first();

        if ($superadmin) {
            $superadmin->update([
                'name' => 'Super Administrator',
                'username' => 'superadmin',
                'email' => 'superadmin@cmc.mhe.local',
                'password' => Hash::make(env('PORTAL_SUPERADMIN_PASSWORD', 'password')),
                'is_admin' => true,
                'role' => 'superadmin',
            ]);
            $superadmin->syncRoles([$superAdminRole]);
        } else {
            $superadmin = User::create([
                'name' => 'Super Administrator',
                'username' => 'superadmin',
                'email' => 'superadmin@cmc.mhe.local',
                'password' => Hash::make(env('PORTAL_SUPERADMIN_PASSWORD', 'password')),
                'is_admin' => true,
                'role' => 'superadmin',
            ]);
            $superadmin->syncRoles([$superAdminRole]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed
    }
};
