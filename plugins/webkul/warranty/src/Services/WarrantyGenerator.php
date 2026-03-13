<?php

namespace Webkul\Warranty\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Webkul\Warranty\Enums\StartTrigger;
use Webkul\Warranty\Enums\WarrantyStatus;
use Webkul\Warranty\Models\Warranty;
use Webkul\Warranty\Models\WarrantyPolicy;

class WarrantyGenerator
{
    /**
     * Create a warranty record manually from the admin UI.
     *
     * @param  array{
     *     warranty_policy_id?: int|null,
     *     product_id: int,
     *     serial_number?: string|null,
     *     asset_tag?: string|null,
     *     customer_id: int,
     *     company_id?: int|null,
     *     sales_order_id?: int|null,
     *     delivery_id?: int|null,
     *     start_date?: string|null,
     *     end_date?: string|null,
     *     notes?: string|null,
     * } $data
     */
    public function createManually(array $data): Warranty
    {
        $policy = isset($data['warranty_policy_id'])
            ? WarrantyPolicy::find($data['warranty_policy_id'])
            : null;

        // Derive dates from the policy when not supplied
        if ($policy && empty($data['end_date']) && ! empty($data['start_date'])) {
            $data['end_date'] = Carbon::parse($data['start_date'])
                ->addMonths($policy->duration_months)
                ->toDateString();
        }

        $status = $this->deriveStatus($data['start_date'] ?? null, $data['end_date'] ?? null);

        return Warranty::create([
            'warranty_policy_id'     => $data['warranty_policy_id'] ?? null,
            'product_id'             => $data['product_id'],
            'serial_number'          => $data['serial_number'] ?? null,
            'asset_tag'              => $data['asset_tag'] ?? null,
            'customer_id'            => $data['customer_id'],
            'company_id'             => $data['company_id'] ?? (Auth::user()?->default_company_id),
            'sales_order_id'         => $data['sales_order_id'] ?? null,
            'delivery_id'            => $data['delivery_id'] ?? null,
            'start_date'             => $data['start_date'] ?? null,
            'end_date'               => $data['end_date'] ?? null,
            'start_trigger'          => $policy?->start_trigger ?? StartTrigger::Manual->value,
            'duration_months'        => $policy?->duration_months ?? 12,
            'coverage_snapshot_json' => $policy?->coverage_json ?? [],
            'status'                 => $status->value,
            'notes'                  => $data['notes'] ?? null,
            'creator_id'             => Auth::id(),
        ]);
    }

    /**
     * Auto-generate warranties for every serialised line on a completed delivery.
     *
     * TODO – Phase 2: call this from an Observer on inventories_operations when
     *         state transitions to 'done'.
     *
     *   Example wiring (OperationObserver@updated):
     *     if ($operation->isDirty('state') && $operation->state === 'done') {
     *         app(WarrantyGenerator::class)->createFromDelivery($operation);
     *     }
     */
    public function createFromDelivery(object $operation): array
    {
        $created = [];

        foreach ($operation->moves as $move) {
            $product = $move->product;

            if (! $product || ! $product->warranty_policy_id) {
                continue;
            }

            $policy = WarrantyPolicy::find($product->warranty_policy_id);
            if (! $policy) {
                continue;
            }

            $startDate = $this->resolveStartDate($policy, $operation);

            $created[] = Warranty::create([
                'warranty_policy_id'     => $policy->id,
                'product_id'             => $product->id,
                'serial_number'          => null, // supply externally if tracked
                'customer_id'            => $operation->partner_id,
                'company_id'             => $operation->company_id,
                'delivery_id'            => $operation->id,
                'start_date'             => $startDate?->toDateString(),
                'end_date'               => $startDate?->addMonths($policy->duration_months)->toDateString(),
                'start_trigger'          => $policy->start_trigger,
                'duration_months'        => $policy->duration_months,
                'coverage_snapshot_json' => $policy->coverage_json ?? [],
                'status'                 => WarrantyStatus::Active->value,
                'creator_id'             => Auth::id(),
            ]);
        }

        return $created;
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function resolveStartDate(WarrantyPolicy $policy, object $operation): ?Carbon
    {
        return match ($policy->start_trigger) {
            StartTrigger::DeliveryDate->value  => Carbon::parse($operation->scheduled_at ?? now()),
            StartTrigger::InvoiceDate->value   => null, // wire to invoice once available
            StartTrigger::Manual->value        => null,
            default                            => Carbon::now(),
        };
    }

    private function deriveStatus(?string $startDate, ?string $endDate): WarrantyStatus
    {
        if (! $startDate || ! $endDate) {
            return WarrantyStatus::Draft;
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();
        $now   = now();

        if ($now->lt($start)) {
            return WarrantyStatus::Draft;
        }

        return $now->lte($end) ? WarrantyStatus::Active : WarrantyStatus::Expired;
    }
}
