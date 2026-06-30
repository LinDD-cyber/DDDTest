<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domains\User\Models\User;
use App\Domains\User\Models\FrontIdentity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Front Identities
        $member = FrontIdentity::firstOrCreate(
            ['code' => 'member'],
            [
                'name' => '一般會員',
                'description' => '一般會員身分',
                'is_system' => true,
                'is_enabled' => true,
                'sort' => 1
            ]
        );

        $coach = FrontIdentity::firstOrCreate(
            ['code' => 'coach'],
            [
                'name' => '教練',
                'description' => '教練身分',
                'is_system' => true,
                'is_enabled' => true,
                'sort' => 2
            ]
        );

        // 2. Create Spatie Permissions & Roles (letting guard_name default automatically)
        $manageBackendUser = Permission::firstOrCreate(['name' => 'backend_user.manage']);

        $systemAdmin = Role::firstOrCreate(['name' => 'System Admin']);
        $venueManager = Role::firstOrCreate(['name' => 'Venue Manager']);
        $venueStaff = Role::firstOrCreate(['name' => 'Venue Staff']);

        // Give permissions to roles
        $venueManager->givePermissionTo($manageBackendUser);
        $systemAdmin->givePermissionTo($manageBackendUser);

        // 3. Create a Default System Admin User
        $admin = User::firstOrCreate(
            ['account' => 'admin'],
            [
                'uuid' => (string) Str::uuid(),
                'email' => 'admin@example.com',
                'phone' => '0900000000',
                'password' => 'password', // will be casted to hashed by casts()
                'status' => 1,
            ]
        );

        if (!$admin->hasRole('System Admin')) {
            $admin->assignRole('System Admin');
        }

        // Create profile for admin
        $admin->profile()->firstOrCreate(
            ['user_id' => $admin->id],
            [
                'name' => '系統管理員',
                'nickname' => 'Admin',
                'gender' => 1,
            ]
        );
    }
}
