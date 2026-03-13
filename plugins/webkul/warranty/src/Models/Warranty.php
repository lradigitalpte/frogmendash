<?php

namespace Webkul\Warranty\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Inventory\Models\Operation;
use Webkul\Partner\Models\Partner;
use Webkul\Sales\Models\Order;
use Webkul\Security\Models\Scopes\CompanyScope;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Warranty\Enums\WarrantyStatus;

class Warranty extends Model
{
    use HasFactory;

    protected $table = 'warranties';

    protected $fillable = [
        'warranty_policy_id',
        'product_id',
        'serial_number',
        'asset_tag',
        'customer_id',
        'company_id',
        'sales_order_id',
        'delivery_id',
        'start_date',
        'end_date',
        'start_trigger',
        'duration_months',
        'coverage_snapshot_json',
        'status',
        'notes',
        'creator_id',
    ];

    protected $casts = [
        'start_date'             => 'date',
        'end_date'               => 'date',
        'coverage_snapshot_json' => 'array',
        'duration_months'        => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    // ── Relationships ──────────────────────────────────────────────────────

    public function policy(): BelongsTo
    {
        return $this->belongsTo(WarrantyPolicy::class, 'warranty_policy_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Product\Models\Product::class, 'product_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'customer_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sales_order_id');
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Operation::class, 'delivery_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    // ── Computed helpers ───────────────────────────────────────────────────

    /** Days remaining until expiry (negative when expired). */
    public function getDaysRemainingAttribute(): ?int
    {
        if (! $this->end_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->end_date->startOfDay(), false);
    }

    /** True if end_date is within the next $days calendar days. */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (! $this->end_date || $this->status !== WarrantyStatus::Active->value) {
            return false;
        }

        return $this->days_remaining !== null
            && $this->days_remaining >= 0
            && $this->days_remaining <= $days;
    }

    public function isActive(): bool
    {
        return $this->status === WarrantyStatus::Active->value;
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', WarrantyStatus::Active->value);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', WarrantyStatus::Expired->value);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->active()
            ->whereDate('end_date', '>=', now())
            ->whereDate('end_date', '<=', now()->addDays($days));
    }
}
