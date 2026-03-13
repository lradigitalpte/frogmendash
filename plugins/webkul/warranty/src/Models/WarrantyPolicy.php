<?php

namespace Webkul\Warranty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Security\Models\Scopes\CompanyScope;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class WarrantyPolicy extends Model
{
    use HasFactory;

    protected $table = 'warranty_policies';

    protected $fillable = [
        'name',
        'description',
        'duration_months',
        'start_trigger',
        'coverage_json',
        'include_spare_parts',
        'include_labour',
        'max_visits_per_year',
        'is_active',
        'company_id',
        'creator_id',
    ];

    protected $casts = [
        'coverage_json'       => 'array',
        'include_spare_parts' => 'boolean',
        'include_labour'      => 'boolean',
        'is_active'           => 'boolean',
        'duration_months'     => 'integer',
        'max_visits_per_year' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(Warranty::class, 'warranty_policy_id');
    }

    /** Human-readable coverage list for display. */
    public function getCoverageLabelAttribute(): string
    {
        $tags = $this->coverage_json ?? [];

        return empty($tags) ? '—' : implode(', ', array_map('ucfirst', $tags));
    }
}
