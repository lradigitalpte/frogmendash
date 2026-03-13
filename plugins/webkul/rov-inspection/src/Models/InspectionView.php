<?php

namespace Webkul\RovInspection\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionView extends Model
{
    use HasFactory;

    protected $table = 'inspection_views';

    protected $fillable = [
        'name',
        'view_type',
        'structure_id',
    ];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(ProjectStructure::class, 'structure_id');
    }

    public function points(): HasMany
    {
        return $this->hasMany(InspectionPoint::class, 'inspection_view_id');
    }
}
