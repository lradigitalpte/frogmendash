<?php

namespace Webkul\RovInspection\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Security\Models\User;

class InspectionMedia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inspection_media';

    protected $fillable = [
        'media_type',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'duration',
        'thumbnail_path',
        'inspection_point_id',
        'uploaded_by',
    ];

    public function inspectionPoint(): BelongsTo
    {
        return $this->belongsTo(InspectionPoint::class, 'inspection_point_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
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
