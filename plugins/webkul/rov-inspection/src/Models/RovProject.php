<?php

namespace Webkul\RovInspection\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\Scopes\CompanyScope;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class RovProject extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rov_projects';

    protected $fillable = [
        'name',
        'description',
        'location',
        'status',
        'site_map_path',
        'start_date',
        'end_date',
        'company_id',
        'customer_id',
        'creator_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'customer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function inspectionPoints(): HasMany
    {
        return $this->hasMany(InspectionPoint::class, 'rov_project_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(InspectionReport::class, 'rov_project_id');
    }
}
