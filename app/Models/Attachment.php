<?php

namespace App\Models;

use Dom\Document;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Attachment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'filename',
        'original_filename',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    // Relationships
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Business Logic
    public function getUrl(): string
    {
        return Storage::url($this->file_path);
    }

    public function download(): StreamedResponse
    {
        return Storage::download($this->file_path, $this->original_filename);
    }

    public function delete(): bool
    {
        // Delete physical file
        Storage::delete($this->file_path);

        return parent::delete();
    }

    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }
}
