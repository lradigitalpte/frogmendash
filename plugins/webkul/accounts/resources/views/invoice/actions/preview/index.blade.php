@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Carbon;

    // ---- Shared data prepared once for whichever template is selected ----
    $company  = $record->company;
    $partner  = $record->partner;
    $billing  = $record->partnerShipping ?? $record->partner;
    $currency = $record->currency->name ?? 'AED';

    // Embed the logo as a base64 data URI so it renders in both the
    // browser preview (modal) and the dompdf-generated PDF.
    // The "Company Logo" in the UI is stored on the company's partner avatar;
    // fall back to the company's own logo column if present.
    $logoPath = $company?->partner?->avatar ?: $company?->logo;
    $logoData = null;
    if ($logoPath && Storage::disk('public')->exists($logoPath)) {
        $ext = pathinfo($logoPath, PATHINFO_EXTENSION) ?: 'png';
        $logoData = 'data:image/' . $ext . ';base64,' . base64_encode(Storage::disk('public')->get($logoPath));
    }

    // Effective VAT rate (falls back to 5% when there is nothing to derive from).
    $vatRate = $record->amount_untaxed > 0
        ? rtrim(rtrim(number_format($record->amount_tax / $record->amount_untaxed * 100, 2), '0'), '.')
        : 5;

    $fmtDate = fn ($value) => $value ? Carbon::parse($value)->format('d/m/Y') : '';

    // Payment Details: use the invoice's recipient bank if one was chosen,
    // otherwise fall back to the company's own bank account so the section
    // always shows where to pay.
    $bankAccount = $record->partnerBank;
    if (! $bankAccount && $company?->partner) {
        $bankAccount = $company->partner->bankAccounts()->first();
    }

    // ---- Resolve the globally-selected invoice template ----
    $templates = [
        'tax'     => 'accounts::invoice.actions.preview.templates.tax',
        'classic' => 'accounts::invoice.actions.preview.templates.classic',
        'minimal' => 'accounts::invoice.actions.preview.templates.minimal',
    ];

    try {
        $selected = app(\Webkul\Account\Settings\CustomerInvoiceSettings::class)->invoice_template ?? 'tax';
    } catch (\Throwable $e) {
        $selected = 'tax';
    }

    $chosen = $templates[$selected] ?? $templates['tax'];
@endphp

@include($chosen)
