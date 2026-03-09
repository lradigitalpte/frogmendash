<?php

namespace Webkul\RovInspection\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionPoint extends Model
{
    use HasFactory;

    protected $table = 'inspection_points';

    protected $fillable = [
        'point_number',
        'label',
        'x_coordinate',
        'y_coordinate',
        'severity',
        'defect_type',
        'description',
        'recommendations',
        'rov_project_id',
    ];

    protected $casts = [
        'x_coordinate' => 'float',
        'y_coordinate' => 'float',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(RovProject::class, 'rov_project_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(InspectionMedia::class, 'inspection_point_id');
    }
}
