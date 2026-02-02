<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['name' => 'Information Technology', 'code' => 'IT'],
            ['name' => 'Finance', 'code' => 'FIN'],
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Operations', 'code' => 'OPS'],
            ['name' => 'Marketing', 'code' => 'MKT'],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }
    }
}
