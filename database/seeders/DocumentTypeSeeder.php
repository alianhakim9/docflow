<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DocumentType::create([
            'name' => 'Leave Request',
            'slug' => 'leave_request',
            'description' => 'Employee leave request form',
            'form_schema' => [
                'fields' => [
                    [
                        'name' => 'leave_type',
                        'type' => 'select',
                        'label' => 'Leave Type',
                        'required' => true,
                        'options' => [
                            ['value' => 'annual', 'label' => 'Annual Leave'],
                            ['value' => 'sick', 'label' => 'Sick Leave'],
                            ['value' => 'emergency', 'label' => 'Emergency Leave']
                        ]
                    ],
                    [
                        'name' => 'start_date',
                        'type' => 'date',
                        'label' => 'Start Date',
                        'required' => true,
                    ],
                    [
                        'name' => 'end_date',
                        'type' => 'date',
                        'label' => 'End Date',
                        'required' => true,
                    ],
                    [
                        'name' => 'days',
                        'type' => 'number',
                        'label' => 'Number of Days',
                        'required' => true,
                        'min' => 1,
                        'max' => 14,
                    ],
                    [
                        'name' => 'reason',
                        'type' => 'textarea',
                        'label' => 'Reason',
                        'required' => true,
                    ],
                ]
            ],
            'requires_attachment' => false,
            'max_attachments' => 2,
            'is_active' => true,
        ]);

        DocumentType::create([
            'name' => 'Reimbursement Claim',
            'slug' => 'reimbursement',
            'description' => 'Employee expense reimbursement claim',
            'form_schema' => [
                'fields' => [
                    [
                        'name' => 'expense_type',
                        'type' => 'select',
                        'label' => 'Expense Type',
                        'required' => true,
                        'options' => [
                            ['value' => 'travel', 'label' => 'Travel'],
                            ['value' => 'meal', 'label' => 'Meal'],
                            ['value' => 'accommodation', 'label' => 'Accommodation'],
                            ['value' => 'other', 'label' => 'Other'],
                        ],
                    ],
                    [
                        'name' => 'amount',
                        'type' => 'number',
                        'label' => 'Amount (IDR)',
                        'required' => true,
                        'min' => 0,
                    ],
                    [
                        'name' => 'expense_date',
                        'type' => 'date',
                        'label' => 'Expense Date',
                        'required' => true,
                        'min' => 0,
                    ],
                    [
                        'name' => 'description',
                        'type' => 'textarea',
                        'label' => 'Description',
                        'required' => true,
                    ],
                ]
            ],
            'requires_attachment' => true,
            'max_attachments' => 5,
            'is_active' => true
        ]);

        DocumentType::create([
            'name' => 'Purchase Request',
            'slug' => 'purchase_request',
            'description' => 'Purchase request for goods or services',
            'form_schema' => [
                'fields' => [
                    [
                        'name' => 'item_name',
                        'type' => 'text',
                        'label' => 'Item Name',
                        'required' => true,
                    ],
                    [
                        'name' => 'quantity',
                        'type' => 'number',
                        'label' => 'Quantity',
                        'required' => true,
                        'min' => 1,
                    ],
                    [
                        'name' => 'estimated_amount',
                        'type' => 'number',
                        'label' => 'Estimated Amount (IDR)',
                        'required' => true,
                        'min' => 1,
                    ],
                    [
                        'name' => 'vendor',
                        'type' => 'text',
                        'label' => 'Preferred Vendor',
                        'required' => false,
                    ],
                    [
                        'name' => 'justification',
                        'type' => 'textarea',
                        'label' => 'Justification',
                        'required' => true,
                    ],
                ]
            ],
            'requires_attachment' => false,
            'max_attachments' => 3,
            'is_active' => true,
        ]);
    }
}
