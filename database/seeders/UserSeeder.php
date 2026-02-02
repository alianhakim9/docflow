<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            'IT' => [
                'Manager' => 'Sarah Johnson',
                'Staff' => ['John Doe', 'Jane Smith', 'Mike Chen', 'Lisa Wong']
            ],
            'FIN' => [
                'Manager' => 'Robert Lee',
                'Staff' => ['Emily Chen', 'David Kim', 'Maria Garcia', 'Ahmed Hassan']
            ],
            'HR' => [
                'Manager' => 'Jennifer Brown',
                'Staff' => ['Tom Wilson', 'Anna Lee', 'Carlos Rodriguez', 'Fatima Ali']
            ],
            'OPS' => [
                'Manager' => 'Michael Chang',
                'Staff' => ['Sophie Martin', 'Kevin Tan', 'Rachel Green', 'Ali Rahman']
            ],
            'MKT' => [
                'Manager' => 'Amanda White',
                'Staff' => ['Peter Jones', 'Nina Patel', 'James Lee', 'Sara Ibrahim']
            ],
        ];

        $staffRole = Role::where('slug', 'staff')->first();
        $managerRole = Role::where('slug', 'manager')->first();
        $financeRole = Role::where('slug', 'finance')->first();
        $adminRole = Role::where('slug', 'admin')->first();

        $employeeCounter = 1000;

        foreach ($departments as $deptCode => $roles) {
            $dept = Department::where('code', $deptCode)->first();

            $manager = User::create([
                'name' => $roles['Manager'],
                'email' => Str::slug($roles['Manager']) . '@techindo.com',
                'password' => bcrypt('password'),
                'employee_id' => 'EMP' . str_pad($employeeCounter++, 4, '0', STR_PAD_LEFT),
                'department_id' => $dept->id,
                'role_id' => $managerRole->id,
                'reports_to' => null,
                'is_active' => true,
            ]);

            // create staff
            foreach ($roles['Staff'] as $staffName) {
                User::create([
                    'name' => $staffName,
                    'email' => Str::slug($staffName) . '@techindo.com',

                    'password' => bcrypt('password'),
                    'employee_id' => 'EMP' . str_pad($employeeCounter++, 4, '0', STR_PAD_LEFT),
                    'department_id' => $dept->id,
                    'role_id' => $staffRole->id,
                    'reports_to' => $manager->id,
                    'is_active' => true,
                ]);
            }

            // create finance user (special role)
            $financeDept = Department::where('code', 'FIN')->first();
            User::firstOrCreate(
                ['email' => 'finance@techindo.com'],
                [
                    'name' => 'Finance Officer',
                    'password' => bcrypt('password'),
                    'employee_id' => 'EMP' . str_pad($employeeCounter++, 4, '0', STR_PAD_LEFT),
                    'department_id' => $financeDept->id,
                    'role_id' => $financeRole->id,
                    'reports_to' => null,
                    'is_active' => true,
                ]
            );

            // create admin
            $itDept = Department::where('code', 'IT')->first();
            User::firstOrCreate(
                ['email' => 'admin@techindo.com'],
                [
                    'name' => 'System Admin',
                    'password' => bcrypt('password'),
                    'employee_id' => 'EMP0001',
                    'department_id' => $itDept->id,
                    'role_id' => $adminRole->id,
                    'reports_to' => null,
                    'is_active'  => true
                ]
            );
        }
    }
}
