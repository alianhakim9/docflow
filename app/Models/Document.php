<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'document_number',
        'document_type_id',
        'submitter_id',
        'title',
        'data',
        'status',
        'submitted_at',
        'completed_at',
    ];

    protected $casts = [
        'data' => 'array',
        'status' => DocumentStatus::class,
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitter_id');
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'entity_id')
            ->where('entity_type', 'Document');
    }

    // Scopes
    public function scopeByStatus($query, DocumentStatus|string $status)
    {
        if ($status instanceof DocumentStatus) {
            $status = $status->value;
        }

        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', DocumentStatus::PENDING->value);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', DocumentStatus::APPROVED->value);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', DocumentStatus::REJECTED->value);
    }

    // Business Logic
    public function canBeEdited(): bool
    {
        return in_array($this->status, [
            DocumentStatus::DRAFT,
            DocumentStatus::RETURNED
        ]);
    }

    public function canBeCancelled(): bool
    {
        return $this->status === DocumentStatus::PENDING
            && $this->approvalSteps()->pending()->exists();
    }

    public function canBeDeleted(): bool
    {
        return $this->status === DocumentStatus::DRAFT;
    }

    public function currentStep(): ?ApprovalStep
    {
        return $this->approvalSteps()
            ->where('status', ApprovalStatus::PENDING->value)
            ->orderBy('sequence')
            ->first();
    }

    public function isFullyApproved(): bool
    {
        return $this->approvalSteps()
            ->where('status', '!=', ApprovalStatus::APPROVED->value)
            ->doesntExist();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }
}
