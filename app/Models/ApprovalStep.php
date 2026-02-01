<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'template_step_id',
        'sequence',
        'step_name',
        'approver_id',
        'delegated_from_id',
        'delegation_start_date',
        'delegation_end_date',
        'status',
        'action_taken_at',
        'action_taken_by',
        'comments',
        'sla_hours',
        'due_at',
    ];

    protected $casts = [
        'status' => ApprovalStatus::class,
        'delegation_start_date' => 'date',
        'delegation_end_date' => 'date',
        'action_taken_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    // Relationships
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function templateStep(): BelongsTo
    {
        return $this->belongsTo(ApprovalTemplateStep::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function delegatedFrom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_from_id');
    }

    public function actionTakenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_taken_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', ApprovalStatus::PENDING->value);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', ApprovalStatus::APPROVED->value);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [
            ApprovalStatus::APPROVED->value,
            ApprovalStatus::REJECTED->value,
            ApprovalStatus::RETURNED->value,
        ]);
    }

    // Business Logic
    public function isPending(): bool
    {
        return $this->status === ApprovalStatus::PENDING;
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [
            ApprovalStatus::APPROVED,
            ApprovalStatus::REJECTED,
            ApprovalStatus::RETURNED,
            ApprovalStatus::SKIPPED
        ]);
    }

    public function canTakeAction(): bool
    {
        return $this->isPending();
    }

    public function isDelegated(): bool
    {
        return $this->delegated_from_id !== null;
    }

    public function isDelegationActive(): bool
    {
        if (!$this->isDelegated()) {
            return false;
        }

        $now = now();

        return $now->between(
            $this->delegation_start_date,
            $this->delegation_end_date,
        );
    }

    public function isSLABreached(): bool
    {
        if (!$this->due_at || $this->isCompleted()) {
            return false;
        }

        return now()->isAfter($this->due_at);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }
}
