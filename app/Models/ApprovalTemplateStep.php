<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalTemplateStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_template_id',
        'sequence',
        'step_name',
        'approver_type',
        'approver_role_id',
        'approver_user_id',
        'is_parallel',
        'sla_hours'
    ];

    protected $casts = [
        'is_parallel' => 'boolean'
    ];

    // Relationships
    public function approvalTemplate(): BelongsTo
    {
        return $this->belongsTo(ApprovalTemplate::class);
    }

    public function approverRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'approver_role_id');
    }

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class, 'template_step_id');
    }

    // Business Logic
    public function resolveApprover(Document $document): ?User
    {
        return match ($this->approver_type) {
            'specific_user' => $this->approverUser,
            'role' => $this->resolveByRole($document),
            'dynamic' => $this->resolveDynamic($document)
        };
    }

    public function resolveByRole(Document $document): ?User
    {
        return User::where('role_id', $this->approver_role_id)
            ->where('department_id', $document->submitter->department_id)
            ->where('is_active', true)
            ->first();
    }

    public function resolveDynamic(Document $document): ?User
    {
        return $document->submitter->manager;
    }
}
