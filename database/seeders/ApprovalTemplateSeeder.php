<?php

namespace Database\Seeders;

use App\Models\ApprovalTemplate;
use App\Models\ApprovalTemplateStep;
use App\Models\DocumentType;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApprovalTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $managerRole = Role::where('slug', 'manager')->first();
        $financeRole = Role::where('slug', 'finance')->first();

        $leaveType = DocumentType::where('slug', 'leave_request')->first();
        $leaveTemplate = ApprovalTemplate::create([
            'document_type_id' => $leaveType->id,
            'name' => 'Standard Leave Approval',
            'description' => 'Two-step approval: Manager -> Department Head',
            'condition_rules' => null,
            'is_default' => true,
            'is_active' => true,
        ]);

        ApprovalTemplateStep::create([
            'approval_template_id' => $leaveTemplate->id,
            'sequence' => 1,
            'step_name' => 'Manager Approval',
            'approver_type' => 'dynamic',
            'approver_role_id' => null,
            'approver_user_id' => null,
            'is_parallel' => false,
            'sla_hours' => 24,
        ]);

        ApprovalTemplateStep::create([
            'approval_template_id' => $leaveTemplate->id,
            'sequence' => 2,
            'step_name' => 'Department Head Approval',
            'approver_type' => 'role',
            'approver_role_id' => $managerRole->id,
            'approver_user_id' => null,
            'is_parallel' => false,
            'sla_hours' => 24
        ]);

        $reimbType = DocumentType::where('slug', 'reimbursement')->first();
        $reimbTemplate = ApprovalTemplate::create([
            'document_type_id' => $reimbType->id,
            'name' => 'Standard Reimbursement Approval',
            'description' => 'Finance verification -> Manager approval',
            'condition_rules' => [
                'fields' => 'amount',
                'operator' => '<=',
                'value' => 5000000
            ],
            'is_default' => true,
            'is_active' => true
        ]);

        ApprovalTemplateStep::create([
            'approval_template_id' => $reimbTemplate->id,
            'sequence' => 1,
            'step_name' => 'Finance Verification',
            'approver_type' => 'role',
            'approver_role_id' => $financeRole->id,
            'approver_user_id' => null,
            'is_parallel' => false,
            'sla_hours' => 48
        ]);

        ApprovalTemplateStep::create([
            'approval_template_id' => $reimbTemplate->id,
            'sequence' => 2,
            'step_name' => 'Manager Approval',
            'approver_type' => 'dynamic',
            'approver_role_id' => null,
            'approver_user_id' => null,
            'is_parallel' => false,
            'sla_hours' => 24
        ]);

        $reimbHighTemplate = ApprovalTemplate::create([
            'document_type_id' => $reimbType->id,
            'name' => 'High Amount Reimbursement',
            'description' => 'For reimbursement > 5 million (requires director approval)',
            'condition_rules' => [
                'field' => 'amount',
                'operator' => '>',
                'value' => 5000000
            ],
            'is_default' => false,
            'is_active' => true
        ]);

        ApprovalTemplateStep::create([
            'approval_template_id' => $reimbHighTemplate->id,
            'sequence' => 1,
            'step_name' => 'Finance Verification',
            'approver_type' => 'role',
            'approver_role_id' => $financeRole->id,
            'approver_user_id' => null,
            'is_parallel' => false,
            'sla_hours' => 48
        ]);

        ApprovalTemplateStep::create([
            'approval_template_id' => $reimbHighTemplate->id,
            'sequence' => 2,
            'step_name' => 'Manager Approval',
            'approver_type' => 'dynamic',
            'approver_role_id' => null,
            'approver_user_id' => null,
            'is_parallel' => false,
            'sla_hours' => 24
        ]);

        $prType = DocumentType::where('slug', 'purchase_request')->first();
        $prTemplate = ApprovalTemplate::create([
            'document_type_id' => $prType->id,
            'name' => 'Standard Purchase Approval',
            'description' => 'Manager -> Finance approval',
            'condition_rules' => null,
            'is_default' => true,
            'is_active' => true
        ]);

        ApprovalTemplateStep::create([
            'approval_template_id' => $prTemplate->id,
            'sequence' => 1,
            'step_name' => 'Manager Approval',
            'approver_type' => 'dynamic',
            'approver_role_id' => null,
            'approver_user_id' => null,
            'is_parallel' => false,
            'sla_hours' => 24
        ]);

        ApprovalTemplateStep::create([
            'approval_template_id' => $prTemplate->id,
            'sequence' => 2,
            'step_name' => 'Finance Approval',
            'approver_type' => 'role',
            'approver_role_id' => $financeRole->id,
            'approver_user_id' => null,
            'is_parallel' => false,
            'sla_hours' => 48
        ]);
    }
}
