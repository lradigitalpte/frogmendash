<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
        .invoice-sheet {
            background: #ffffff;
            color: #1a1a1a;
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 12px;
            line-height: 1.55;
            padding: 32px;
            margin: 0 auto;
        }
        .invoice-sheet * { color: inherit; }
        .invoice-sheet table { border-collapse: collapse; }

        .invoice-sheet .head { width: 100%; border-bottom: 3px double #1a1a1a; padding-bottom: 14px; }
        .invoice-sheet .head > tbody > tr > td { vertical-align: top; }
        .invoice-sheet .head .left { width: 60%; }
        .invoice-sheet .head .right { width: 40%; text-align: right; }
        .invoice-sheet .head img { max-height: 80px; max-width: 200px; }
        .invoice-sheet .co-name { font-size: 18px; font-weight: bold; letter-spacing: .5px; }
        .invoice-sheet .doc-title { font-size: 30px; font-weight: bold; letter-spacing: 4px; text-transform: uppercase; }

        .invoice-sheet .small { font-size: 11px; }
        .invoice-sheet .muted { color: #555555; }

        .invoice-sheet .meta { width: 100%; margin-top: 18px; }
        .invoice-sheet .meta > tbody > tr > td { vertical-align: top; width: 50%; }
        .invoice-sheet .meta .blk-title { font-weight: bold; text-transform: uppercase; font-size: 10px; letter-spacing: 1px; color: #555; margin-bottom: 4px; }

        .invoice-sheet .items { width: 100%; margin-top: 24px; }
        .invoice-sheet .items th {
            border-top: 2px solid #1a1a1a;
            border-bottom: 2px solid #1a1a1a;
            padding: 9px 8px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .invoice-sheet .items td { padding: 9px 8px; border-bottom: 1px solid #dddddd; }
        .invoice-sheet .items .num { text-align: right; }

        .invoice-sheet .totals { width: 100%; margin-top: 8px; }
        .invoice-sheet .totals > tbody > tr > td { vertical-align: top; }
        .invoice-sheet .totals .sp { width: 58%; }
        .invoice-sheet .totals .box { width: 42%; }
        .invoice-sheet .totals .box table { width: 100%; }
        .invoice-sheet .totals .box td { padding: 6px 4px; }
        .invoice-sheet .totals .box .v { text-align: right; }
        .invoice-sheet .totals .box .grand td { border-top: 2px solid #1a1a1a; border-bottom: 2px solid #1a1a1a; font-weight: bold; font-size: 14px; }

        .invoice-sheet .pay { margin-top: 26px; border: 1px solid #cccccc; padding: 12px 14px; width: 62%; }
        .invoice-sheet .pay .h { font-weight: bold; text-transform: uppercase; font-size: 11px; letter-spacing: 1px; margin-bottom: 6px; }
        .invoice-sheet .foot { margin-top: 26px; font-size: 11px; font-style: italic; color: #555555; text-align: center; }
    </style>
</head>

<body>
    <div class="invoice-sheet">
        <table class="head">
            <tr>
                <td class="left">
                    @if ($logoData)
                        <img src="{{ $logoData }}" alt="{{ $company?->name }}">
                    @endif
                    <div class="co-name">{{ $company?->name }}</div>
                    @if ($company?->partner)
                        <div class="small">{{ $company->partner->street1 }}@if ($company->partner->street2), {{ $company->partner->street2 }}@endif</div>
                        <div class="small">{{ $company->partner->city }}@if ($company->partner->state), {{ $company->partner->state->name }}@endif @if ($company->partner->zip){{ $company->partner->zip }}@endif@if ($company->partner->country), {{ $company->partner->country->name }}@endif</div>
                    @endif
                    @if ($company?->tax_id)<div class="small">TRN: {{ $company->tax_id }}</div>@endif
                    @if ($company?->phone)<div class="small">{{ $company->phone }}</div>@endif
                    @if ($company?->email)<div class="small">{{ $company->email }}</div>@endif
                </td>
                <td class="right">
                    <div class="doc-title">Invoice</div>
                    <div class="small">No. <strong>{{ $record->name }}</strong></div>
                    <div class="small">Date: {{ $fmtDate($record->invoice_date) }}</div>
                    @if ($record->invoicePaymentTerm)
                        <div class="small">Terms: {{ $record->invoicePaymentTerm->name }}</div>
                    @endif
                    <div class="small"><strong>Amount Due: {{ money($record->amount_residual, $currency) }}</strong></div>
                </td>
            </tr>
        </table>

        <table class="meta">
            <tr>
                <td>
                    <div class="blk-title">Bill To</div>
                    @if ($partner)
                        <div><strong>{{ $partner->name }}</strong></div>
                        <div class="small">{{ $partner->street1 }}@if ($partner->street2), {{ $partner->street2 }}@endif</div>
                        <div class="small">{{ $partner->city }}@if ($partner->state), {{ $partner->state->name }}@endif @if ($partner->zip){{ $partner->zip }}@endif</div>
                        @if ($partner->country)<div class="small">{{ $partner->country->name }}</div>@endif
                        @if ($partner->tax_id)<div class="small">TRN: {{ $partner->tax_id }}</div>@endif
                    @else
                        <div class="small muted">Customer details not available.</div>
                    @endif
                </td>
                <td style="text-align: right;">
                    <div class="blk-title">Ship To</div>
                    @if ($billing)
                        <div><strong>{{ $billing->name }}</strong></div>
                        <div class="small">{{ $billing->street1 }}@if ($billing->street2), {{ $billing->street2 }}@endif</div>
                        <div class="small">{{ $billing->city }}@if ($billing->state), {{ $billing->state->name }}@endif @if ($billing->zip){{ $billing->zip }}@endif</div>
                        @if ($billing->country)<div class="small">{{ $billing->country->name }}</div>@endif
                    @else
                        <div class="small muted">—</div>
                    @endif
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th style="width:52%;">Description</th>
                    <th class="num" style="width:12%;">Qty</th>
                    <th class="num" style="width:18%;">Unit Price</th>
                    <th class="num" style="width:18%;">Amount</th>
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
                <td class="sp">&nbsp;</td>
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
            <div class="h">Payment Details</div>
            <div class="small">{{ $bankAccount?->partner?->name ?? $company?->name }}</div>
            @if ($bankAccount?->bank?->name)
                <div class="small">Bank: {{ $bankAccount->bank->name }}</div>
            @endif
            @if ($bankAccount?->account_number)
                <div class="small">{{ $currency }} IBAN: {{ $bankAccount->account_number }}</div>
            @endif
        </div>

        <div class="foot">Invoice no. to be used as reference no. when making payment</div>
    </div>
</body>
</html>
