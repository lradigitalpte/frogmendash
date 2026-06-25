<?php

namespace Webkul\RovInspection\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Webkul\Security\Models\Scopes\CompanyScope;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class InspectionReport extends Model
{
    use HasFactory;

    protected $table = 'inspection_reports';

    protected $fillable = [
        'title',
        'summary',
        'full_report',
        'conclusions',
        'recommendations',
        'status',
        'shared_link_hash',
        'shared_link_password',
        'shared_link_expires_at',
        'client_can_download',
        'client_can_print',
        'shared_date',
        'rov_project_id',
        'company_id',
        'shared_by',
    ];

    protected static function booted(): void
    {
        // Tenant isolation: only show reports for the current company.
        static::addGlobalScope(new CompanyScope);

        // Inherit the company from the owning project so the record is scoped.
        static::creating(function (self $report) {
            if (empty($report->company_id)) {
                $report->company_id = $report->project?->company_id
                    ?? filament()->auth()->user()?->default_company_id;
            }
        });
    }

    protected $casts = [
        'client_can_download'    => 'boolean',
        'client_can_print'       => 'boolean',
        'shared_link_expires_at' => 'datetime',
        'shared_date'            => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(RovProject::class, 'rov_project_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(ReportAccessLog::class, 'report_id');
    }

    public function generateShareLink(): void
    {
        $this->shared_link_hash = Str::uuid()->toString();
        $this->shared_date = now();
    }
}
