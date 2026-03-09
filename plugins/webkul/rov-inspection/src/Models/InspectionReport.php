<?php

namespace Webkul\RovInspection\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Webkul\Security\Models\User;

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
        'shared_by',
    ];

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
