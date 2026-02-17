<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all admin panel permissions
        $permissions = [
            // User Management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            
            // Role Management
            'view_roles',
            'create_roles',
            'edit_roles',
            'delete_roles',
            
            // Permission Management
            'view_permissions',
            'create_permissions',
            'edit_permissions',
            'delete_permissions',
            
            // Plan Management
            'view_plans',
            'create_plans',
            'edit_plans',
            'delete_plans',
            
            // Mobile User Management
            'view_mobile_users',
            'activate_mobile_users',
            'deactivate_mobile_users',
            
            // Subscription Management
            'view_subscriptions',
            
            // Business Context (Read-only)
            'view_businesses',
            
            // Activity Logs
            'view_activity_logs',
            
            // Settings
            'view_settings',
            'edit_settings',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create Super Admin role with all permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(Permission::all());

        // Create Admin role with limited permissions
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions([
            'view_users',
            'view_roles',
            'view_permissions',
            'view_plans',
            'create_plans',
            'edit_plans',
            'view_mobile_users',
            'activate_mobile_users',
            'deactivate_mobile_users',
            'view_subscriptions',
            'view_businesses',
            'view_activity_logs',
            'view_settings',
        ]);

        // Create Manager role (limited access)
        $managerRole = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $managerRole->syncPermissions([
            'view_plans',
            'view_mobile_users',
            'view_subscriptions',
            'view_businesses',
            'view_activity_logs',
        ]);

        // Assign Super Admin role to first admin user
        $adminUser = User::where('user_type', 'client')->first();
        if ($adminUser) {
            $adminUser->syncRoles(['Super Admin']);
        }

        $this->command->info('Permissions and roles seeded successfully!');
    }
}
