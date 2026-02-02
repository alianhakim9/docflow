<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use App\Models\ApprovalStep;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    private array $leaveReasons = [
        'Family vacation to Bali',
        'Medical appointment and recovery',
        'Attending family wedding ceremony',
        'Personal rest and recuperation',
        'Visiting family in hometown',
        'Medical treatment follow-up',
        'Extended weekend getaway',
        'Child school event',
        'Home renovation supervision',
        'Religious pilgrimage',
    ];

    private array $reimbReasons = [
        'Client meeting lunch',
        'Transportation to client site',
        'Hotel accommodation for business trip',
        'Conference registration fee',
        'Office supplies purchase',
        'Team building event expenses',
        'Professional training course',
        'Airport transfer and parking',
        'Business lunch with partner',
        'Emergency office equipment repair',
    ];

    private array $prJustifications = [
        'Replace broken office equipment',
        'Upgrade team productivity tools',
        'Support new project requirements',
        'Improve workplace ergonomics',
        'Enhance team collaboration',
        'Replace outdated technology',
        'Comply with safety regulations',
        'Increase operational efficiency',
        'Support client deliverables',
        'Maintain system reliability',
    ];

    public function run(): void
    {
        $staff = User::whereHas('role', fn($q) => $q->where('slug', 'staff'))->get();
        $leaveType = DocumentType::where('slug', 'leave_request')->first();
        $reimbType = DocumentType::where('slug', 'reimbursement')->first();
        $prType = DocumentType::where('slug', 'purchase_request')->first();

        $documentCounter = 1;

        foreach ($staff as $user) {
            // Each user creates 5-7 documents over last 3 months
            $documentCount = rand(5, 7);

            for ($i = 0; $i < $documentCount; $i++) {
                // Randomly distribute across 3 document types
                $typeRandom = rand(1, 10);

                if ($typeRandom <= 5) {
                    // 50% Leave requests
                    $this->createLeaveRequest($user, $leaveType, $documentCounter++);
                } elseif ($typeRandom <= 8) {
                    // 30% Reimbursements
                    $this->createReimbursement($user, $reimbType, $documentCounter++);
                } else {
                    // 20% Purchase requests
                    $this->createPurchaseRequest($user, $prType, $documentCounter++);
                }
            }
        }
    }

    private function createLeaveRequest(User $user, DocumentType $type, int $counter): void
    {
        // Random date in last 90 days
        $submittedAt = Carbon::now()->subDays(rand(1, 90));
        $startDate = $submittedAt->copy()->addDays(rand(1, 14));
        $days = rand(1, 5);
        $endDate = $startDate->copy()->addDays($days - 1);

        $document = Document::create([
            'document_number' => 'LV-' . $submittedAt->format('Y') . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT),
            'document_type_id' => $type->id,
            'submitter_id' => $user->id,
            'title' => $this->generateLeaveTitle(),
            'data' => [
                'leave_type' => $this->randomLeaveType(),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'days' => $days,
                'reason' => $this->leaveReasons[array_rand($this->leaveReasons)],
            ],
            'status' => $this->determineStatus($submittedAt),
            'submitted_at' => $submittedAt,
            'completed_at' => $this->generateCompletedAt($submittedAt),
        ]);

        $this->createApprovalSteps($document, $submittedAt);
        $this->createAuditLog($document, $user);
    }

    private function createReimbursement(User $user, DocumentType $type, int $counter): void
    {
        $submittedAt = Carbon::now()->subDays(rand(1, 90));
        $expenseDate = $submittedAt->copy()->subDays(rand(1, 7));
        $amount = $this->generateReimbursementAmount();

        $document = Document::create([
            'document_number' => 'RB-' . $submittedAt->format('Y') . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT),
            'document_type_id' => $type->id,
            'submitter_id' => $user->id,
            'title' => $this->generateReimbursementTitle($amount),
            'data' => [
                'expense_type' => $this->randomExpenseType(),
                'amount' => $amount,
                'expense_date' => $expenseDate->format('Y-m-d'),
                'description' => $this->reimbReasons[array_rand($this->reimbReasons)],
            ],
            'status' => $this->determineStatus($submittedAt),
            'submitted_at' => $submittedAt,
            'completed_at' => $this->generateCompletedAt($submittedAt),
        ]);

        $this->createApprovalSteps($document, $submittedAt);
        $this->createAuditLog($document, $user);
    }

    private function createPurchaseRequest(User $user, DocumentType $type, int $counter): void
    {
        $submittedAt = Carbon::now()->subDays(rand(1, 90));
        $amount = rand(500000, 15000000);

        $document = Document::create([
            'document_number' => 'PR-' . $submittedAt->format('Y') . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT),
            'document_type_id' => $type->id,
            'submitter_id' => $user->id,
            'title' => $this->generatePurchaseTitle(),
            'data' => [
                'item_name' => $this->generateItemName(),
                'quantity' => rand(1, 10),
                'estimated_amount' => $amount,
                'vendor' => $this->generateVendorName(),
                'justification' => $this->prJustifications[array_rand($this->prJustifications)],
            ],
            'status' => $this->determineStatus($submittedAt),
            'submitted_at' => $submittedAt,
            'completed_at' => $this->generateCompletedAt($submittedAt),
        ]);

        $this->createApprovalSteps($document, $submittedAt);
        $this->createAuditLog($document, $user);
    }

    private function createApprovalSteps(Document $document, Carbon $submittedAt): void
    {
        $manager = $document->submitter->manager;

        if (!$manager) {
            return; // Skip if no manager
        }

        // Step 1: Manager Approval
        $step1Status = $this->determineStepStatus($document->status);
        $step1 = ApprovalStep::create([
            'document_id' => $document->id,
            'template_step_id' => null,
            'sequence' => 1,
            'step_name' => 'Manager Approval',
            'approver_id' => $manager->id,
            'status' => $step1Status,
            'action_taken_at' => $step1Status !== ApprovalStatus::PENDING
                ? $submittedAt->copy()->addHours(rand(2, 8))
                : null,
            'action_taken_by' => $step1Status !== ApprovalStatus::PENDING
                ? $manager->id
                : null,
            'comments' => $step1Status !== ApprovalStatus::PENDING
                ? $this->generateApprovalComment($step1Status)
                : null,
            'sla_hours' => 24,
            'due_at' => $submittedAt->copy()->addHours(24),
        ]);

        // Step 2: Department Head / Finance (if step 1 approved)
        if (in_array($step1Status, [ApprovalStatus::APPROVED, ApprovalStatus::REJECTED])) {
            $step2Approver = $this->getSecondApprover($document, $manager);

            if ($step2Approver) {
                $step2Status = $document->status === DocumentStatus::APPROVED
                    ? ApprovalStatus::APPROVED
                    : ($document->status === DocumentStatus::REJECTED
                        ? ApprovalStatus::REJECTED
                        : ApprovalStatus::PENDING);

                ApprovalStep::create([
                    'document_id' => $document->id,
                    'template_step_id' => null,
                    'sequence' => 2,
                    'step_name' => $this->getSecondStepName($document),
                    'approver_id' => $step2Approver->id,
                    'status' => $step2Status,
                    'action_taken_at' => $step2Status !== ApprovalStatus::PENDING
                        ? $step1->action_taken_at->copy()->addHours(rand(1, 4))
                        : null,
                    'action_taken_by' => $step2Status !== ApprovalStatus::PENDING
                        ? $step2Approver->id
                        : null,
                    'comments' => $step2Status !== ApprovalStatus::PENDING
                        ? $this->generateApprovalComment($step2Status)
                        : null,
                    'sla_hours' => 24,
                    'due_at' => $step1->action_taken_at->copy()->addHours(24),
                ]);
            }
        }
    }

    private function createAuditLog(Document $document, User $user): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'action' => 'document.submitted',
            'entity_type' => 'Document',
            'entity_id' => $document->id,
            'old_values' => null,
            'new_values' => [
                'document_number' => $document->document_number,
                'status' => $document->status->value,
            ],
            'metadata' => [
                'ip' => '127.0.0.1',
                'user_agent' => 'Seeder',
            ],
            'created_at' => $document->submitted_at,
        ]);
    }

    // Helper methods
    private function determineStatus(Carbon $submittedAt): DocumentStatus
    {
        $daysAgo = now()->diffInDays($submittedAt);

        if ($daysAgo > 30) {
            // Old documents: mostly approved/rejected
            return collect([
                DocumentStatus::APPROVED,
                DocumentStatus::APPROVED,
                DocumentStatus::APPROVED,
                DocumentStatus::REJECTED,
            ])->random();
        } elseif ($daysAgo > 7) {
            // Mid documents: mix
            return collect([
                DocumentStatus::APPROVED,
                DocumentStatus::APPROVED,
                DocumentStatus::REJECTED,
                DocumentStatus::PENDING,
            ])->random();
        } else {
            // Recent: mostly pending
            return collect([
                DocumentStatus::PENDING,
                DocumentStatus::PENDING,
                DocumentStatus::APPROVED,
            ])->random();
        }
    }

    private function determineStepStatus(DocumentStatus $docStatus): ApprovalStatus
    {
        return match ($docStatus) {
            DocumentStatus::PENDING => ApprovalStatus::PENDING,
            DocumentStatus::APPROVED => ApprovalStatus::APPROVED,
            DocumentStatus::REJECTED => ApprovalStatus::REJECTED,
            default => ApprovalStatus::PENDING,
        };
    }

    private function generateCompletedAt(Carbon $submittedAt): ?Carbon
    {
        $status = $this->determineStatus($submittedAt);

        if ($status === DocumentStatus::PENDING) {
            return null;
        }

        return $submittedAt->copy()->addHours(rand(4, 48));
    }

    private function getSecondApprover(Document $document, User $manager): ?User
    {
        // For reimbursement: Finance
        if ($document->documentType->slug === 'reimbursement') {
            return User::whereHas('role', fn($q) => $q->where('slug', 'finance'))->first();
        }

        // For others: Department head (another manager in same dept)
        return User::where('department_id', $document->submitter->department_id)
            ->whereHas('role', fn($q) => $q->where('slug', 'manager'))
            ->where('id', '!=', $manager->id)
            ->whereNull('reports_to')
            ->first() ?? $manager; // Fallback to same manager if no dept head
    }

    private function getSecondStepName(Document $document): string
    {
        if ($document->documentType->slug === 'reimbursement') {
            return 'Finance Verification';
        }
        return 'Department Head Approval';
    }

    private function generateApprovalComment(ApprovalStatus $status): string
    {
        $approved = ['Approved', 'Looks good', 'Approved, proceed', 'OK'];
        $rejected = ['Quota exceeded', 'Insufficient budget', 'Please resubmit with more details'];

        return $status === ApprovalStatus::APPROVED
            ? $approved[array_rand($approved)]
            : $rejected[array_rand($rejected)];
    }

    private function randomLeaveType(): string
    {
        return collect(['annual', 'sick', 'emergency'])->random();
    }

    private function randomExpenseType(): string
    {
        return collect(['travel', 'meal', 'accommodation', 'other'])->random();
    }

    private function generateLeaveTitle(): string
    {
        $titles = [
            'Annual Leave - Family Time',
            'Sick Leave - Medical Appointment',
            'Emergency Leave - Personal Matter',
            'Annual Leave - Extended Weekend',
            'Sick Leave - Recovery Period',
        ];
        return $titles[array_rand($titles)];
    }

    private function generateReimbursementTitle(int $amount): string
    {
        return 'Reimbursement Claim - IDR ' . number_format($amount, 0, ',', '.');
    }

    private function generatePurchaseTitle(): string
    {
        $items = ['Office Equipment', 'Software License', 'Furniture', 'IT Hardware', 'Stationery'];
        return 'Purchase Request - ' . $items[array_rand($items)];
    }

    private function generateReimbursementAmount(): int
    {
        // Realistic distribution: mostly small amounts, some large
        $random = rand(1, 100);
        if ($random <= 70) {
            return rand(50000, 1000000); // 50k - 1M (70%)
        } elseif ($random <= 90) {
            return rand(1000000, 3000000); // 1M - 3M (20%)
        } else {
            return rand(3000000, 8000000); // 3M - 8M (10%)
        }
    }

    private function generateItemName(): string
    {
        $items = [
            'Ergonomic Office Chair',
            'Laptop Computer',
            'Monitor Display',
            'Wireless Mouse and Keyboard',
            'Desk Lamp',
            'Filing Cabinet',
            'Whiteboard',
            'Conference Table',
            'Printer',
            'External Hard Drive',
        ];
        return $items[array_rand($items)];
    }

    private function generateVendorName(): string
    {
        $vendors = [
            'PT Teknologi Maju',
            'CV Sejahtera Office',
            'Toko Komputer Central',
            'PT Furniture Indonesia',
            'Online Marketplace',
        ];
        return $vendors[array_rand($vendors)];
    }
}
