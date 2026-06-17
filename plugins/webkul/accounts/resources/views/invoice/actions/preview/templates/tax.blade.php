<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
        .invoice-sheet {
            background: #ffffff;
            color: #222222;
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            padding: 28px;
            margin: 0 auto;
        }
        .invoice-sheet * { color: inherit; }

        .invoice-sheet .top-bar {
            height: 8px;
            background: #1f2d5c;
            margin-bottom: 18px;
        }

        .invoice-sheet table { border-collapse: collapse; }

        .invoice-sheet .header-table { width: 100%; }
        .invoice-sheet .header-table > tbody > tr > td { vertical-align: top; }
        .invoice-sheet .logo-cell { width: 55%; }
        .invoice-sheet .logo-cell img { max-height: 90px; max-width: 220px; }
        .invoice-sheet .meta-cell { width: 45%; text-align: right; }

        .invoice-sheet .tax-title {
            font-size: 22px;
            font-weight: bold;
            color: #1f2d5c;
            margin-bottom: 6px;
        }

        .invoice-sheet .meta-box { width: 100%; border: 1px solid #1f2d5c; }
        .invoice-sheet .meta-box td {
            border: 1px solid #ffffff;
            padding: 5px 8px;
            font-size: 11px;
        }
        .invoice-sheet .meta-box .label {
            background: #2f5c9e;
            color: #ffffff;
            text-align: left;
            width: 55%;
        }
        .invoice-sheet .meta-box .value { background: #ffffff; color: #222222; text-align: right; }
        .invoice-sheet .meta-box .validity { color: #d12f2f; font-weight: bold; }

        .invoice-sheet .company-name {
            font-size: 17px;
            font-weight: bold;
            color: #1f2d5c;
            margin: 16px 0 4px;
        }
        .invoice-sheet .company-block div { font-size: 11.5px; }

        .invoice-sheet .address-table { width: 100%; margin-top: 18px; }
        .invoice-sheet .address-table > tbody > tr > td { vertical-align: top; width: 50%; }
        .invoice-sheet .address-cell-left { padding-right: 12px; }
        .invoice-sheet .address-cell-right { padding-left: 12px; }
        .invoice-sheet .address-label { font-weight: bold; margin-bottom: 4px; }
        .invoice-sheet .address-box {
            border: 1px solid #b9c2d0;
            min-height: 70px;
            padding: 8px 10px;
            font-size: 11.5px;
        }

        .invoice-sheet .items-table { width: 100%; margin: 22px 0 10px; }
        .invoice-sheet .items-table th {
            background: #3e6db5;
            color: #ffffff;
            padding: 8px 10px;
            text-align: left;
            font-size: 11.5px;
            border: 1px solid #3e6db5;
        }
        .invoice-sheet .items-table td {
            padding: 7px 10px;
            border: 1px solid #cfd6e0;
            font-size: 11.5px;
        }
        .invoice-sheet .items-table .num { text-align: right; }

        .invoice-sheet .totals-table { width: 100%; margin-top: 6px; }
        .invoice-sheet .totals-table > tbody > tr > td { vertical-align: top; }
        .invoice-sheet .totals-spacer { width: 55%; }
        .invoice-sheet .totals-box { width: 45%; }
        .invoice-sheet .totals-box table { width: 100%; }
        .invoice-sheet .totals-box td { padding: 6px 10px; font-size: 12px; }
        .invoice-sheet .totals-box .t-label { text-align: left; }
        .invoice-sheet .totals-box .t-value { text-align: right; }
        .invoice-sheet .totals-box .grand td {
            border: 1px solid #b9c2d0;
            font-weight: bold;
            font-size: 13px;
        }

        .invoice-sheet .payment { margin-top: 28px; width: 60%; border: 1px solid #b9c2d0; }
        .invoice-sheet .payment .pay-head {
            background: #1f2d5c;
            color: #ffffff;
            font-weight: bold;
            padding: 6px 10px;
            font-size: 12px;
        }
        .invoice-sheet .payment .pay-body { padding: 8px 10px; font-size: 11.5px; }
        .invoice-sheet .payment .iban { color: #2f5c9e; }

        .invoice-sheet .footer-note { margin-top: 26px; font-size: 11px; color: #444444; }
    </style>
</head>

<body>
    <div class="invoice-sheet">
        <div class="top-bar"></div>

        <!-- Header: logo + Tax Invoice meta box -->
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
                    <div class="tax-title">Tax Invoice</div>
                    <table class="meta-box">
                        <tr>
                            <td class="label">Tax Inv No.</td>
                            <td class="value">{{ $record->name }}</td>
                        </tr>
                        <tr>
                            <td class="label">Date</td>
                            <td class="value">{{ $fmtDate($record->invoice_date) }}</td>
                        </tr>
                        <tr>
                            <td class="label">Amount Due</td>
                            <td class="value">{{ money($record->amount_residual, $currency) }}</td>
                        </tr>
                        @if ($record->invoicePaymentTerm)
                            <tr>
                                <td class="label">Tax Inv Validity</td>
                                <td class="value validity">{{ $record->invoicePaymentTerm->name }}</td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        <!-- Company details -->
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

        <!-- Address boxes -->
        <table class="address-table">
            <tr>
                <td class="address-cell-left">
                    <div class="address-label">Tax Invoice To:</div>
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
        </table>

        <!-- Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th class="num" style="width: 12%;">Qty</th>
                    <th class="num" style="width: 19%;">Unit Price</th>
                    <th class="num" style="width: 19%;">Total Price</th>
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

        <!-- Totals -->
        <table class="totals-table">
            <tr>
                <td class="totals-spacer">&nbsp;</td>
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

        <!-- Payment details -->
        <table class="payment">
            <tr>
                <td class="pay-head">Payment Details</td>
            </tr>
            <tr>
                <td class="pay-body">
                    <div>{{ $bankAccount?->partner?->name ?? $company?->name }}</div>
                    @if ($bankAccount?->bank?->name)
                        <div>Bank Account: {{ $bankAccount->bank->name }}</div>
                    @endif
                    @if ($bankAccount?->account_number)
                        <div class="iban">{{ $currency }} account IBAN: {{ $bankAccount->account_number }}</div>
                    @endif
                </td>
            </tr>
        </table>

        <div class="footer-note">
            Invoice no. to be used as reference no. when making payment
        </div>
    </div>
</body>
</html>
