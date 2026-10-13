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
        if (app()->runningUnitTests()) {
            return;
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $supervisorRole = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $operatorRole = Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);

        $superadmin = User::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Super Administrator',
                'email' => 'superadmin@cmc.mhe.local',
                'password' => Hash::make(env('PORTAL_SUPERADMIN_PASSWORD', 'password')),
                'is_admin' => true,
                'role' => 'superadmin',
            ]
        );
        $superadmin->syncRoles([$superAdminRole]);

        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@cmc.mhe.local',
                'password' => Hash::make(env('PORTAL_ADMIN_PASSWORD', env('ADMIN_PASSWORD', 'BDS@Admin#'.date('Y')))),
                'is_admin' => true,
                'role' => 'admin',
            ]
        );
        $admin->syncRoles([$adminRole]);

        $supervisor = User::firstOrCreate(
            ['username' => 'supervisor'],
            [
                'name' => 'Supervisor BDS',
                'email' => 'supervisor@cmc.mhe.local',
                'password' => Hash::make(env('PORTAL_SUPERVISOR_PASSWORD', env('SUPERVISOR_PASSWORD', 'BDS@Supervisor#'.date('Y')))),
                'is_admin' => false,
                'role' => 'supervisor',
            ]
        );
        $supervisor->syncRoles([$supervisorRole]);

        $operator = User::firstOrCreate(
            ['username' => 'operator'],
            [
                'name' => 'Operator BDS',
                'email' => 'operator@cmc.mhe.local',
                'password' => Hash::make(env('PORTAL_OPERATOR_PASSWORD', 'password')),
                'is_admin' => false,
                'is_team_leader' => false,
                'role' => 'operator',
            ]
        );
        $operator->syncRoles([$operatorRole]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
