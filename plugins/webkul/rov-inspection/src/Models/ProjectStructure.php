<?php

namespace Webkul\RovInspection\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ProjectStructure extends Model
{
    use HasFactory;

    protected $table = 'project_structures';

    protected $fillable = [
        'name',
        'description',
        'diagram_path',
        'photo_path',
        'sort',
        'rov_project_id',
    ];

    protected $casts = [
        'sort' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(RovProject::class, 'rov_project_id');
    }

    public function views(): HasMany
    {
        return $this->hasMany(InspectionView::class, 'structure_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(InspectionMedia::class, 'structure_id');
    }

    /** All observation points across all inspection views for this structure. */
    public function allPoints(): HasManyThrough
    {
        return $this->hasManyThrough(
            InspectionPoint::class,
            InspectionView::class,
            'structure_id',
            'inspection_view_id'
        );
    }
}
