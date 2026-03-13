<?php

namespace Webkul\RovInspection\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Webkul\Security\Models\User;

class InspectionMedia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inspection_media';

    protected $fillable = [
        'structure_id',
        'inspection_point_id',
        'media_type',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'duration',
        'thumbnail_path',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'duration'  => 'integer',
    ];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(ProjectStructure::class, 'structure_id');
    }

    /** The observation pin this media is linked to (nullable). */
    public function inspectionPoint(): BelongsTo
    {
        return $this->belongsTo(InspectionPoint::class, 'inspection_point_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isVideo(): bool
    {
        return $this->media_type === 'video';
    }

    public function isImage(): bool
    {
        return $this->media_type === 'image';
    }

    public function getUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail_path) {
            return Storage::disk('public')->url($this->thumbnail_path);
        }

        return $this->isImage() ? $this->url : null;
    }

    public function getHumanFileSizeAttribute(): string
    {
        $size = $this->file_size;

        if (! $size) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 1).' '.$units[$i];
    }
}
