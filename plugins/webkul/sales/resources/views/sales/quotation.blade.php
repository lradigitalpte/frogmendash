@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Storage;
    use Webkul\Sale\Enums\OrderState;

    $company = $record->company;
    $partner = $record->partner;
    $billing = $record->partnerInvoice ?? $record->partner;
    $shipping = $record->partnerShipping ?? $record->partner;
    $currency = $record->currency->name ?? 'AED';
    $title = $record->state == OrderState::SALE ? 'Sales Order' : 'Quotation';

    $logoPath = $company?->partner?->avatar ?: $company?->logo;
    $logoData = null;
    if ($logoPath) {
        $defaultDisk = config('filesystems.default', 'public');
        if (Storage::disk($defaultDisk)->exists($logoPath)) {
            $ext = pathinfo($logoPath, PATHINFO_EXTENSION) ?: 'png';
            $logoData = 'data:image/' . $ext . ';base64,' . base64_encode(Storage::disk($defaultDisk)->get($logoPath));
        } elseif (Storage::disk('public')->exists($logoPath)) {
            $ext = pathinfo($logoPath, PATHINFO_EXTENSION) ?: 'png';
            $logoData = 'data:image/' . $ext . ';base64,' . base64_encode(Storage::disk('public')->get($logoPath));
        }
    }

    $vatRate = $record->amount_untaxed > 0
        ? rtrim(rtrim(number_format($record->amount_tax / $record->amount_untaxed * 100, 2), '0'), '.')
        : 5;

    $fmtDate = fn ($value) => $value ? Carbon::parse($value)->format('d/m/Y') : '';
    $showUom = app(\Webkul\Product\Settings\ProductSettings::class)->enable_uom;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
        .quotation-sheet {
            background: #ffffff;
            color: #222222;
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            padding: 28px;
            margin: 0 auto;
        }
        .quotation-sheet * { color: inherit; }

        .quotation-sheet .top-bar {
            height: 8px;
            background: #1f2d5c;
            margin-bottom: 18px;
        }

        .quotation-sheet table { border-collapse: collapse; }

        .quotation-sheet .header-table { width: 100%; }
        .quotation-sheet .header-table > tbody > tr > td { vertical-align: top; }
        .quotation-sheet .logo-cell { width: 55%; }
        .quotation-sheet .logo-cell img { max-height: 90px; max-width: 220px; }
        .quotation-sheet .meta-cell { width: 45%; text-align: right; }

        .quotation-sheet .doc-title {
            font-size: 22px;
            font-weight: bold;
            color: #1f2d5c;
            margin-bottom: 6px;
        }

        .quotation-sheet .meta-box { width: 100%; border: 1px solid #1f2d5c; }
        .quotation-sheet .meta-box td {
            border: 1px solid #ffffff;
            padding: 5px 8px;
            font-size: 11px;
        }
        .quotation-sheet .meta-box .label {
            background: #2f5c9e;
            color: #ffffff;
            text-align: left;
            width: 55%;
        }
        .quotation-sheet .meta-box .value { background: #ffffff; color: #222222; text-align: right; }
        .quotation-sheet .meta-box .validity { color: #d12f2f; font-weight: bold; }

        .quotation-sheet .company-name {
            font-size: 17px;
            font-weight: bold;
            color: #1f2d5c;
            margin: 16px 0 4px;
        }
        .quotation-sheet .company-block div { font-size: 11.5px; }

        .quotation-sheet .address-table { width: 100%; margin-top: 18px; }
        .quotation-sheet .address-table > tbody > tr > td { vertical-align: top; width: 50%; }
        .quotation-sheet .address-cell-left { padding-right: 12px; }
        .quotation-sheet .address-cell-right { padding-left: 12px; }
        .quotation-sheet .address-label { font-weight: bold; margin-bottom: 4px; }
        .quotation-sheet .address-box {
            border: 1px solid #b9c2d0;
            min-height: 70px;
            padding: 8px 10px;
            font-size: 11.5px;
        }

        .quotation-sheet .items-table { width: 100%; margin: 22px 0 10px; }
        .quotation-sheet .items-table th {
            background: #3e6db5;
            color: #ffffff;
            padding: 8px 10px;
            text-align: left;
            font-size: 11.5px;
            border: 1px solid #3e6db5;
        }
        .quotation-sheet .items-table td {
            padding: 7px 10px;
            border: 1px solid #cfd6e0;
            font-size: 11.5px;
        }
        .quotation-sheet .items-table .num { text-align: right; }

        .quotation-sheet .totals-table { width: 100%; margin-top: 6px; }
        .quotation-sheet .totals-table > tbody > tr > td { vertical-align: top; }
        .quotation-sheet .totals-spacer { width: 55%; padding-right: 16px; }
        .quotation-sheet .notes-box {
            border: 1px solid #b9c2d0;
            min-height: 90px;
            padding: 8px 10px;
            font-size: 11px;
            line-height: 1.45;
        }
        .quotation-sheet .notes-box .notes-label {
            font-weight: bold;
            margin-bottom: 4px;
            color: #1f2d5c;
        }
        .quotation-sheet .notes-box p { margin: 0 0 4px; }
        .quotation-sheet .totals-box { width: 45%; }
        .quotation-sheet .totals-box table { width: 100%; }
        .quotation-sheet .totals-box td { padding: 6px 10px; font-size: 12px; }
        .quotation-sheet .totals-box .t-label { text-align: left; }
        .quotation-sheet .totals-box .t-value { text-align: right; }
        .quotation-sheet .totals-box .grand td {
            border: 1px solid #b9c2d0;
            font-weight: bold;
            font-size: 13px;
        }

        .quotation-sheet .footer-note { margin-top: 26px; font-size: 11px; color: #444444; }
    </style>
</head>

<body>
    <div class="quotation-sheet">
        <div class="top-bar"></div>

        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if ($logoData)
                        <img src="{{ $logoData }}" alt="{{ $company?->name }}">
                    @else
                        <div class="company-name">{{ $company?->name }}</div>
                    @endif
                </td>
                <td class="meta-cell">
                    <div class="doc-title">{{ $title }}</div>
                    <table class="meta-box">
                        <tr>
                            <td class="label">{{ $record->state == OrderState::SALE ? 'Order No.' : 'Quotation No.' }}</td>
                            <td class="value">{{ $record->name }}</td>
                        </tr>
                        <tr>
                            <td class="label">Date</td>
                            <td class="value">{{ $fmtDate($record->date_order) }}</td>
                        </tr>
                        <tr>
                            <td class="label">Total Amount</td>
                            <td class="value">{{ money($record->amount_total, $currency) }}</td>
                        </tr>
                        @if ($record->validity_date)
                            <tr>
                                <td class="label">Validity</td>
                                <td class="value validity">{{ $fmtDate($record->validity_date) }}</td>
                            </tr>
                        @endif
                        @if ($record->paymentTerm)
                            <tr>
                                <td class="label">Payment Terms</td>
                                <td class="value">{{ $record->paymentTerm->name }}</td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        <div class="company-block">
            <div class="company-name">{{ $company?->name }}</div>

            @if ($company?->partner)
                <div>
                    {{ $company->partner->street1 }}@if ($company->partner->street2), {{ $company->partner->street2 }}@endif
                </div>
                <div>
                    {{ $company->partner->city }}@if ($company->partner->state), {{ $company->partner->state->name }}@endif @if ($company->partner->zip) {{ $company->partner->zip }} @endif@if ($company->partner->country), {{ $company->partner->country->name }}@endif
                </div>
            @endif

            @if ($company?->tax_id)
                <div>TRN : {{ $company->tax_id }}</div>
            @endif
            @if ($company?->phone)
                <div>Phone : {{ $company->phone }}</div>
            @endif
            @if ($company?->email)
                <div>Email : {{ $company->email }}</div>
            @endif
            @if ($company?->website)
                <div>Website : {{ $company->website }}</div>
            @endif
        </div>

        <table class="address-table">
            <tr>
                <td class="address-cell-left">
                    <div class="address-label">{{ $record->state == OrderState::SALE ? 'Order To:' : 'Quotation To:' }}</div>
                    <div class="address-box">
                        @if ($partner)
                            <div><strong>{{ $partner->name }}</strong></div>
                            <div>{{ $partner->street1 }}@if ($partner->street2), {{ $partner->street2 }}@endif</div>
                            <div>{{ $partner->city }}@if ($partner->state), {{ $partner->state->name }}@endif @if ($partner->zip){{ $partner->zip }}@endif</div>
                            @if ($partner->country)<div>{{ $partner->country->name }}</div>@endif
                            @if ($partner->tax_id)<div>TRN : {{ $partner->tax_id }}</div>@endif
                        @else
                            <div>Customer details not available.</div>
                        @endif
                    </div>
                </td>
                <td class="address-cell-right">
                    <div class="address-label">Billing Address</div>
                    <div class="address-box">
                        @if ($billing)
                            <div><strong>{{ $billing->name }}</strong></div>
                            <div>{{ $billing->street1 }}@if ($billing->street2), {{ $billing->street2 }}@endif</div>
                            <div>{{ $billing->city }}@if ($billing->state), {{ $billing->state->name }}@endif @if ($billing->zip){{ $billing->zip }}@endif</div>
                            @if ($billing->country)<div>{{ $billing->country->name }}</div>@endif
                        @else
                            <div>—</div>
                        @endif
                    </div>
                </td>
            </tr>
            @if ($shipping && $shipping?->id !== $billing?->id)
                <tr>
                    <td colspan="2" style="padding-top: 12px;">
                        <div class="address-label">Shipping Address</div>
                        <div class="address-box">
                            <div><strong>{{ $shipping->name }}</strong></div>
                            <div>{{ $shipping->street1 }}@if ($shipping->street2), {{ $shipping->street2 }}@endif</div>
                            <div>{{ $shipping->city }}@if ($shipping->state), {{ $shipping->state->name }}@endif @if ($shipping->zip){{ $shipping->zip }}@endif</div>
                            @if ($shipping->country)<div>{{ $shipping->country->name }}</div>@endif
                        </div>
                    </td>
                </tr>
            @endif
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 42%;">Description</th>
                    <th class="num" style="width: 12%;">Qty</th>
                    @if ($showUom)
                        <th style="width: 12%;">Unit</th>
                    @endif
                    <th class="num" style="width: 17%;">Unit Price</th>
                    <th class="num" style="width: 17%;">Total Price</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($record->lines as $item)
                    <tr>
                        <td>{{ $item->name ?? $item->product?->name }}</td>
                        <td class="num">{{ number_format($item->product_uom_qty, 2) }}</td>
                        @if ($showUom)
                            <td>{{ $item->product?->uom?->name ?? $item->product_uom?->name ?? '-' }}</td>
                        @endif
                        <td class="num">{{ money($item->price_unit, $currency) }}</td>
                        <td class="num">{{ money($item->price_subtotal ?? ($item->price_unit * $item->product_uom_qty), $currency) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $showUom ? 5 : 4 }}">&nbsp;</td></tr>
                @endforelse
            </tbody>
        </table>

        <table class="totals-table">
            <tr>
                <td class="totals-spacer">
                    @if (filled(trim(strip_tags((string) $record->note))))
                        <div class="notes-box">
                            <div class="notes-label">Notes</div>
                            {!! str($record->note)->sanitizeHtml() !!}
                        </div>
                    @endif
                </td>
                <td class="totals-box">
                    <table>
                        <tr>
                            <td class="t-label">Sub Total</td>
                            <td class="t-value">{{ money($record->amount_untaxed, $currency) }}</td>
                        </tr>
                        <tr>
                            <td class="t-label">VAT {{ $vatRate }}%</td>
                            <td class="t-value">{{ money($record->amount_tax, $currency) }}</td>
                        </tr>
                        @if ($record->total_discount)
                            <tr>
                                <td class="t-label">Discount</td>
                                <td class="t-value">-{{ money($record->total_discount, $currency) }}</td>
                            </tr>
                        @endif
                        <tr class="grand">
                            <td class="t-label">Total Amount<br>(Including VAT)</td>
                            <td class="t-value">{{ money($record->amount_total, $currency) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="footer-note">
            {{ $record->state == OrderState::SALE ? 'Order' : 'Quotation' }} no. {{ $record->name }} should be used as reference in future correspondence.
        </div>
    </div>
</body>

</html>
