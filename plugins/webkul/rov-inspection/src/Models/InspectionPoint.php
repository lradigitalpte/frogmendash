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
        'inspection_view_id',
        'observation_id',
        'point_number',
        'label',
        'x_coordinate',
        'y_coordinate',
        'severity',
        'finding_type',
        'description',
        'dive_location',
        'depth_m',
        'dimension_mm',
        'recommendations',
    ];

    protected $casts = [
        'x_coordinate' => 'float',
        'y_coordinate' => 'float',
        'depth_m'      => 'float',
        'point_number' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $point) {
            if (empty($point->observation_id) && $point->inspection_view_id) {
                $count = static::where('inspection_view_id', $point->inspection_view_id)->count();
                $point->observation_id = 'O'.($count + 1);
            }

            if (empty($point->point_number) && $point->inspection_view_id) {
                $max = static::where('inspection_view_id', $point->inspection_view_id)->max('point_number');
                $point->point_number = ($max ?? 0) + 1;
            }
        });
    }

    public function inspectionView(): BelongsTo
    {
        return $this->belongsTo(InspectionView::class, 'inspection_view_id');
    }

    /** Media files linked to this specific pin (shown in the pin pop-up / inline player). */
    public function media(): HasMany
    {
        return $this->hasMany(InspectionMedia::class, 'inspection_point_id');
    }
}
