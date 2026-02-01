<?php

namespace App\Models;

use App\Enums\PolicyType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Policy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'policy_type',
        'document_type_id',
        'department_id',
        'role_id',
        'rules',
        'is_active',
        'priority'
    ];

    protected $casts = [
        'policy_type' => PolicyType::class,
        'rules' => 'array',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    public function scopeForDocumentType($query, int $documentTypeId)
    {
        return $query->where(function ($q) use ($documentTypeId) {
            $q->where('document_type_id', $documentTypeId)
                ->orWhereNull('document_type_id');
        });
    }

    // Business Logic
    public function isApplicable(User $user, Document $document): bool
    {
        // Check document type
        if ($this->document_type_id && $this->document_type_id !== $document->document_type_id) {
            return false;
        }

        // Check department
        if ($this->department_id && $this->department_id !== $user->department_id) {
            return false;
        }

        // Check role
        if ($this->role_id && $this->role_id !== $user->role_id) {
            return false;
        }

        return true;
    }

    public function evaluate(User $user, Document $document): bool
    {
        if (!$this->isApplicable($user, $document)) {
            return true; // Not applicable = pass
        }

        return match ($this->policy_type) {
            PolicyType::QUOTA_LIMIT => $this->evaluateQuotaLimit($user, $document),
            PolicyType::AMOUNT_THRESHOLD => $this->evaluateAmountThreshold($document),
            PolicyType::TIME_BASED => $this->evaluateTimeBased($document),
            PolicyType::CUSTOM => $this->evaluateCustom($user, $document),
            default => true,
        };
    }

    private function evaluateQuotaLimit(User $user, Document $document): bool
    {
        $rules = $this->rules;
        $quotaType = $rules['quota_type'] ?? null;
        $maxPerYear = $rules['max_days_per_year'] ?? 12;
        $maxPerRequest = $rules['max_days_per_request'] ?? 14;

        // Get requesged days
        $requestedDays = (int) data_get($document->data, 'days', 0);

        // Check max per request
        if ($requestedDays > $maxPerRequest) {
            throw new \Exception("Maksimal {$maxPerRequest} hari per pangajuan");
        }

        // Calculate used quota this year
        $usedDays = Document::where('submitter_id', $user->id)
            ->where('document_type_id', $document->document_type_id)
            ->whereIn('status', ['approved', 'completed'])
            ->whereYear('created_at', now()->year)
            ->get()
            ->sum(fn($doc) => (int) data_get($doc->data, 'days', 0));

        // Check if enought quota
        $remainingQuota = $maxPerYear - $usedDays;

        if ($requestedDays > $remainingQuota) {
            throw new \Exception("Kuota tidak mencukupi, Sisa: {$remainingQuota} hari");
        }

        return true;
    }

    private function evaluateAmountThreshold(Document $document): bool
    {
        $rules = $this->rules;
        $field = $rules['field'] ?? 'amount';
        $threshold = $rules['threshold'] ?? 0;
        $action = $rules['action'] ?? 'require_extra_approval';

        $amount = (float) data_get($document->data, $field, 0);

        if ($amount > $threshold) {
            return true;
        }

        return true;
    }

    private function evaluateTimeBased(Document $document): bool
    {
        $rules = $this->rules;
        $minNoticeDays = $rules['min_notice_days'] ?? 3;

        $starDate = data_get($document->data, 'start_date');

        if ($starDate) {
            $daysUntilStart = now()->diffInDays(Carbon::parse($starDate), false);

            if ($daysUntilStart < $minNoticeDays) {
                throw new \Exception("Minimal pengajuan {$minNoticeDays} hari sebelum tanggal mulai");
            }
        }

        return true;
    }

    private function evaluateCustom(User $user, Document $document): bool
    {
        return true;
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->policy_type->label();
    }
}
