<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\Policy;
use Illuminate\Database\Seeder;

class PolicySeeder extends Seeder
{
    public function run(): void
    {
        $leaveType = DocumentType::where('slug', 'leave_request')->first();

        // 1. Annual Leave Quota Policy
        Policy::create([
            'name' => 'Annual Leave Quota Limit',
            'policy_type' => 'quota_limit',
            'document_type_id' => $leaveType->id,
            'department_id' => null,
            'role_id' => null,
            'rules' => [
                'quota_type' => 'annual_leave',
                'max_days_per_year' => 12,
                'max_days_per_request' => 14,
                'min_notice_days' => 3,
            ],
            'is_active' => true,
            'priority' => 10,
        ]);

        // 2. Reimbursement Amount Threshold
        $reimbType = DocumentType::where('slug', 'reimbursement')->first();
        Policy::create([
            'name' => 'Reimbursement Threshold',
            'policy_type' => 'amount_threshold',
            'document_type_id' => $reimbType->id,
            'department_id' => null,
            'role_id' => null,
            'rules' => [
                'field' => 'amount',
                'threshold' => 5000000,
                'action' => 'require_director_approval',
            ],
            'is_active' => true,
            'priority' => 5,
        ]);

        // 3. Leave Notice Period
        Policy::create([
            'name' => 'Leave Notice Period',
            'policy_type' => 'time_based',
            'document_type_id' => $leaveType->id,
            'department_id' => null,
            'role_id' => null,
            'rules' => [
                'min_notice_days' => 3,
            ],
            'is_active' => true,
            'priority' => 8,
        ]);
    }
}
