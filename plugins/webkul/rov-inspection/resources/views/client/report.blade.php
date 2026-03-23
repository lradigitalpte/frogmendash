<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $report->title }} – ROV Inspection Report</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { 900: '#0f172a', 800: '#1e293b', 700: '#334155', 600: '#475569' }
                    }
                }
            }
        }
    </script>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        .tab-btn {
            display: flex; align-items: center; gap: 6px;
            padding: 12px 16px;
            font-size: 13px; font-weight: 500;
            color: #6b7280;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
            transition: color .15s, border-color .15s;
        }
        .tab-btn:hover { color: #1d4ed8; border-color: #bfdbfe; }
        .tab-btn.active { color: #1d4ed8; border-bottom-color: #2563eb; font-weight: 600; }

        .card { background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.06); border: 1px solid #f1f5f9; }

        .stat-pill {
            display: flex; flex-direction: column; align-items: center;
            padding: 16px 12px; border-radius: 14px; min-width: 90px;
        }

        .severity-badge {
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 50%; width: 22px; height: 22px;
            font-size: 10px; font-weight: 700; color: white;
        }

        @media print {
            .no-print { display: none !important; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
        }

        /* Custom scrollbar for tab bar */
        .tab-bar::-webkit-scrollbar { height: 0; }

        /* Smooth section transitions */
        [x-show] { transition: opacity .15s ease; }

        /* Leaflet z-index fix */
        .leaflet-pane { z-index: 1 !important; }
        .leaflet-control { z-index: 2 !important; }
    </style>
</head>

<body class="bg-slate-50 text-gray-900 min-h-screen"
      x-data="{
          activeTab: 'home',
          activePinId: null,
          activeStructureIdx: 0,
          activeViewIdx: 0,
          planViewOpen: false,
          lightboxUrl: null,
          summaryExpanded: false,
          _mapInitialized: false,

          openLightbox(url) { this.lightboxUrl = url; },
          closeLightbox() { this.lightboxUrl = null; },

          initMap() {
              if (this._mapInitialized) return;
              @if ($project && $project->latitude && $project->longitude)
              this._mapInitialized = true;
              const map = L.map('satellite-map', { zoomControl: true }).setView([{{ $project->latitude }}, {{ $project->longitude }}], 15);
              L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                  attribution: '© OpenStreetMap contributors', maxZoom: 19,
              }).addTo(map);
              L.marker([{{ $project->latitude }}, {{ $project->longitude }}])
                  .addTo(map)
                  .bindPopup('<strong>{{ addslashes($project->name) }}</strong>{{ $project->location ? "<br><span style=\'color:#64748b;font-size:12px\'>" . addslashes($project->location) . "</span>" : "" }}')
                  .openPopup();
              @endif
          },
      }"
      x-init="$nextTick(() => initMap())"
>

{{-- ═══════════════════════ HEADER ═══════════════════════ --}}
<header class="no-print" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #1e40af 100%);">
    <div class="mx-auto max-w-7xl px-4 py-3">
        <div class="flex items-center justify-between gap-4">

            {{-- Logo + title --}}
            <div class="flex items-center gap-3 min-w-0">
                <img src="{{ asset('images/logo.png') }}"
                     alt="Frogmen Technologies"
                     class="h-9 shrink-0 brightness-0 invert object-contain" />
                <div class="border-l border-white/20 pl-3 min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-blue-300">ROV Inspection Report</p>
                    <p class="text-sm font-bold text-white leading-tight truncate">{{ $report->title }}</p>
                </div>
            </div>

            {{-- Centre meta --}}
            <div class="hidden lg:flex items-center gap-4 text-xs text-slate-300 shrink-0">
                @if ($project?->customer?->name)
                    <span class="flex items-center gap-1">
                        <svg class="h-3.5 w-3.5 text-blue-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-7 9a7 7 0 1 1 14 0H3Z"/></svg>
                        {{ $project->customer->name }}
                    </span>
                @endif
                @if ($project?->location)
                    <span class="flex items-center gap-1">
                        <svg class="h-3.5 w-3.5 text-blue-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="m9.69 18.933.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 15.227 17 12.457 17 9A7 7 0 1 0 3 9c0 3.457 1.698 6.227 3.354 7.985a12.822 12.822 0 0 0 3.033 2.2l.018.008.006.003ZM10 11.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" clip-rule="evenodd"/></svg>
                        {{ $project->location }}
                    </span>
                @endif
                @if ($project?->start_date)
                    <span class="flex items-center gap-1">
                        <svg class="h-3.5 w-3.5 text-blue-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd"/></svg>
                        {{ $project->start_date->format('d M Y') }}{{ $project->end_date ? ' – '.$project->end_date->format('d M Y') : '' }}
                    </span>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2 shrink-0">
                @if ($project?->plan_view_path)
                    <button @click="planViewOpen = true"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 hover:bg-white/20 px-3 py-1.5 text-xs font-medium text-white transition-colors border border-white/20">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M1 2.75A.75.75 0 0 1 1.75 2h10.5a.75.75 0 0 1 0 1.5H12v13.75a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1-.75-.75v-2.5a.75.75 0 0 0-.75-.75h-2.5a.75.75 0 0 0-.75.75v2.5a.75.75 0 0 1-.75.75H3a.75.75 0 0 1-.75-.75V3.5h-.5A.75.75 0 0 1 1 2.75Z" clip-rule="evenodd"/></svg>
                        Plan View
                    </button>
                @endif
                @if ($report->client_can_download)
                    <button onclick="window.print()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-500 hover:bg-blue-400 px-3 py-1.5 text-xs font-semibold text-white transition-colors">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/></svg>
                        Download
                    </button>
                @endif
            </div>

        </div>
    </div>
