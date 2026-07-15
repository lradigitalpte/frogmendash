<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
        .invoice-sheet {
            background: #ffffff;
            color: #2b2b2b;
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            padding: 36px;
            margin: 0 auto;
        }
        .invoice-sheet * { color: inherit; }
        .invoice-sheet table { border-collapse: collapse; }

        .invoice-sheet .head { width: 100%; }
        .invoice-sheet .head > tbody > tr > td { vertical-align: top; }
        .invoice-sheet .head .right { text-align: right; }
        .invoice-sheet .head img { max-height: 70px; max-width: 180px; }
        .invoice-sheet .co-name { font-size: 16px; font-weight: 600; }
        .invoice-sheet .doc-title { font-size: 26px; font-weight: 300; letter-spacing: 6px; text-transform: uppercase; color: #888; }

        .invoice-sheet .small { font-size: 11px; }
        .invoice-sheet .muted { color: #999999; }
        .invoice-sheet .label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #999; }

        .invoice-sheet .meta { width: 100%; margin-top: 34px; }
        .invoice-sheet .meta > tbody > tr > td { vertical-align: top; width: 33%; }

        .invoice-sheet .items { width: 100%; margin-top: 30px; }
        .invoice-sheet .items th {
            padding: 8px 6px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            border-bottom: 1px solid #e2e2e2;
        }
        .invoice-sheet .items td { padding: 10px 6px; border-bottom: 1px solid #f0f0f0; }
        .invoice-sheet .items .num { text-align: right; }

        .invoice-sheet .totals { width: 100%; margin-top: 6px; }
        .invoice-sheet .totals > tbody > tr > td { vertical-align: top; }
        .invoice-sheet .totals .sp { width: 60%; padding-right: 16px; }
        .invoice-sheet .notes-box {
            border: 1px solid #e2e2e2;
            min-height: 80px;
            padding: 8px 10px;
            font-size: 11px;
            line-height: 1.45;
        }
        .invoice-sheet .notes-box .notes-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 4px;
        }
        .invoice-sheet .notes-box p { margin: 0 0 4px; }
        .invoice-sheet .totals .box { width: 40%; }
        .invoice-sheet .totals .box table { width: 100%; }
        .invoice-sheet .totals .box td { padding: 6px 2px; }
        .invoice-sheet .totals .box .v { text-align: right; }
        .invoice-sheet .totals .box .grand td { border-top: 2px solid #2b2b2b; font-weight: 600; font-size: 14px; padding-top: 10px; }

        .invoice-sheet .pay { margin-top: 30px; }
        .invoice-sheet .foot { margin-top: 30px; font-size: 10.5px; color: #999999; }
    </style>
</head>

<body>
    <div class="invoice-sheet">
        <table class="head">
            <tr>
                <td>
                    @if ($logoData)
                        <img src="{{ $logoData }}" alt="{{ $company?->name }}">
                    @else
                        <div class="co-name">{{ $company?->name }}</div>
                    @endif
                </td>
                <td class="right">
                    <div class="doc-title">Invoice</div>
                    <div class="small">{{ $record->name }}</div>
                </td>
            </tr>
        </table>

        <table class="meta">
            <tr>
                <td>
                    <div class="label">From</div>
                    <div class="co-name">{{ $company?->name }}</div>
                    @if ($company?->partner)
                        <div class="small">{{ $company->partner->street1 }}</div>
                        <div class="small">{{ $company->partner->city }}@if ($company->partner->country), {{ $company->partner->country->name }}@endif</div>
                    @endif
                    @if ($company?->tax_id)<div class="small">TRN: {{ $company->tax_id }}</div>@endif
                    @if ($company?->email)<div class="small">{{ $company->email }}</div>@endif
                </td>
                <td>
                    <div class="label">Bill To</div>
                    @if ($partner)
                        <div class="co-name">{{ $partner->name }}</div>
                        <div class="small">{{ $partner->street1 }}</div>
                        <div class="small">{{ $partner->city }}@if ($partner->country), {{ $partner->country->name }}@endif</div>
                        @if ($partner->tax_id)<div class="small">TRN: {{ $partner->tax_id }}</div>@endif
                    @else
                        <div class="small muted">Customer details not available.</div>
                    @endif
                </td>
                <td class="right">
                    <div class="label">Details</div>
                    <div class="small">Date: {{ $fmtDate($record->invoice_date) }}</div>
                    @if ($record->invoicePaymentTerm)<div class="small">Terms: {{ $record->invoicePaymentTerm->name }}</div>@endif
                    <div class="small">Due: {{ money($record->amount_residual, $currency) }}</div>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th style="width:54%;">Description</th>
                    <th class="num" style="width:12%;">Qty</th>
                    <th class="num" style="width:17%;">Unit Price</th>
                    <th class="num" style="width:17%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($record->invoiceLines as $item)
                    <tr>
                        <td>{{ $item->name ?? $item->product?->name }}</td>
                        <td class="num">{{ number_format($item->quantity, 2) }}</td>
                        <td class="num">{{ money($item->price_unit, $currency) }}</td>
                        <td class="num">{{ money($item->price_unit * $item->quantity, $currency) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">&nbsp;</td></tr>
                @endforelse
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td class="sp">
                    @if (filled(trim(strip_tags((string) $record->narration))))
                        <div class="notes-box">
                            <div class="notes-label">Notes</div>
                            {!! str($record->narration)->sanitizeHtml() !!}
                        </div>
                    @endif
                </td>
                <td class="box">
                    <table>
                        <tr><td>Sub Total</td><td class="v">{{ money($record->amount_untaxed, $currency) }}</td></tr>
                        <tr><td>VAT {{ $vatRate }}%</td><td class="v">{{ money($record->amount_tax, $currency) }}</td></tr>
                        @if ($record->total_discount)
                            <tr><td>Discount</td><td class="v">-{{ money($record->total_discount, $currency) }}</td></tr>
                        @endif
                        <tr class="grand"><td>Total</td><td class="v">{{ money($record->amount_total, $currency) }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="pay">
            <div class="label">Payment</div>
            <div class="small">{{ $bankAccount?->partner?->name ?? $company?->name }}</div>
            @if ($bankAccount?->bank?->name)<div class="small">Bank: {{ $bankAccount->bank->name }}</div>@endif
            @if ($bankAccount?->account_number)<div class="small">{{ $currency }} IBAN: {{ $bankAccount->account_number }}</div>@endif
        </div>

        <div class="foot">Invoice no. to be used as reference no. when making payment</div>
    </div>
</body>
</html>
