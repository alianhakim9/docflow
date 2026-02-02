<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
                'name' => 'Staff',
                'slug' => 'staff',
                'permissions' => [
                    'document.create',
                    'document.view_own',
                    'document.update_own',
                    'document.cancel_own',
                ]
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'permissions' => [
                    'document.create',
                    'document.view_own',
                    'document.view_team',
                    'document.update_own',
                    'document.cancel_own',
                    'approval.approve',
                    'approval.reject',
                    'approval.return',
                    'approval.delegate'
                ]
            ],
            [
                'name' => 'Finance',
                'slug' => 'finance',
                'permissions' => [
                    'document.view_financial',
                    'approval.approve',
                    'approval.reject'
                ]
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'permissions' => [
                    'document.view_all',
                    'config.document_types',
                    'config.approval_templates',
                    'config.policies',
                    'user.manage',
                ]
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
