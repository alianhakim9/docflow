<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type_id',
        'name',
        'description',
        'condition_rules',
        'is_default',
        'is_active'
    ];

    protected $casts = [
        'condition_rules' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalTemplateStep::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Business Logic
    public function isApplicable(Document $document): bool
    {
        // If no conditions, always applicable
        if (empty($this->condition_rules)) {
            return true;
        }

        // Evaluate conditions based on document data
        // Example: amount > 5000
        $rules = $this->condition_rules;

        if (isset($rules['field']) && isset($rules['operator']) && isset($rules['value'])) {
            $fieldValue = data_get($document->data, $rules['field']);

            return match ($rules['operator']) {
                '>' => $fieldValue > $rules['value'],
                '>=' => $fieldValue >= $rules['value'],
                '<' => $fieldValue < $rules['value'],
                '<=' => $fieldValue <= $rules['value'],
                '=' => $fieldValue === $rules['value'],
                '!=' => $fieldValue != $rules['value'],
                default => false,
            };
        }
        return true;
    }

    public function getStepsOrdered()
    {
        return $this->steps()->orderBy('sequence')->get();
    }
}
