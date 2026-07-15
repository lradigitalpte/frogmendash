@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Storage;

    $fmtDate = fn ($value) => $value ? Carbon::parse($value)->format('d/m/Y') : '';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
        .po-sheet {
            background: #ffffff;
            color: #222222;
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            padding: 28px;
            margin: 0 auto;
            page-break-after: always;
        }
        .po-sheet:last-child { page-break-after: auto; }
        .po-sheet * { color: inherit; }

        .po-sheet .top-bar {
            height: 8px;
            background: #1f2d5c;
            margin-bottom: 18px;
        }

        .po-sheet table { border-collapse: collapse; }

        .po-sheet .header-table { width: 100%; }
        .po-sheet .header-table > tbody > tr > td { vertical-align: top; }
        .po-sheet .logo-cell { width: 55%; }
        .po-sheet .logo-cell img { max-height: 90px; max-width: 220px; }
        .po-sheet .meta-cell { width: 45%; text-align: right; }

        .po-sheet .doc-title {
            font-size: 22px;
            font-weight: bold;
            color: #1f2d5c;
            margin-bottom: 6px;
        }

        .po-sheet .meta-box { width: 100%; border: 1px solid #1f2d5c; }
        .po-sheet .meta-box td {
            border: 1px solid #ffffff;
            padding: 5px 8px;
            font-size: 11px;
        }
        .po-sheet .meta-box .label {
            background: #2f5c9e;
            color: #ffffff;
            text-align: left;
            width: 55%;
        }
        .po-sheet .meta-box .value { background: #ffffff; color: #222222; text-align: right; }
        .po-sheet .meta-box .validity { color: #d12f2f; font-weight: bold; }

        .po-sheet .company-name {
            font-size: 17px;
            font-weight: bold;
            color: #1f2d5c;
            margin: 16px 0 4px;
        }
        .po-sheet .company-block div { font-size: 11.5px; }

        .po-sheet .address-table { width: 100%; margin-top: 18px; }
        .po-sheet .address-table > tbody > tr > td { vertical-align: top; width: 50%; }
        .po-sheet .address-cell-left { padding-right: 12px; }
        .po-sheet .address-cell-right { padding-left: 12px; }
        .po-sheet .address-label { font-weight: bold; margin-bottom: 4px; }
        .po-sheet .address-box {
            border: 1px solid #b9c2d0;
            min-height: 70px;
            padding: 8px 10px;
            font-size: 11.5px;
        }

        .po-sheet .items-table { width: 100%; margin: 22px 0 10px; }
        .po-sheet .items-table th {
            background: #3e6db5;
            color: #ffffff;
            padding: 8px 10px;
            text-align: left;
            font-size: 11.5px;
            border: 1px solid #3e6db5;
        }
        .po-sheet .items-table td {
            padding: 7px 10px;
            border: 1px solid #cfd6e0;
            font-size: 11.5px;
        }
        .po-sheet .items-table .num { text-align: right; }

        .po-sheet .totals-table { width: 100%; margin-top: 6px; }
        .po-sheet .totals-table > tbody > tr > td { vertical-align: top; }
        .po-sheet .totals-spacer { width: 55%; padding-right: 16px; }
        .po-sheet .notes-box {
            border: 1px solid #b9c2d0;
            min-height: 90px;
            padding: 8px 10px;
            font-size: 11px;
            line-height: 1.45;
        }
        .po-sheet .notes-box .notes-label {
            font-weight: bold;
            margin-bottom: 4px;
            color: #1f2d5c;
        }
        .po-sheet .notes-box p { margin: 0 0 4px; }
        .po-sheet .totals-box { width: 45%; }
        .po-sheet .totals-box table { width: 100%; }
        .po-sheet .totals-box td { padding: 6px 10px; font-size: 12px; }
        .po-sheet .totals-box .t-label { text-align: left; }
        .po-sheet .totals-box .t-value { text-align: right; }
        .po-sheet .totals-box .grand td {
            border: 1px solid #b9c2d0;
            font-weight: bold;
            font-size: 13px;
        }

        .po-sheet .footer-note { margin-top: 26px; font-size: 11px; color: #444444; }
    </style>
</head>

<body>
@foreach ($records as $record)
    @php
        $company  = $record->company;
        $partner  = $record->partner;
        $currency = $record->currency->name ?? 'AED';

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

        $vatRate = $record->untaxed_amount > 0
            ? rtrim(rtrim(number_format($record->tax_amount / $record->untaxed_amount * 100, 2), '0'), '.')
            : 5;
    @endphp

    <div class="po-sheet">
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
                    <div class="doc-title">Purchase Order</div>
                    <table class="meta-box">
                        <tr>
                            <td class="label">PO No.</td>
                            <td class="value">{{ $record->name }}</td>
                        </tr>
                        <tr>
                            <td class="label">Date</td>
                            <td class="value">{{ $fmtDate($record->ordered_at ?? $record->created_at) }}</td>
                        </tr>
                        <tr>
                            <td class="label">Total</td>
                            <td class="value">{{ money($record->total_amount, $currency) }}</td>
                        </tr>
                        @if ($record->paymentTerm)
                            <tr>
                                <td class="label">Payment Terms</td>
                                <td class="value validity">{{ $record->paymentTerm->name }}</td>
                            </tr>
                        @endif
                        @if ($record->planned_at)
                            <tr>
                                <td class="label">Expected Arrival</td>
                                <td class="value">{{ $fmtDate($record->planned_at) }}</td>
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
                    <div class="address-label">Vendor:</div>
                    <div class="address-box">
                        @if ($partner)
                            <div><strong>{{ $partner->name }}</strong></div>
                            <div>{{ $partner->street1 }}@if ($partner->street2), {{ $partner->street2 }}@endif</div>
                            <div>{{ $partner->city }}@if ($partner->state), {{ $partner->state->name }}@endif @if ($partner->zip){{ $partner->zip }}@endif</div>
                            @if ($partner->country)<div>{{ $partner->country->name }}</div>@endif
                            @if ($partner->tax_id)<div>TRN : {{ $partner->tax_id }}</div>@endif
                            @if ($partner->email)<div>Email : {{ $partner->email }}</div>@endif
                            @if ($partner->phone)<div>Phone : {{ $partner->phone }}</div>@endif
                        @else
                            <div>Vendor details not available.</div>
                        @endif
                    </div>
                </td>
                <td class="address-cell-right">
                    <div class="address-label">Ship To:</div>
                    <div class="address-box">
                        @if ($company?->partner)
                            <div><strong>{{ $company->name }}</strong></div>
                            <div>{{ $company->partner->street1 }}@if ($company->partner->street2), {{ $company->partner->street2 }}@endif</div>
                            <div>{{ $company->partner->city }}@if ($company->partner->state), {{ $company->partner->state->name }}@endif @if ($company->partner->zip){{ $company->partner->zip }}@endif</div>
                            @if ($company->partner->country)<div>{{ $company->partner->country->name }}</div>@endif
                        @else
                            <div>{{ $company?->name ?? '—' }}</div>
                        @endif
                        @if ($record->user)
                            <div style="margin-top: 6px;">Buyer: {{ $record->user->name }}</div>
                        @endif
                        @if ($record->partner_reference)
                            <div>Vendor Ref: {{ $record->partner_reference }}</div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 44%;">Description</th>
                    <th class="num" style="width: 12%;">Qty</th>
                    <th class="num" style="width: 14%;">Unit Price</th>
                    <th class="num" style="width: 12%;">Discount</th>
                    <th class="num" style="width: 18%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($record->lines as $item)
                    <tr>
                        <td>
                            {{ $item->name }}
                            @if ($item->taxes?->isNotEmpty())
                                <div style="font-size: 10px; color: #666;">{{ $item->taxes->pluck('name')->implode(', ') }}</div>
                            @endif
                        </td>
                        <td class="num">{{ number_format($item->product_qty, 2) }}{{ $item->uom?->name ? ' '.$item->uom->name : '' }}</td>
                        <td class="num">{{ money($item->price_unit, $currency) }}</td>
                        <td class="num">{{ round($item->discount ?? 0, 2) }}%</td>
                        <td class="num">{{ money($item->price_subtotal, $currency) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">&nbsp;</td></tr>
                @endforelse
            </tbody>
        </table>

        <table class="totals-table">
            <tr>
                <td class="totals-spacer">
                    @if (filled(trim(strip_tags((string) $record->description))))
                        <div class="notes-box">
                            <div class="notes-label">Notes / Terms</div>
                            {!! str($record->description)->sanitizeHtml() !!}
                        </div>
                    @endif
                </td>
                <td class="totals-box">
                    <table>
                        <tr>
                            <td class="t-label">Sub Total</td>
                            <td class="t-value">{{ money($record->untaxed_amount, $currency) }}</td>
                        </tr>
                        <tr>
                            <td class="t-label">VAT {{ $vatRate }}%</td>
                            <td class="t-value">{{ money($record->tax_amount, $currency) }}</td>
                        </tr>
                        <tr class="grand">
                            <td class="t-label">Total Amount<br>(Including VAT)</td>
                            <td class="t-value">{{ money($record->total_amount, $currency) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="footer-note">
            Please use PO no. {{ $record->name }} as reference on all correspondence and invoices.
        </div>
    </div>
@endforeach
</body>
</html>
