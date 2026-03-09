<?php

namespace Webkul\RovInspection\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAccessLog extends Model
{
    use HasFactory;

    protected $table = 'report_access_logs';

    protected $fillable = [
        'accessed_by',
        'accessed_at',
        'ip_address',
        'duration',
        'report_id',
    ];

    protected $casts = [
        'accessed_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(InspectionReport::class, 'report_id');
    }
}
