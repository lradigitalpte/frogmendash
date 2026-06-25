<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Project\Database\Factories\MilestoneFactory;
use Webkul\Security\Models\Scopes\CompanyScope;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class Milestone extends Model
{
    use HasFactory;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'projects_milestones';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'deadline',
        'is_completed',
        'completed_at',
        'project_id',
        'company_id',
        'creator_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'is_completed' => 'boolean',
        'deadline'     => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected static function boot()
    {
        parent::boot();

        // Tenant isolation: only show milestones for the current company.
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($milestone) {
            $milestone->creator_id = filament()->auth()->id();

            // Inherit the company from the owning project.
            if (empty($milestone->company_id)) {
                $milestone->company_id = $milestone->project?->company_id
                    ?? filament()->auth()->user()?->default_company_id;
            }
        });
    }

    protected static function newFactory(): MilestoneFactory
    {
        return MilestoneFactory::new();
    }
}
