<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'permissions' => [
                    'manage_users' => true,
                    'manage_posts' => true,
                    'manage_categories' => true,
                    'manage_roles' => true,
                ],
            ],
            [
                'name' => 'moderator',
                'permissions' => [
                    'manage_users' => false,
                    'manage_posts' => true,
                    'manage_categories' => false,
                    'manage_roles' => false,
                ],
            ],
            [
                'name' => 'user',
                'permissions' => [
                    'manage_users' => false,
                    'manage_posts' => false,
                    'manage_categories' => false,
                    'manage_roles' => false,
                ],
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
