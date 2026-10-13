<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\GroupApprovalRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $supervisorRole = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);

        // 2. Define and assign permissions for Admin (Creates and views requests, views audit logs)
        $adminPermissions = [
            'ViewAny:GroupApprovalRequest',
            'View:GroupApprovalRequest',
            'Create:GroupApprovalRequest',
            'ViewAny:UserCreationRequest',
            'View:UserCreationRequest',
            'Create:UserCreationRequest',
            'ViewAny:AuditLog',
            'View:AuditLog',
        ];

        foreach ($adminPermissions as $permName) {
            $perm = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            $adminRole->givePermissionTo($perm);
        }

        // 3. Define and assign permissions for Supervisor (Views, Approves, and Rejects assigned requests, views audit logs)
        $supervisorPermissions = [
            'ViewAny:GroupApprovalRequest',
            'View:GroupApprovalRequest',
            'Update:GroupApprovalRequest',
            'ViewAny:UserCreationRequest',
            'View:UserCreationRequest',
            'Update:UserCreationRequest',
            'ViewAny:AuditLog',
            'View:AuditLog',
        ];

        foreach ($supervisorPermissions as $permName) {
            $perm = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            $supervisorRole->givePermissionTo($perm);
        }

        // 4. Create Portal Admin (Creates requests only, CANNOT approve/reject)
        $admin = User::firstOrCreate(
            ['email' => 'admin@cmc.mhe.local'],
            [
                'name' => 'System Administrator',
                'username' => 'admin',
                'password' => Hash::make(env('PORTAL_ADMIN_PASSWORD', env('ADMIN_PASSWORD', 'BDS@Admin#'.date('Y')))),
                'is_admin' => true,
                'role' => 'admin',
            ]
        );
        $admin->syncRoles([$adminRole]);

        // 5. Create Supervisor (Authorizes or rejects requests only)
        $supervisor = User::firstOrCreate(
            ['email' => 'supervisor@cmc.mhe.local'],
            [
                'name' => 'Supervisor BDS',
                'username' => 'supervisor',
                'password' => Hash::make(env('PORTAL_SUPERVISOR_PASSWORD', env('SUPERVISOR_PASSWORD', 'BDS@Supervisor#'.date('Y')))),
                'is_admin' => false,
                'role' => 'supervisor',
            ]
        );
        $supervisor->syncRoles([$supervisorRole]);

        // 6. Create Super Administrator (Full governance, User Management & Roles/Permissions)
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
        $superadmin->syncRoles([$superAdminRole]);
        $superAdminRole->syncPermissions(Permission::all());

        // 7. Create Default Operator BDS
        $operatorRole = Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
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

        // 7. Create Initial Sample Request (Created by Admin for target AD user 'BDS Niklas')
        $request = GroupApprovalRequest::firstOrCreate(
            ['target_username' => 'BDS Niklas', 'target_group_name' => 'BDS_Technician'],
            [
                'requester_id' => $admin->id,
                'requester_name' => $admin->name,
                'target_user_dn' => 'CN=BDS Niklas,OU=Users,OU=Tier3-Test-UAT,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local',
                'target_group_dn' => 'CN=BDS_Technician,OU=Groups,OU=CMC.MHE,DC=cmc,DC=mhe,DC=local',
                'business_justification' => 'Field maintenance work order for BDS scanner conveyor system.',
                'approver_email' => 'supervisor@cmc.mhe.local',
                'status' => 'PENDING',
            ]
        );

        AuditLog::logAction(
            action: 'REQUEST_CREATED',
            actor: $admin->email,
            requestId: $request->id,
            details: "Sample request submitted by Admin for target AD account {$request->target_username} to {$request->target_group_name}."
        );

        // 8. Seed BDS Master Data & Sample Reports
        $this->call([
            BdsMasterDataSeeder::class,
            BdsSampleReportSeeder::class,
        ]);
    }
}