</header>

{{-- ═══════════════════════ TAB BAR ═══════════════════════ --}}
<nav class="sticky top-0 z-40 bg-white shadow-sm no-print" style="border-bottom: 1px solid #e2e8f0;">
    <div class="mx-auto max-w-7xl px-4">
        <div class="flex tab-bar overflow-x-auto">
            @php
                $tabs = [
                    ['id'=>'home',         'label'=>'Home',             'icon'=>'M10.707 2.293a1 1 0 0 0-1.414 0l-7 7a1 1 0 0 0 1.414 1.414L4 10.414V17a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-6.586l.293.293a1 1 0 0 0 1.414-1.414l-7-7Z'],
                    ['id'=>'images',       'label'=>'Inspection Images', 'icon'=>'M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z'],
                    ['id'=>'map',          'label'=>'Inspection Map',   'icon'=>'M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z'],
                    ['id'=>'observations', 'label'=>'Observations',     'icon'=>'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z'],
                    ['id'=>'data',         'label'=>'Inspection Data',  'icon'=>'m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z'],
                    ['id'=>'conclusions',  'label'=>'Conclusions',      'icon'=>'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                ];
            @endphp
            @foreach ($tabs as $tab)
                <button @click="activeTab = '{{ $tab['id'] }}'; @if ($tab['id'] === 'home') $nextTick(() => initMap()); @endif"
                        :class="activeTab === '{{ $tab['id'] }}' ? 'active' : ''"
                        class="tab-btn">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}"/></svg>
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>
    </div>
</nav>

{{-- ═══════════════════════ PLAN VIEW MODAL ═══════════════════════ --}}
<div x-show="planViewOpen" x-cloak
     class="no-print fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4"
     @click.self="planViewOpen = false">
    <div class="relative max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <h2 class="font-bold text-gray-900">Plan View</h2>
            <button @click="planViewOpen = false" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
            </button>
        </div>
        <div class="overflow-auto p-4">
            @if ($project?->plan_view_path)
                <img src="{{ asset('storage/'.$project->plan_view_path) }}" alt="Plan View" class="mx-auto max-w-full rounded-lg" />
            @endif
        </div>
    </div>
</div>

{{-- ═══════════════════════ LIGHTBOX ═══════════════════════ --}}
<div x-show="lightboxUrl" x-cloak
     class="no-print fixed inset-0 z-50 flex items-center justify-center bg-black/90"
     @click.self="closeLightbox()">
    <img :src="lightboxUrl" class="max-h-[90vh] max-w-[90vw] rounded-xl shadow-2xl object-contain" />
    <button @click="closeLightbox()"
            class="absolute right-5 top-5 rounded-full bg-white/10 p-2 text-white hover:bg-white/25 transition-colors">
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
    </button>
</div>

{{-- ═══════════════════════ MAIN CONTENT ═══════════════════════ --}}
<main class="mx-auto max-w-7xl px-4 py-6 space-y-6">

    {{-- ─────────────────── TAB 1: HOME ─────────────────── --}}
    <div x-show="activeTab === 'home'" x-cloak>

        {{-- Top row: map + project card --}}
        <div class="grid gap-5 lg:grid-cols-5">

            {{-- Map: takes 3 cols --}}
            <div class="lg:col-span-3 card overflow-hidden">
                <div id="satellite-map" class="h-72 w-full bg-slate-100"
                     x-init="$nextTick(() => { if (activeTab === 'home') initMap(); })">
                    @if (! ($project?->latitude && $project?->longitude))
                        <div class="flex h-full flex-col items-center justify-center text-slate-400">
                            <svg class="h-10 w-10 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                            <p class="text-sm">No GPS coordinates set</p>
                        </div>
                    @endif
                </div>
                <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border-t border-slate-100">
                    <p class="text-xs text-slate-500 font-medium">Project Location</p>
                    @if ($project?->latitude && $project?->longitude)
                        <p class="text-xs text-slate-400 font-mono">{{ number_format($project->latitude, 5) }}, {{ number_format($project->longitude, 5) }}</p>
                    @endif
                </div>
            </div>

            {{-- Project card: takes 2 cols --}}
            <div class="lg:col-span-2 card p-6 flex flex-col justify-between">
                {{-- Customer logo if available --}}
                @if ($project?->customer?->image_url ?? false)
                    <img src="{{ $project->customer->image_url }}"
                         alt="{{ $project->customer->name }}"
                         class="mb-4 h-10 object-contain self-start" />
                @endif

                <div class="space-y-1">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-blue-500">Underwater Visual Inspection</p>
                    <h1 class="text-xl font-bold text-slate-900 leading-tight">{{ $project?->name ?? $report->title }}</h1>
                    @if ($project?->customer?->name)
                        <p class="text-sm text-slate-500 flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-7 9a7 7 0 1 1 14 0H3Z"/></svg>
                            {{ $project->customer->name }}
                        </p>
                    @endif
                    @if ($project?->location)
                        <p class="text-sm text-slate-500 flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="m7.539 14.841.003.003.002.002a.755.755 0 0 0 .912 0l.002-.002.003-.003.012-.009a5.57 5.57 0 0 0 .19-.153 15.588 15.588 0 0 0 2.046-2.082c1.101-1.362 2.291-3.342 2.291-5.597A5 5 0 0 0 3 7c0 2.255 1.19 4.235 2.292 5.597a15.591 15.591 0 0 0 2.046 2.082 8.916 8.916 0 0 0 .189.153l.012.01ZM8 8.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd"/></svg>
                            {{ $project->location }}
                        </p>
                    @endif
                    @if ($project?->start_date)
                        <p class="text-sm text-slate-500 flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd"/></svg>
                            {{ $project->start_date->format('d M Y') }}{{ $project->end_date ? ' – '.$project->end_date->format('d M Y') : '' }}
                        </p>
                    @endif
                </div>

                {{-- Severity counts --}}
                <div class="mt-5 grid grid-cols-3 gap-2">
                    <div class="stat-pill bg-red-50 rounded-xl">
                        <p class="text-3xl font-black text-red-500">{{ $severityCounts['major'] }}</p>
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-red-400 mt-0.5">Major</p>
                    </div>
                    <div class="stat-pill bg-orange-50 rounded-xl">
                        <p class="text-3xl font-black text-orange-500">{{ $severityCounts['moderate'] }}</p>
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-orange-400 mt-0.5">Moderate</p>
                    </div>
                    <div class="stat-pill bg-yellow-50 rounded-xl">
                        <p class="text-3xl font-black text-yellow-500">{{ $severityCounts['minor'] }}</p>
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-yellow-500 mt-0.5">Minor</p>
                    </div>
                </div>

                @if ($report->client_can_download)
                    <button onclick="window.print()"
                            class="mt-4 w-full rounded-xl bg-blue-600 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 transition-colors flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/></svg>
                        Download Executive Report
                    </button>
                @endif
            </div>
        </div>

        {{-- Report Summary --}}
        @if ($report->summary || $report->full_report)
            <div class="card overflow-hidden">
                <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600">
                        <svg class="h-4 w-4 text-white" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 0 1 2-2h4.586A2 2 0 0 1 12 2.586L15.414 6A2 2 0 0 1 16 7.414V16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4Zm2 6a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H7a1 1 0 0 1-1-1Zm1 3a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H7Z" clip-rule="evenodd"/></svg>
                    </div>
                    <h2 class="font-semibold text-slate-900">Report Summary</h2>
                </div>
                <div class="px-5 py-4">
                    @if ($report->summary)
                        <p class="text-sm text-slate-700 leading-relaxed">{{ $report->summary }}</p>
                    @endif

                    @if ($report->full_report)
                        <div x-data="{ open: false }">
                            <button @click="open = !open"
                                    class="mt-3 flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-500 transition-colors">
                                <svg class="h-3.5 w-3.5 transition-transform" :class="open ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                                <span x-text="open ? 'Hide full report' : 'Read full report'"></span>
                            </button>
                            <div x-show="open" x-cloak class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-700 leading-relaxed whitespace-pre-line">
                                {{ $report->full_report }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Structures overview --}}
        @if ($project && $project->structures->count())
            <div class="card overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900">Structures Inspected</h2>
                    <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">{{ $project->structures->count() }}</span>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach ($project->structures as $structure)
                        @php
                            $structureMajor = 0; $structureMod = 0; $structureMinor = 0;
                            foreach ($structure->views as $v) {
                                foreach ($v->points as $p) {
                                    $s = strtolower($p->severity ?? '');
                                    if ($s === 'major') $structureMajor++;
                                    elseif ($s === 'moderate') $structureMod++;
                                    elseif ($s === 'minor') $structureMinor++;
                                }
                            }
                        @endphp
                        <div class="flex items-center gap-3 px-5 py-3">
                            <div class="h-8 w-8 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1a6 6 0 0 0-3.815 10.631C7.237 12.5 8 13.443 8 14.456v.644a.75.75 0 0 0 .572.729 6.016 6.016 0 0 0 2.856 0A.75.75 0 0 0 12 15.1v-.644c0-1.013.762-1.957 1.815-2.825A6 6 0 0 0 10 1ZM8.863 17.414a.75.75 0 0 0-.226 1.483 9.066 9.066 0 0 0 2.726 0 .75.75 0 0 0-.226-1.483 7.553 7.553 0 0 1-2.274 0Z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-800">{{ $structure->name }}</p>
                                @if ($structure->description)
                                    <p class="text-xs text-slate-400 truncate">{{ $structure->description }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                @if ($structureMajor) <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-600">{{ $structureMajor }} major</span> @endif
                                @if ($structureMod) <span class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-semibold text-orange-600">{{ $structureMod }} mod</span> @endif
                                @if ($structureMinor) <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-semibold text-yellow-600">{{ $structureMinor }} minor</span> @endif
                                @if (! $structureMajor && ! $structureMod && ! $structureMinor)
                                    <span class="text-xs text-slate-400">No observations</span>
                                @endif
                                <span class="text-xs text-slate-300">{{ $structure->views->count() }} view(s)</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>{{-- /home --}}

    {{-- ─────────────────── TAB 2: INSPECTION IMAGES ─────────────────── --}}
    <div x-show="activeTab === 'images'" x-cloak>
        @php $photosExist = $project && $project->structures->filter(fn($s) => $s->photo_path)->count(); @endphp
        @if ($photosExist)
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($project->structures->filter(fn($s) => $s->photo_path) as $structure)
                    <div class="card overflow-hidden group cursor-pointer"
                         @click="openLightbox('{{ asset('storage/'.$structure->photo_path) }}')">
                        <div class="overflow-hidden aspect-[4/3]">
                            <img src="{{ asset('storage/'.$structure->photo_path) }}"
                                 alt="{{ $structure->name }}"
                                 class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                        </div>
                        <div class="px-3 py-2.5">
                            <p class="text-xs font-semibold text-slate-800">{{ $structure->name }}</p>
                            @if ($structure->description)
                                <p class="mt-0.5 text-xs text-slate-400 line-clamp-1">{{ $structure->description }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="card flex flex-col items-center justify-center py-20 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                <p class="mt-3 text-sm font-medium text-slate-500">No structure photos uploaded</p>
                <p class="mt-1 text-xs text-slate-400">Structure surface photos will appear here once uploaded.</p>
            </div>
        @endif
    </div>

    {{-- ─────────────────── TAB 3: INSPECTION MAP ─────────────────── --}}
    <div x-show="activeTab === 'map'" x-cloak>
        @if ($project && $project->structures->count())
            {{-- Structure pill tabs --}}
            <div class="mb-4 flex flex-wrap gap-2">
                @foreach ($project->structures as $sIdx => $structure)
                    <button @click="activeStructureIdx = {{ $sIdx }}; activeViewIdx = 0; activePinId = null"
                            :class="activeStructureIdx === {{ $sIdx }}
                                ? 'bg-blue-600 text-white shadow-sm'
                                : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'"
                            class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors">
                        {{ $structure->name }}
                    </button>
                @endforeach
            </div>

            @foreach ($project->structures as $sIdx => $structure)
                <div x-show="activeStructureIdx === {{ $sIdx }}" x-cloak>

                    {{-- View sub-tabs --}}
                    @if ($structure->views->count() > 1)
                        <div class="mb-3 flex gap-2 flex-wrap">
                            @foreach ($structure->views as $vIdx => $view)
                                <button @click="activeViewIdx = {{ $vIdx }}; activePinId = null"
                                        :class="activeViewIdx === {{ $vIdx }}
                                            ? 'bg-slate-900 text-white'
                                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors">
                                    {{ $view->name }}
                                    <span class="ml-1 opacity-60 text-[10px]">{{ ucfirst($view->view_type) }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @foreach ($structure->views as $vIdx => $view)
                        <div x-show="activeViewIdx === {{ $vIdx }}" x-cloak>
                            <div class="flex flex-col gap-4 xl:flex-row">

                                {{-- Annotated diagram --}}
                                <div class="min-w-0 flex-1 card overflow-hidden">
                                    @if ($structure->diagram_path)
                                        <div class="relative select-none bg-slate-900"
                                             x-data="{ imageLoaded: false }">
                                            <img src="{{ asset('storage/'.$structure->diagram_path) }}"
                                                 alt="{{ $structure->name }}"
                                                 @load="imageLoaded = true"
                                                 class="w-full object-contain" />

                                            @foreach ($view->points as $point)
                                                @php
                                                    $pinColor = match(strtolower($point->severity ?? '')) {
                                                        'major'    => '#ef4444',
                                                        'moderate' => '#f97316',
                                                        'minor'    => '#eab308',
                                                        default    => '#6b7280',
                                                    };
                                                @endphp
                                                <div class="absolute -translate-x-1/2 -translate-y-full cursor-pointer group z-10"
                                                     style="left: {{ $point->x_coordinate }}%; top: {{ $point->y_coordinate }}%;"
                                                     @click.stop="activePinId = activePinId === {{ $point->id }} ? null : {{ $point->id }}">
                                                    <div class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-white shadow-lg transition-all group-hover:scale-125"
                                                         :class="activePinId === {{ $point->id }} ? 'scale-125 ring-2 ring-white ring-offset-1' : ''"
                                                         style="background-color: {{ $pinColor }}">
                                                        <span class="text-[10px] font-bold text-white leading-none">{{ $point->observation_id ?? $point->point_number }}</span>
                                                    </div>
                                                    <div class="mx-auto h-2 w-0.5 opacity-60" style="background-color: {{ $pinColor }}"></div>
                                                </div>
                                            @endforeach
                                        </div>

                                        {{-- Legend --}}
                                        <div class="flex flex-wrap items-center gap-4 border-t border-slate-100 bg-slate-50 px-4 py-2.5">
                                            @foreach ([['major','#ef4444'],['moderate','#f97316'],['minor','#eab308']] as [$s,$c])
                                                <div class="flex items-center gap-1.5">
                                                    <div class="h-2.5 w-2.5 rounded-full" style="background-color:{{ $c }}"></div>
                                                    <span class="text-xs capitalize text-slate-500">{{ $s }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center py-20 text-slate-400">
                                            <p class="text-sm">No diagram uploaded for {{ $structure->name }}</p>
                                        </div>
                                    @endif
                                </div>

                                {{-- Observations panel --}}
                                <div class="w-full xl:w-[400px] shrink-0">
                                    <div class="card overflow-hidden">
                                        <div class="border-b border-slate-100 bg-slate-50 px-4 py-3">
                                            <p class="text-sm font-semibold text-slate-900">{{ $view->name }}</p>
                                            <p class="text-xs text-slate-400 mt-0.5">
                                                {{ $view->points->count() }} observation{{ $view->points->count() !== 1 ? 's' : '' }}
                                                · Click a row or pin to expand media
                                            </p>
                                        </div>

                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <thead>
                                                    <tr class="border-b border-slate-100 bg-white text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                                        <th class="px-3 py-2 text-left">ID</th>
                                                        <th class="px-3 py-2 text-left">Type / Description</th>
                                                        <th class="px-3 py-2 text-right">Depth</th>
                                                        <th class="px-3 py-2 text-center w-8"></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-50">
                                                    @forelse ($view->points as $point)
                                                        @php
                                                            $pc = match(strtolower($point->severity ?? '')) {
                                                                'major' => '#ef4444', 'moderate' => '#f97316',
                                                                'minor' => '#eab308', default => '#6b7280'
                                                            };
                                                        @endphp
                                                        <tr @click="activePinId = activePinId === {{ $point->id }} ? null : {{ $point->id }}"
                                                            :class="activePinId === {{ $point->id }} ? 'bg-blue-50' : 'hover:bg-slate-50'"
                                                            class="cursor-pointer transition-colors">
                                                            <td class="px-3 py-2.5">
                                                                <span class="severity-badge text-white"
                                                                      style="background-color: {{ $pc }}">
                                                                    {{ $point->observation_id ?? $point->point_number }}
                                                                </span>
                                                            </td>
                                                            <td class="px-3 py-2.5">
                                                                <p class="font-medium text-slate-800 text-xs">{{ $point->finding_type ?? '—' }}</p>
                                                                @if ($point->description)
                                                                    <p class="text-[11px] text-slate-400 line-clamp-1">{{ $point->description }}</p>
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2.5 text-right text-xs text-slate-500">{{ $point->depth_m ? $point->depth_m.'m' : '—' }}</td>
                                                            <td class="px-3 py-2.5 text-center">
                                                                @if ($point->media->count())
                                                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-semibold text-blue-600">
                                                                        {{ $point->media->count() }}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                        </tr>

                                                        {{-- Inline media panel --}}
                                                        <tr x-show="activePinId === {{ $point->id }}" x-cloak class="bg-blue-50">
                                                            <td colspan="4" class="px-4 py-3">
                                                                @if ($point->media->count())
                                                                    <p class="mb-2.5 text-xs font-bold text-blue-700 uppercase tracking-wide">
                                                                        Media · {{ $point->observation_id ?? 'Point '.$point->point_number }}
                                                                    </p>
                                                                    <div class="space-y-2">
                                                                        @foreach ($point->media as $media)
                                                                            <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                                                                                @if ($media->isVideo())
                                                                                    <video controls class="w-full max-h-52 rounded-t-xl bg-black" src="{{ $media->url }}" preload="metadata"></video>
                                                                                @else
                                                                                    <img src="{{ $media->url }}" alt="{{ $media->file_name }}"
                                                                                         @click="openLightbox('{{ $media->url }}')"
                                                                                         class="w-full max-h-52 cursor-pointer object-contain rounded-t-xl hover:opacity-90" />
                                                                                @endif
                                                                                <div class="flex items-center justify-between px-3 py-2 border-t border-slate-100">
                                                                                    <p class="text-xs text-slate-600 font-medium truncate">{{ $media->file_name }}</p>
                                                                                    <a href="{{ $media->url }}" download="{{ $media->file_name }}"
                                                                                       class="ml-2 shrink-0 text-xs font-semibold text-blue-600 hover:text-blue-500">↓</a>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <p class="text-xs text-slate-400 italic">No media linked to this observation.</p>
                                                                @endif
                                                                @if ($point->recommendations)
                                                                    <div class="mt-2.5 rounded-lg bg-orange-50 border border-orange-100 px-3 py-2 text-xs text-orange-700">
                                                                        <strong>Recommendation:</strong> {{ $point->recommendations }}
                                                                    </div>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-400">No observations for this view.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @else
            <div class="card flex flex-col items-center justify-center py-20 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z"/></svg>
                <p class="mt-3 text-sm font-medium text-slate-500">No inspection structures added yet.</p>
            </div>
        @endif
    </div>

    {{-- ─────────────────── TAB 4: OBSERVATIONS ─────────────────── --}}
    <div x-show="activeTab === 'observations'" x-cloak>
        @php
            $hasObs = false;
            if ($project) {
                foreach ($project->structures as $st) {
                    foreach ($st->views as $v) {
                        if ($v->points->count()) { $hasObs = true; break 2; }
                    }
                }
            }
        @endphp
        @if ($hasObs)
            @foreach ($project->structures as $structure)
                @foreach ($structure->views as $view)
                    @if ($view->points->count())
                        <div class="mb-5 card overflow-hidden">
                            {{-- Section header --}}
                            <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50 px-5 py-3">
                                <span class="rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-bold text-slate-700">{{ $structure->name }}</span>
                                <svg class="h-3.5 w-3.5 text-slate-300" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                                <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">{{ $view->name }}</span>
                                <span class="text-xs text-slate-400 capitalize">{{ $view->view_type }}</span>
                                <span class="ml-auto text-xs text-slate-400">{{ $view->points->count() }} observation{{ $view->points->count() !== 1 ? 's' : '' }}</span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-100 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                            <th class="bg-red-50 px-4 py-2.5 text-left text-red-500">Defect ID</th>
                                            <th class="px-4 py-2.5 text-left">Type / Description</th>
                                            <th class="px-4 py-2.5 text-left">Dive Location</th>
                                            <th class="px-4 py-2.5 text-right">Depth</th>
                                            <th class="px-4 py-2.5 text-center">Media</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        @foreach ($view->points as $point)
                                            @php
                                                $pc = match(strtolower($point->severity ?? '')) {
                                                    'major' => '#ef4444', 'moderate' => '#f97316',
                                                    'minor' => '#eab308', default => '#6b7280'
                                                };
                                            @endphp
                                            <tr @click="activePinId = activePinId === {{ $point->id }} ? null : {{ $point->id }}"
                                                :class="activePinId === {{ $point->id }} ? 'bg-blue-50' : 'hover:bg-slate-50'"
                                                class="cursor-pointer transition-colors">
                                                <td class="px-4 py-3">
                                                    <span class="font-black text-sm" style="color: {{ $pc }}">
                                                        {{ $point->observation_id ?? 'O'.$loop->iteration }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <p class="font-semibold text-slate-800">{{ $point->finding_type ?? '—' }}</p>
                                                    @if ($point->description)
                                                        <p class="text-xs text-slate-400 mt-0.5">{{ $point->description }}</p>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-slate-500 text-xs">{{ $point->dive_location ?? '—' }}</td>
                                                <td class="px-4 py-3 text-right text-xs text-slate-500">{{ $point->depth_m ? $point->depth_m.' m' : '—' }}</td>
                                                <td class="px-4 py-3 text-center">
                                                    @if ($point->media->count())
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-600">
                                                            <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path d="M3.25 4A2.25 2.25 0 0 0 1 6.25v7.5A2.25 2.25 0 0 0 3.25 16h7.5A2.25 2.25 0 0 0 13 13.75v-7.5A2.25 2.25 0 0 0 10.75 4h-7.5ZM19 4.75a.75.75 0 0 0-1.28-.53l-3 3a.75.75 0 0 0-.22.53v4.5c0 .199.079.39.22.53l3 3a.75.75 0 0 0 1.28-.53V4.75Z"/></svg>
                                                            {{ $point->media->count() }}
                                                        </span>
                                                    @else
                                                        <span class="text-xs text-slate-300">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr x-show="activePinId === {{ $point->id }}" x-cloak class="bg-blue-50">
                                                <td colspan="5" class="px-5 py-3">
                                                    @if ($point->media->count())
                                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                                            @foreach ($point->media as $media)
                                                                <div class="card overflow-hidden">
                                                                    @if ($media->isVideo())
                                                                        <video controls class="w-full max-h-44 bg-black" src="{{ $media->url }}" preload="metadata"></video>
                                                                    @else
                                                                        <img src="{{ $media->url }}" alt="{{ $media->file_name }}"
                                                                             @click="openLightbox('{{ $media->url }}')"
                                                                             class="w-full max-h-44 cursor-pointer object-contain" />
                                                                    @endif
                                                                    <div class="flex items-center justify-between px-3 py-2 border-t border-slate-100">
                                                                        <p class="text-xs text-slate-600 truncate">{{ $media->file_name }}</p>
                                                                        <a href="{{ $media->url }}" download class="ml-2 shrink-0 text-xs text-blue-600 hover:underline">↓</a>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <p class="text-xs text-slate-400 italic">No media linked to this observation.</p>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                @endforeach
            @endforeach
        @else
            <div class="card flex flex-col items-center justify-center py-20 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/></svg>
                <p class="mt-3 text-sm font-medium text-slate-500">No observations recorded.</p>
            </div>
        @endif
    </div>

    {{-- ─────────────────── TAB 5: INSPECTION DATA ─────────────────── --}}
    <div x-show="activeTab === 'data'" x-cloak>
        @php
            $allMedia = collect();
            if ($project) {
                foreach ($project->structures as $s) {
                    foreach ($s->views as $v) {
                        foreach ($v->points as $p) {
                            foreach ($p->media as $m) {
                                $allMedia->push(['media' => $m, 'structure' => $s, 'point' => $p]);
                            }
                        }
                    }
                    foreach ($s->media as $m) {
                        $allMedia->push(['media' => $m, 'structure' => $s, 'point' => null]);
                    }
                }
            }
        @endphp

        @if ($allMedia->count())
            @foreach ($project->structures as $structure)
                @php $structureMedia = $allMedia->filter(fn($i) => $i['structure']->id === $structure->id); @endphp
                @if ($structureMedia->count())
                    <div class="mb-8">
                        <div class="mb-3 flex items-center gap-2">
                            <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500">{{ $structure->name }}</h3>
                            <div class="flex-1 border-t border-slate-200"></div>
                            <span class="text-xs text-slate-400">{{ $structureMedia->count() }} file{{ $structureMedia->count() !== 1 ? 's' : '' }}</span>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($structureMedia as $item)
                                @php $media = $item['media']; $point = $item['point']; @endphp
                                <div class="card overflow-hidden">
                                    @if ($media->isVideo())
                                        <video controls class="aspect-video w-full bg-slate-900" src="{{ $media->url }}" preload="metadata"></video>
                                    @else
                                        <div class="aspect-video cursor-pointer overflow-hidden bg-slate-100"
                                             @click="openLightbox('{{ $media->url }}')">
                                            <img src="{{ $media->url }}" alt="{{ $media->file_name }}"
                                                 class="h-full w-full object-cover transition-transform duration-300 hover:scale-105" />
                                        </div>
                                    @endif
                                    <div class="px-3 py-3">
                                        <p class="text-sm font-semibold text-slate-800 truncate">{{ $media->file_name }}</p>
                                        @if ($point)
                                            <p class="mt-0.5 text-xs text-blue-600 font-medium">Linked to {{ $point->observation_id ?? 'Point '.$point->point_number }}</p>
                                        @endif
                                        <div class="mt-2 flex items-center justify-between">
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] capitalize text-slate-500 font-medium">{{ $media->media_type }}</span>
                                            <a href="{{ $media->url }}" download="{{ $media->file_name }}"
                                               class="text-xs font-semibold text-blue-600 hover:text-blue-500">↓ Download</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        @else
            <div class="card flex flex-col items-center justify-center py-20 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                <p class="mt-3 text-sm font-medium text-slate-500">No inspection media uploaded yet.</p>
                <p class="mt-1 text-xs text-slate-400">Videos and images linked to observations will appear here.</p>
            </div>
        @endif
    </div>

    {{-- ─────────────────── TAB 6: CONCLUSIONS ─────────────────── --}}
    <div x-show="activeTab === 'conclusions'" x-cloak>
        <div class="space-y-5">

            {{-- Severity banner --}}
            <div class="card overflow-hidden">
                <div class="grid grid-cols-3 divide-x divide-slate-100">
                    <div class="p-6 text-center bg-red-50">
                        <p class="text-5xl font-black text-red-500">{{ $severityCounts['major'] }}</p>
                        <p class="mt-1.5 text-sm font-bold text-red-400 uppercase tracking-wide">Major</p>
                        <p class="mt-0.5 text-xs text-red-300">Observations</p>
                    </div>
                    <div class="p-6 text-center bg-orange-50">
                        <p class="text-5xl font-black text-orange-500">{{ $severityCounts['moderate'] }}</p>
                        <p class="mt-1.5 text-sm font-bold text-orange-400 uppercase tracking-wide">Moderate</p>
                        <p class="mt-0.5 text-xs text-orange-300">Observations</p>
                    </div>
                    <div class="p-6 text-center bg-yellow-50">
                        <p class="text-5xl font-black text-yellow-500">{{ $severityCounts['minor'] }}</p>
                        <p class="mt-1.5 text-sm font-bold text-yellow-500 uppercase tracking-wide">Minor</p>
                        <p class="mt-0.5 text-xs text-yellow-400">Observations</p>
                    </div>
                </div>
            </div>

            {{-- Severity legend --}}
            <div class="card p-5">
                <h3 class="mb-3 text-sm font-bold text-slate-800">Severity Reference</h3>
                <div class="space-y-2">
                    <div class="flex items-start gap-3 rounded-lg bg-red-50 px-3 py-2.5">
                        <div class="mt-0.5 h-3.5 w-3.5 rounded-full bg-red-500 shrink-0"></div>
                        <div>
                            <p class="text-sm font-semibold text-red-700">Major</p>
                            <p class="text-xs text-red-500">Structural integrity risk — requires immediate attention and remediation.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 rounded-lg bg-orange-50 px-3 py-2.5">
                        <div class="mt-0.5 h-3.5 w-3.5 rounded-full bg-orange-500 shrink-0"></div>
                        <div>
                            <p class="text-sm font-semibold text-orange-700">Moderate</p>
                            <p class="text-xs text-orange-500">Monitored defect — schedule repair within the next maintenance cycle.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 rounded-lg bg-yellow-50 px-3 py-2.5">
                        <div class="mt-0.5 h-3.5 w-3.5 rounded-full bg-yellow-400 shrink-0"></div>
                        <div>
                            <p class="text-sm font-semibold text-yellow-700">Minor</p>
                            <p class="text-xs text-yellow-600">Low-risk observation — record and review at next scheduled inspection.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Conclusions --}}
            @if ($report->conclusions)
                <div class="card overflow-hidden">
                    <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-600">
                            <svg class="h-4 w-4 text-white" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                        </div>
                        <h3 class="font-semibold text-slate-900">Conclusions</h3>
                    </div>
                    <div class="px-5 py-4 space-y-2">
                        @foreach (array_filter(explode("\n", $report->conclusions)) as $line)
                            <div class="flex items-start gap-2.5">
                                <div class="mt-1.5 h-1.5 w-1.5 rounded-full bg-green-500 shrink-0"></div>
                                <p class="text-sm text-slate-700 leading-relaxed">{{ trim($line) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Recommendations --}}
            @if ($report->recommendations)
                <div class="card overflow-hidden">
                    <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500">
                            <svg class="h-4 w-4 text-white" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                        </div>
                        <h3 class="font-semibold text-slate-900">Recommendations</h3>
                    </div>
                    <div class="px-5 py-4 space-y-2">
                        @foreach (array_filter(explode("\n", $report->recommendations)) as $line)
                            <div class="flex items-start gap-2.5">
                                <div class="mt-1.5 h-1.5 w-1.5 rounded-full bg-orange-400 shrink-0"></div>
                                <p class="text-sm text-slate-700 leading-relaxed">{{ trim($line) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- If no conclusions or recommendations at all --}}
            @if (!$report->conclusions && !$report->recommendations)
                <div class="card flex flex-col items-center justify-center py-14 text-center">
                    <svg class="mx-auto h-10 w-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <p class="mt-3 text-sm font-medium text-slate-500">No conclusions written yet.</p>
                    <p class="mt-1 text-xs text-slate-400">Conclusions and recommendations will appear here once the report is finalised.</p>
                </div>
            @endif

        </div>
    </div>

</main>

{{-- ═══════════════════════ FOOTER ═══════════════════════ --}}
<footer class="mt-8 border-t border-slate-200 bg-white no-print">
    <div class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between">
        <p class="text-xs text-slate-400">{{ $report->title }}</p>
        <p class="text-xs text-slate-400">FrogmenDash ROV Inspection Platform</p>
    </div>
</footer>

</body>
</html>
