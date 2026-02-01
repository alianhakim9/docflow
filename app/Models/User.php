<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
        'department_id',
        'role_id',
        'reports_to',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean'
        ];
    }

    //    Relationships
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reports_to');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'reports_to');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'submitter_id');
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class, 'approver_id');
    }

    public function delegatedApprovals(): HasMany
    {
        return $this->hasMany(ApprovalStep::class, 'delegated_from_id');
    }

    public function uploadedAttachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'uploaded_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // Scopes
    public function scopeManagers($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->where('slug', 'manager');
        });
    }

    // Business Logic
    public function hasPermission(string $permission): bool
    {
        return $this->role?->hasPermission($permission) ?? false;
    }

    public function isManager(): bool
    {
        return $this->role?->slug === 'manager';
    }

    public function isFinance(): bool
    {
        return $this->role?->slug === 'finance';
    }

    public function isAdmin(): bool
    {
        return $this->role?->slug === 'admin';
    }

    public function canApprove(Document $document): bool
    {
        return $document->approvalSteps()
            ->where('approved_id', $this->id)
            ->where('status', ApprovalStatus::PENDING->value)
            ->exists();
    }
}
