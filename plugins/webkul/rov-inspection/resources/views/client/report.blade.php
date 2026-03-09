<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $report->title }} – Inspection Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">

    {{-- Header Bar --}}
    <div class="sticky top-0 z-10 bg-white shadow-sm no-print">
        <div class="mx-auto max-w-4xl px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-8 w-8 text-blue-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
                </svg>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">ROV Inspection Report</p>
                    <p class="text-sm font-bold text-gray-900">{{ $report->title }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if ($report->client_can_print)
                    <button onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-200">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                        </svg>
                        Print
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-4xl px-4 py-8 space-y-8">

        {{-- Title Block --}}
        <div class="rounded-2xl bg-gradient-to-br from-blue-600 to-blue-800 p-8 text-white shadow-lg">
            <p class="text-sm font-medium uppercase tracking-widest text-blue-200 mb-2">Inspection Report</p>
            <h1 class="text-3xl font-bold mb-4">{{ $report->title }}</h1>
            <div class="flex flex-wrap gap-6 text-sm text-blue-100">
                @if ($project)
                    <div>
                        <span class="font-semibold text-white">Project:</span>
                        {{ $project->name }}
                    </div>
                    @if ($project->location)
                        <div>
                            <span class="font-semibold text-white">Location:</span>
                            {{ $project->location }}
                        </div>
                    @endif
                    @if ($project->start_date)
                        <div>
                            <span class="font-semibold text-white">Survey Period:</span>
                            {{ $project->start_date->format('d M Y') }}{{ $project->end_date ? ' – '.$project->end_date->format('d M Y') : '' }}
                        </div>
                    @endif
                @endif
                <div>
                    <span class="font-semibold text-white">Report Date:</span>
                    {{ $report->created_at->format('d M Y') }}
                </div>
            </div>
        </div>

        {{-- Status Badge --}}
        <div class="flex items-center gap-3">
            @php
                $statusColors = ['draft' => 'bg-gray-100 text-gray-700', 'final' => 'bg-blue-100 text-blue-700', 'shared' => 'bg-green-100 text-green-700'];
                $statusLabels = ['draft' => 'Draft', 'final' => 'Final', 'shared' => 'Shared'];
                $statusClass  = $statusColors[$report->status] ?? 'bg-gray-100 text-gray-700';
                $statusLabel  = $statusLabels[$report->status] ?? ucfirst($report->status);
            @endphp
            <span class="inline-flex items-center rounded-full {{ $statusClass }} px-3 py-1 text-sm font-semibold">
                {{ $statusLabel }}
            </span>
            @if ($report->shared_date)
                <span class="text-sm text-gray-500">Shared {{ $report->shared_date->format('d M Y') }}</span>
            @endif
        </div>

        {{-- Executive Summary --}}
        @if ($report->summary)
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h2 class="mb-3 text-lg font-bold text-gray-900">Executive Summary</h2>
                <p class="text-gray-700 leading-relaxed whitespace-pre-line">{{ $report->summary }}</p>
            </div>
        @endif

        {{-- Site Map --}}
        @if ($project?->site_map_path)
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h2 class="mb-4 text-lg font-bold text-gray-900">Site Map</h2>
                <div class="relative rounded-lg overflow-hidden border border-gray-200">
                    <img src="{{ asset('storage/'.$project->site_map_path) }}"
                         alt="Site Map"
                         class="w-full object-contain" />
                    @foreach ($points as $point)
                        @if ($point->x_coordinate !== null && $point->y_coordinate !== null)
                            @php
                                $dotColor = match($point->severity) {
                                    'low'      => '#22c55e',
                                    'medium'   => '#3b82f6',
                                    'high'     => '#f97316',
                                    'critical' => '#ef4444',
                                    default    => '#6b7280',
                                };
                            @endphp
                            <div class="absolute -translate-x-1/2 -translate-y-1/2"
                                 style="left: {{ $point->x_coordinate }}%; top: {{ $point->y_coordinate }}%;">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white text-xs font-bold text-white shadow-lg"
                                     style="background-color: {{ $dotColor }}">
                                    {{ $point->point_number }}
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
                {{-- Legend --}}
                <div class="mt-3 flex flex-wrap gap-4">
                    @foreach ([['low','#22c55e','Low'],['medium','#3b82f6','Medium'],['high','#f97316','High'],['critical','#ef4444','Critical']] as [$key,$color,$label])
                        <div class="flex items-center gap-1.5">
                            <div class="h-3 w-3 rounded-full border border-white shadow" style="background-color: {{ $color }}"></div>
                            <span class="text-xs text-gray-600">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Inspection Findings --}}
        @if ($points->count())
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h2 class="mb-4 text-lg font-bold text-gray-900">Inspection Findings ({{ $points->count() }})</h2>
                <div class="space-y-4">
                    @foreach ($points as $point)
                        @php
                            $severityColors = ['low' => 'border-green-400 bg-green-50', 'medium' => 'border-blue-400 bg-blue-50', 'high' => 'border-orange-400 bg-orange-50', 'critical' => 'border-red-500 bg-red-50'];
                            $dotColors      = ['low' => '#22c55e', 'medium' => '#3b82f6', 'high' => '#f97316', 'critical' => '#ef4444'];
                            $severityClass  = $severityColors[$point->severity] ?? 'border-gray-300 bg-gray-50';
                            $dotColor       = $dotColors[$point->severity] ?? '#6b7280';
                        @endphp
                        <div class="rounded-lg border-l-4 p-4 {{ $severityClass }}">
                            <div class="flex items-start gap-3">
                                <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold text-white shadow"
                                     style="background-color: {{ $dotColor }}">
                                    {{ $point->point_number }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <h3 class="font-semibold text-gray-900">{{ $point->label }}</h3>
                                        @if ($point->severity)
                                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold text-white"
                                                  style="background-color: {{ $dotColor }}">
                                                {{ ucfirst($point->severity) }}
                                            </span>
                                        @endif
                                        @if ($point->defect_type)
                                            <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-700">{{ $point->defect_type }}</span>
                                        @endif
                                    </div>
                                    @if ($point->description)
                                        <p class="text-sm text-gray-700 mb-2">{{ $point->description }}</p>
                                    @endif
                                    @if ($point->recommendations)
                                        <div class="mt-2 rounded bg-white/60 p-2">
                                            <p class="text-xs font-semibold text-gray-600 uppercase mb-1">Recommendation</p>
                                            <p class="text-sm text-gray-700">{{ $point->recommendations }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Full Report --}}
        @if ($report->full_report)
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h2 class="mb-4 text-lg font-bold text-gray-900">Detailed Report</h2>
                <div class="prose max-w-none text-gray-700">
                    {!! $report->full_report !!}
                </div>
            </div>
        @endif

        {{-- Conclusions --}}
        @if ($report->conclusions)
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h2 class="mb-3 text-lg font-bold text-gray-900">Conclusions</h2>
                <p class="text-gray-700 leading-relaxed whitespace-pre-line">{{ $report->conclusions }}</p>
            </div>
        @endif

        {{-- Recommendations --}}
        @if ($report->recommendations)
            <div class="rounded-xl bg-amber-50 p-6 shadow-sm ring-1 ring-amber-200">
                <h2 class="mb-3 text-lg font-bold text-amber-900">Recommendations</h2>
                <p class="text-amber-800 leading-relaxed whitespace-pre-line">{{ $report->recommendations }}</p>
            </div>
        @endif

        {{-- Footer --}}
        <div class="border-t border-gray-200 pt-6 text-center text-xs text-gray-400">
            <p>This report was generated via ROV Inspection module. Confidential – for authorized recipients only.</p>
            <p class="mt-1">Report ID: {{ $report->shared_link_hash }}</p>
        </div>
    </div>
</body>
</html>
