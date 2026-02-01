<?php

namespace App\Models;

use Dom\Document;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'form_schema',
        'requires_attachment',
        'max_attachments',
        'is_active',
    ];

    protected $casts = [
        'form_schema' => 'array',
        'requires_attachment' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function approvalTemplates(): HasMany
    {
        return $this->hasMany(ApprovalTemplate::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Business Logic
    public function getDefaultTemplate(): ?ApprovalTemplate
    {
        return $this->approvalTemplates()
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    public function getFormFields(): array
    {
        return $this->form_schema['fields'] ?? [];
    }
}
