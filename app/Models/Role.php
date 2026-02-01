<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    //    Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function approvalTemplateSteps(): HasMany
    {
        return $this->hasMany(ApprovalTemplateStep::class, 'approver_role_id');
    }

    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }

    // Business Logic
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }
}
