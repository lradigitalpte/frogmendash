<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $report->title }} – Inspection Report</title>

    {{-- Tailwind --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Leaflet for satellite map --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* Tab active underline */
        .tab-active { border-bottom: 3px solid #2563eb; color: #1d4ed8; font-weight: 600; }
        .tab-inactive { border-bottom: 3px solid transparent; color: #6b7280; }
        .tab-inactive:hover { color: #374151; border-bottom-color: #d1d5db; }

        /* Pin SVG colors */
        .pin-major    { color: #ef4444; }
        .pin-moderate { color: #f97316; }
        .pin-minor    { color: #eab308; }

        @media print {
            .no-print { display: none !important; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-900 min-h-screen"
      x-data="{
          activeTab: 'home',
          activePinId: null,
          activeStructureIdx: 0,
          activeViewIdx: 0,
          planViewOpen: false,
          lightboxUrl: null,

          openPin(pinId) {
              this.activePinId = this.activePinId === pinId ? null : pinId;
          },

          openLightbox(url) { this.lightboxUrl = url; },
          closeLightbox() { this.lightboxUrl = null; },

          severityColor(s) {
              return { major: '#ef4444', moderate: '#f97316', minor: '#eab308' }[s] ?? '#6b7280';
          },
          severityLabel(s) {
              return { major: 'Major', moderate: 'Moderate', minor: 'Minor' }[s] ?? '—';
          },

          initMap() {
              @if ($project && $project->latitude && $project->longitude)
              const map = L.map('satellite-map', { zoomControl: true }).setView([{{ $project->latitude }}, {{ $project->longitude }}], 16);
              L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                  attribution: '© OpenStreetMap contributors',
                  maxZoom: 19,
              }).addTo(map);
              L.marker([{{ $project->latitude }}, {{ $project->longitude }}])
                  .addTo(map)
                  .bindPopup('<strong>{{ addslashes($project->name) }}</strong>{{ $project->location ? "<br>" . addslashes($project->location) : "" }}')
                  .openPopup();
              @endif
          },
      }"
      x-init="$nextTick(() => { if (activeTab === 'home') initMap(); })"
>

{{-- ═══════════════════════════════════════════════════════ TOP NAV ══ --}}
<nav class="sticky top-0 z-50 bg-white shadow-sm no-print">
    <div class="mx-auto max-w-7xl px-4">
        {{-- Brand + project title --}}
        <div class="flex items-center justify-between py-3 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 text-white">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Inspection Report</p>
                    <p class="text-sm font-bold text-gray-900 leading-tight">{{ $report->title }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if ($project?->plan_view_path)
                    <button @click="planViewOpen = true"
                            class="no-print inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-200">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M1 2.75A.75.75 0 0 1 1.75 2h10.5a.75.75 0 0 1 0 1.5H12v13.75a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1-.75-.75v-2.5a.75.75 0 0 0-.75-.75h-2.5a.75.75 0 0 0-.75.75v2.5a.75.75 0 0 1-.75.75H3a.75.75 0 0 1-.75-.75V3.5h-.5A.75.75 0 0 1 1 2.75Z" clip-rule="evenodd" /></svg>
                        Plan View
                    </button>
                @endif
                @if ($report->client_can_download)
                    <button onclick="window.print()"
                            class="no-print inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V7.621a1.5 1.5 0 0 0-.44-1.06l-4.12-4.122A1.5 1.5 0 0 0 11.378 2H4.5Zm4.75 6.75a.75.75 0 0 1 1.5 0v2.546l.943-1.048a.75.75 0 1 1 1.114 1.004l-2.25 2.5a.75.75 0 0 1-1.114 0l-2.25-2.5a.75.75 0 1 1 1.114-1.004l.943 1.048V8.75Z" clip-rule="evenodd" /></svg>
                        Download Report
                    </button>
                @endif
            </div>
        </div>

        {{-- Tab bar --}}
        <div class="flex gap-0 overflow-x-auto no-print">
            @foreach ([
                ['home',       'Home'],
                ['images',     'Inspection Image'],
                ['map',        'Inspection Map'],
                ['observations','Observations'],
                ['data',       'Inspection Data'],
                ['conclusions','Conclusions'],
            ] as [$tab, $label])
                <button @click="activeTab = '{{ $tab }}'; {{ $tab === 'home' ? '$nextTick(() => initMap())' : '' }}"
                        :class="activeTab === '{{ $tab }}' ? 'tab-active' : 'tab-inactive'"
                        class="whitespace-nowrap px-4 py-3 text-sm transition-colors">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>
</nav>

{{-- ═══════════════════════════════════════════ PLAN VIEW MODAL ══ --}}
<div x-show="planViewOpen" x-cloak
     class="no-print fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
     @click.self="planViewOpen = false">
    <div class="relative max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b px-5 py-4">
            <h2 class="text-lg font-bold text-gray-900">Plan View</h2>
            <button @click="planViewOpen = false" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
            </button>
        </div>
        <div class="overflow-auto p-4">
            <img src="{{ asset('storage/'.$project->plan_view_path) }}" alt="Plan View" class="mx-auto max-w-full rounded-lg" />
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════ LIGHTBOX ══ --}}
<div x-show="lightboxUrl" x-cloak
     class="no-print fixed inset-0 z-50 flex items-center justify-center bg-black/80"
     @click.self="closeLightbox()">
    <img :src="lightboxUrl" class="max-h-[90vh] max-w-[90vw] rounded-xl shadow-2xl" />
    <button @click="closeLightbox()" class="absolute right-4 top-4 rounded-full bg-white/20 p-2 text-white hover:bg-white/30">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
    </button>
</div>

{{-- ═══════════════════════════════════════════ PAGE CONTENT ══ --}}
<main class="mx-auto max-w-7xl px-4 py-8">

    {{-- ─────────────────── TAB 1: HOME ─────────────────── --}}
    <div x-show="activeTab === 'home'" x-cloak>
        <div class="grid gap-6 lg:grid-cols-2">

            {{-- Satellite map --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div id="satellite-map" class="h-96 w-full bg-gray-200"
                     x-init="if (activeTab === 'home') $nextTick(() => initMap())">
                    @if (! ($project?->latitude && $project?->longitude))
                        <div class="flex h-full items-center justify-center text-gray-400">
                            <div class="text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                                <p class="text-sm">No GPS coordinates set for this project</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Project summary card --}}
            <div class="flex flex-col justify-between rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-200">
                @if ($project?->customer?->image_url ?? false)
                    <img src="{{ $project->customer->image_url }}" alt="{{ $project->customer->name }}" class="mb-4 h-16 object-contain" />
                @endif

                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Underwater Visual Inspection Report</p>
                    <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $project?->name ?? $report->title }}</h1>
                    @if ($project?->customer?->name)
                        <p class="mt-1 text-gray-500">{{ $project->customer->name }}</p>
                    @endif
                    @if ($project?->location)
                        <p class="mt-1 flex items-center gap-1 text-sm text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-4 w-4 text-gray-400"><path fill-rule="evenodd" d="m7.539 14.841.003.003.002.002a.755.755 0 0 0 .912 0l.002-.002.003-.003.012-.009a5.57 5.57 0 0 0 .19-.153 15.588 15.588 0 0 0 2.046-2.082c1.101-1.362 2.291-3.342 2.291-5.597A5 5 0 0 0 3 7c0 2.255 1.19 4.235 2.292 5.597a15.591 15.591 0 0 0 2.046 2.082 8.916 8.916 0 0 0 .189.153l.012.01ZM8 8.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd"/></svg>
                            {{ $project->location }}
                        </p>
                    @endif
                    @if ($project?->start_date)
                        <p class="mt-1 text-sm text-gray-500">{{ $project->start_date->format('d M Y') }}{{ $project->end_date ? ' – '.$project->end_date->format('d M Y') : '' }}</p>
                    @endif
                </div>

                <div class="mt-6 grid grid-cols-3 gap-3 border-t border-gray-100 pt-6">
                    @php
                        $totalObs = $severityCounts['major'] + $severityCounts['moderate'] + $severityCounts['minor'];
                    @endphp
                    <div class="text-center">
                        <p class="text-2xl font-bold text-red-500">{{ $severityCounts['major'] }}</p>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Major</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-orange-500">{{ $severityCounts['moderate'] }}</p>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Moderate</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-yellow-500">{{ $severityCounts['minor'] }}</p>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Minor</p>
                    </div>
                </div>

                @if ($report->client_can_download)
                    <button onclick="window.print()"
                            class="mt-6 w-full rounded-xl bg-blue-600 py-3 text-sm font-semibold text-white hover:bg-blue-500 transition-colors">
                        Download Executive Report
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ─────────────────── TAB 2: INSPECTION IMAGE ─────────────────── --}}
    <div x-show="activeTab === 'images'" x-cloak>
        @if ($project && $project->structures->where('photo_path', '!=', null)->count())
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($project->structures->filter(fn($s) => $s->photo_path) as $structure)
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                        <div class="cursor-pointer overflow-hidden"
                             @click="openLightbox('{{ asset('storage/'.$structure->photo_path) }}')">
                            <img src="{{ asset('storage/'.$structure->photo_path) }}"
                                 alt="{{ $structure->name }}"
                                 class="aspect-square w-full object-cover transition-transform duration-200 hover:scale-105" />
                        </div>
                        <div class="px-3 py-2">
                            <p class="text-xs font-semibold text-gray-800">{{ $structure->name }}</p>
                            @if ($structure->description)
                                <p class="mt-0.5 text-xs text-gray-400 line-clamp-1">{{ $structure->description }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="flex flex-col items-center justify-center rounded-2xl bg-white py-20 text-center shadow-sm ring-1 ring-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                <p class="mt-3 text-sm text-gray-500">No structure photos uploaded for this project.</p>
            </div>
        @endif
    </div>

    {{-- ─────────────────── TAB 3: INSPECTION MAP ─────────────────── --}}
    <div x-show="activeTab === 'map'" x-cloak>
        @if ($project && $project->structures->count())
            {{-- Structure tabs --}}
            <div class="mb-4 flex flex-wrap gap-2">
                @foreach ($project->structures as $sIdx => $structure)
                    <button @click="activeStructureIdx = {{ $sIdx }}; activeViewIdx = 0; activePinId = null"
                            :class="activeStructureIdx === {{ $sIdx }} ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50'"
                            class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors">
                        {{ $structure->name }}
                    </button>
                @endforeach
            </div>

            @foreach ($project->structures as $sIdx => $structure)
                <div x-show="activeStructureIdx === {{ $sIdx }}" x-cloak>

                    {{-- View sub-tabs --}}
                    @if ($structure->views->count() > 1)
                        <div class="mb-3 flex gap-2">
                            @foreach ($structure->views as $vIdx => $view)
                                <button @click="activeViewIdx = {{ $vIdx }}; activePinId = null"
                                        :class="activeViewIdx === {{ $vIdx }} ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                        class="rounded-lg px-3 py-1 text-xs font-medium transition-colors">
                                    {{ $view->name }} ({{ ucfirst($view->view_type) }})
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @foreach ($structure->views as $vIdx => $view)
                        <div x-show="activeViewIdx === {{ $vIdx }}" x-cloak>
                            <div class="flex flex-col gap-4 lg:flex-row">

                                {{-- Annotated diagram --}}
                                <div class="flex-1 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                                    @if ($structure->diagram_path)
                                        <div class="relative select-none"
                                             x-data="{ imageLoaded: false }"
                                             x-ref="diagramWrap_{{ $sIdx }}_{{ $vIdx }}">
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
                                                <div class="absolute -translate-x-1/2 -translate-y-full cursor-pointer group"
                                                     style="left: {{ $point->x_coordinate }}%; top: {{ $point->y_coordinate }}%;"
                                                     @click.stop="activePinId = activePinId === {{ $point->id }} ? null : {{ $point->id }}">
                                                    <div class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-white shadow-lg transition-transform group-hover:scale-125"
                                                         :class="activePinId === {{ $point->id }} ? 'scale-125 ring-2 ring-white ring-offset-1' : ''"
                                                         style="background-color: {{ $pinColor }}">
                                                        <span class="text-[10px] font-bold text-white leading-none">{{ $point->observation_id ?? $point->point_number }}</span>
                                                    </div>
                                                    <div class="mx-auto h-2 w-0.5 opacity-60" style="background-color: {{ $pinColor }}"></div>
                                                </div>
                                            @endforeach
                                        </div>

                                        {{-- Severity legend --}}
                                        <div class="flex flex-wrap items-center gap-4 border-t border-gray-100 px-4 py-3">
                                            @foreach ([['major','#ef4444'],['moderate','#f97316'],['minor','#eab308']] as [$s,$c])
                                                <div class="flex items-center gap-1.5">
                                                    <div class="h-3 w-3 rounded-full" style="background-color:{{ $c }}"></div>
                                                    <span class="text-xs capitalize text-gray-500">{{ $s }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center py-20 text-center text-gray-400">
                                            <p class="text-sm">No diagram uploaded for {{ $structure->name }}</p>
                                        </div>
                                    @endif
                                </div>

                                {{-- Observations table + inline media player --}}
                                <div class="w-full lg:w-96 shrink-0">
                                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                                        <div class="border-b border-gray-100 px-4 py-3">
                                            <p class="text-sm font-semibold text-gray-900">{{ $view->name }}</p>
                                            <p class="text-xs text-gray-400">{{ $view->points->count() }} observations · Click a row or pin to see media</p>
                                        </div>

                                        {{-- Table --}}
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <thead>
                                                    <tr class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                        <th class="px-3 py-2 text-left">ID</th>
                                                        <th class="px-3 py-2 text-left">Description</th>
                                                        <th class="px-3 py-2 text-left">Location</th>
                                                        <th class="px-3 py-2 text-right">Depth</th>
                                                        <th class="px-3 py-2 text-center">↓</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100">
                                                    @forelse ($view->points as $point)
                                                        <tr @click="activePinId = activePinId === {{ $point->id }} ? null : {{ $point->id }}"
                                                            :class="activePinId === {{ $point->id }} ? 'bg-blue-50' : 'hover:bg-gray-50'"
                                                            class="cursor-pointer transition-colors">
                                                            <td class="px-3 py-2">
                                                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-bold text-white"
                                                                      style="background-color: {{ match(strtolower($point->severity ?? '')) { 'major' => '#ef4444', 'moderate' => '#f97316', 'minor' => '#eab308', default => '#6b7280' } }}">
                                                                    {{ $point->observation_id ?? $point->point_number }}
                                                                </span>
                                                            </td>
                                                            <td class="px-3 py-2">
                                                                <p class="font-medium text-gray-800">{{ $point->finding_type ?? '—' }}</p>
                                                                @if ($point->description)
                                                                    <p class="text-xs text-gray-400 line-clamp-1">{{ $point->description }}</p>
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2 text-xs text-gray-500">{{ $point->dive_location ?? '—' }}</td>
                                                            <td class="px-3 py-2 text-right text-xs text-gray-500">{{ $point->depth_m ? $point->depth_m.'m' : '—' }}</td>
                                                            <td class="px-3 py-2 text-center">
                                                                @if ($point->media->count())
                                                                    <span class="inline-flex items-center gap-0.5 rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-700">
                                                                        {{ $point->media->count() }}🎬
                                                                    </span>
                                                                @endif
                                                            </td>
                                                        </tr>

                                                        {{-- Inline media panel for this pin --}}
                                                        <tr x-show="activePinId === {{ $point->id }}"
                                                            x-cloak
                                                            class="bg-blue-50">
                                                            <td colspan="5" class="px-4 py-3">
                                                                @if ($point->media->count())
                                                                    <p class="mb-2 text-xs font-semibold text-blue-700">Media for {{ $point->observation_id ?? 'Point '.$point->point_number }}</p>
                                                                    <div class="space-y-2">
                                                                        @foreach ($point->media as $media)
                                                                            <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                                                                                @if ($media->isVideo())
                                                                                    <video controls class="w-full max-h-52 rounded-lg bg-black"
                                                                                           src="{{ $media->url }}"
                                                                                           preload="metadata">
                                                                                        Your browser does not support video playback.
                                                                                    </video>
                                                                                @else
                                                                                    <img src="{{ $media->url }}"
                                                                                         alt="{{ $media->file_name }}"
                                                                                         @click="openLightbox('{{ $media->url }}')"
                                                                                         class="w-full max-h-52 cursor-pointer object-contain rounded-lg hover:opacity-90" />
                                                                                @endif
                                                                                <div class="flex items-center justify-between px-3 py-2">
                                                                                    <p class="text-xs font-medium text-gray-700">{{ $media->file_name }}</p>
                                                                                    <a href="{{ $media->url }}" download="{{ $media->file_name }}"
                                                                                       class="text-xs font-medium text-blue-600 hover:text-blue-500">↓ Download</a>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <p class="text-xs text-gray-400 italic">No media linked to this observation.</p>
                                                                @endif

                                                                @if ($point->recommendations)
                                                                    <div class="mt-2 rounded-lg bg-orange-50 px-3 py-2 text-xs text-orange-700">
                                                                        <strong>Recommendation:</strong> {{ $point->recommendations }}
                                                                    </div>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">No observations for this view.</td>
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
            <div class="rounded-2xl bg-white py-20 text-center shadow-sm ring-1 ring-gray-200">
                <p class="text-sm text-gray-500">No inspection structures have been added to this project.</p>
            </div>
        @endif
    </div>

    {{-- ─────────────────── TAB 4: OBSERVATIONS ─────────────────── --}}
    <div x-show="activeTab === 'observations'" x-cloak>
        @if ($project && $project->structures->count())
            @foreach ($project->structures as $structure)
                @foreach ($structure->views as $view)
                    @if ($view->points->count())
                        <div class="mb-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                            <div class="flex items-center gap-3 border-b border-gray-100 bg-gray-50 px-5 py-4">
                                <span class="rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-semibold text-gray-700">{{ $structure->name }}</span>
                                <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">{{ $view->name }} · {{ ucfirst($view->view_type) }}</span>
                                <span class="ml-auto text-xs text-gray-400">{{ $view->points->count() }} observations</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            <th class="bg-red-50 px-4 py-3 text-left text-red-600">Defect ID</th>
                                            <th class="px-4 py-3 text-left">Description</th>
                                            <th class="px-4 py-3 text-left">Dive Location</th>
                                            <th class="px-4 py-3 text-right">Depth (m)</th>
                                            <th class="px-4 py-3 text-center">Media</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($view->points as $point)
                                            <tr @click="activePinId = activePinId === {{ $point->id }} ? null : {{ $point->id }}"
                                                :class="activePinId === {{ $point->id }} ? 'bg-blue-50' : 'hover:bg-gray-50'"
                                                class="cursor-pointer transition-colors">
                                                <td class="px-4 py-3">
                                                    <span class="font-bold" style="color: {{ match(strtolower($point->severity ?? '')) { 'major' => '#ef4444', 'moderate' => '#f97316', 'minor' => '#eab308', default => '#6b7280' } }}">
                                                        {{ $point->observation_id ?? 'O'.$loop->iteration }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 font-medium text-gray-800">{{ $point->finding_type ?? $point->description ?? '—' }}</td>
                                                <td class="px-4 py-3 text-gray-500">{{ $point->dive_location ?? '—' }}</td>
                                                <td class="px-4 py-3 text-right text-gray-500">{{ $point->depth_m ?? '—' }}</td>
                                                <td class="px-4 py-3 text-center">
                                                    <a x-show="!activePinId || activePinId !== {{ $point->id }}"
                                                       class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline">
                                                        ↓ {{ $point->media->count() }} file(s)
                                                    </a>
                                                </td>
                                            </tr>
                                            {{-- Inline media --}}
                                            <tr x-show="activePinId === {{ $point->id }}" x-cloak class="bg-blue-50">
                                                <td colspan="5" class="px-5 py-3">
                                                    @if ($point->media->count())
                                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                            @foreach ($point->media as $media)
                                                                <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                                                                    @if ($media->isVideo())
                                                                        <video controls class="w-full max-h-48 bg-black" src="{{ $media->url }}" preload="metadata"></video>
                                                                    @else
                                                                        <img src="{{ $media->url }}" alt="{{ $media->file_name }}"
                                                                             @click="openLightbox('{{ $media->url }}')"
                                                                             class="w-full max-h-48 cursor-pointer object-contain" />
                                                                    @endif
                                                                    <div class="flex items-center justify-between px-3 py-2">
                                                                        <p class="text-xs font-medium text-gray-700">{{ $media->file_name }}</p>
                                                                        <a href="{{ $media->url }}" download class="text-xs text-blue-600 hover:underline">↓</a>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <p class="text-xs text-gray-400 italic">No media linked to this observation.</p>
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
            <div class="rounded-2xl bg-white py-20 text-center shadow-sm ring-1 ring-gray-200">
                <p class="text-sm text-gray-500">No observations recorded.</p>
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
                    // Unlinked media (structure-level, no point)
                    foreach ($s->media as $m) {
                        $allMedia->push(['media' => $m, 'structure' => $s, 'point' => null]);
                    }
                }
            }
        @endphp

        @if ($allMedia->count())
            @foreach ($project->structures as $structure)
                @php
                    $structureMedia = $allMedia->filter(fn($i) => $i['structure']->id === $structure->id);
                @endphp
                @if ($structureMedia->count())
                    <div class="mb-8">
                        <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-gray-500">{{ $structure->name }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($structureMedia as $item)
                                @php $media = $item['media']; $point = $item['point']; @endphp
                                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                                    @if ($media->isVideo())
                                        <video controls class="aspect-video w-full bg-gray-900" src="{{ $media->url }}" preload="metadata"></video>
                                    @else
                                        <div class="aspect-video cursor-pointer overflow-hidden bg-gray-100"
                                             @click="openLightbox('{{ $media->url }}')">
                                            <img src="{{ $media->url }}" alt="{{ $media->file_name }}"
                                                 class="h-full w-full object-cover transition-transform hover:scale-105" />
                                        </div>
                                    @endif
                                    <div class="px-3 py-3">
                                        <p class="font-semibold text-gray-800">{{ $media->file_name }}</p>
                                        @if ($point)
                                            <p class="mt-0.5 text-xs text-blue-600">Linked to {{ $point->observation_id ?? 'Point '.$point->point_number }}</p>
                                        @endif
                                        <div class="mt-2 flex items-center justify-between">
                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs capitalize text-gray-600">{{ $media->media_type }}</span>
                                            <a href="{{ $media->url }}" download="{{ $media->file_name }}"
                                               class="text-xs font-medium text-blue-600 hover:underline">↓ Download</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        @else
            <div class="rounded-2xl bg-white py-20 text-center shadow-sm ring-1 ring-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                <p class="mt-3 text-sm text-gray-500">No inspection videos or images uploaded yet.</p>
            </div>
        @endif
    </div>

    {{-- ─────────────────── TAB 6: CONCLUSIONS ─────────────────── --}}
    <div x-show="activeTab === 'conclusions'" x-cloak>
        <div class="space-y-6">

            {{-- Severity summary --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="font-semibold text-gray-900">Observation Summary</h2>
                </div>
                <div class="grid grid-cols-3 divide-x divide-gray-100">
                    @foreach ([['Major','major','text-red-600','bg-red-50'],['Moderate','moderate','text-orange-600','bg-orange-50'],['Minor','minor','text-yellow-600','bg-yellow-50']] as [$label,$key,$text,$bg])
                        <div class="{{ $bg }} p-6 text-center">
                            <p class="text-4xl font-bold {{ $text }}">{{ $severityCounts[$key] }}</p>
                            <p class="mt-1 text-sm font-medium text-gray-600">{{ $label }}</p>
                            <p class="mt-0.5 text-xs text-gray-400">Observations</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Legend --}}
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <h3 class="mb-3 text-sm font-semibold text-gray-900">Marker Legend</h3>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li class="flex items-center gap-2"><span class="h-3.5 w-3.5 rounded-full bg-red-500"></span> <strong>Red markers</strong> — Major defects (require immediate attention)</li>
                    <li class="flex items-center gap-2"><span class="h-3.5 w-3.5 rounded-full bg-orange-500"></span> <strong>Orange markers</strong> — Moderate defects (monitor and schedule repair)</li>
                    <li class="flex items-center gap-2"><span class="h-3.5 w-3.5 rounded-full bg-yellow-400"></span> <strong>Yellow markers</strong> — Minor observations (record and review periodically)</li>
                </ul>
            </div>

            {{-- Report conclusions --}}
            @if ($report->conclusions)
                <div class="rounded-2xl bg-green-50 p-6 shadow-sm ring-1 ring-green-200">
                    <h3 class="mb-3 font-semibold text-green-900">Conclusions</h3>
                    <div class="prose prose-sm max-w-none text-green-800">
                        @foreach (array_filter(explode("\n", $report->conclusions)) as $line)
                            <p class="flex items-start gap-2 text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 h-4 w-4 shrink-0 text-green-600"><path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" /></svg>
                                {{ $line }}
                            </p>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Recommendations --}}
            @if ($report->recommendations)
                <div class="rounded-2xl bg-orange-50 p-6 shadow-sm ring-1 ring-orange-200">
                    <h3 class="mb-3 font-semibold text-orange-900">Recommendations</h3>
                    <div class="prose prose-sm max-w-none text-orange-800">
                        @foreach (array_filter(explode("\n", $report->recommendations)) as $line)
                            <p class="text-sm">{{ $line }}</p>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

</main>

{{-- Footer --}}
<footer class="mt-16 border-t border-gray-200 bg-white py-8 no-print">
    <div class="mx-auto max-w-7xl px-4 flex flex-col items-center gap-2 text-center sm:flex-row sm:justify-between">
        <p class="text-xs text-gray-400">{{ $report->title }}</p>
        <p class="text-xs text-gray-400">Generated by FrogmenDash ROV Inspection Platform</p>
    </div>
</footer>

</body>
</html>
