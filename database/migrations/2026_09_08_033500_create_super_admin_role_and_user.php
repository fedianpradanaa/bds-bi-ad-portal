<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure super_admin role exists in Spatie permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        // 2. Grant all current permissions to super_admin
        $permissions = Permission::all();
        if ($permissions->isNotEmpty()) {
            $superAdminRole->syncPermissions($permissions);
        }

        // 3. Create or promote default Super Administrator
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@cmc.mhe.local'],
            [
                'name' => 'Super Administrator',
                'username' => 'superadmin',
                'password' => Hash::make(env('PORTAL_SUPERADMIN_PASSWORD', 'password')),
                'is_admin' => true,
                'role' => 'superadmin',
            ]
        );

        $superadmin->update([
            'is_admin' => true,
            'role' => 'superadmin',
        ]);

        $superadmin->syncRoles([$superAdminRole]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive downgrade needed
    }
};
