<?php

namespace App\Models;

use Illuminate\Cache\HasCacheLock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'metadata',
        'created_at'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime'
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Polymorphic relationship to entity
    public function entity()
    {
        return $this->morphTo(__FUNCTION__, 'entity_type', 'entity_id');
    }

    // Scopes
    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Business Logic
    public static function log(
        string $action,
        Model $entity,
        ?User $user = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): self {
        $user = $user ?? auth()->guard()->user();

        return self::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'action' => $action,
            'entity_type' => class_basename($entity),
            'entity_id' => $entity->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => array_merge($metadata ?? [], [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]),
            'created_at' => now()
        ]);
    }

    public function getChanges(): array
    {
        $changes = [];

        foreach ($this->new_values ?? [] as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'from' => $oldValue,
                    'to' => $newValue
                ];
            }
        }

        return $changes;
    }

    public function getActionDescriptionAttribute(): string
    {
        return match ($this->action) {
            'document.created' => 'Dokumen dibuat',
            'document.submitted' => 'Dokumen diajukan',
            'document.approved' => 'Dokumen disetujui',
            'document.rejected' => 'Dokumen ditolak',
            'document.returned' => 'Dokumen dikembalikan',
            'document.cancelled' => 'Dokumen dibatalkan',
            'approval.approved' => 'Menyetujui tahap approval',
            'approval.rejected' => 'Menolak tahap approval',
            'approval.delegated' => 'Mendelegasikan approval',
            default => $this->action,
        };
    }
}
