<x-filament-panels::page>
    @if (! $project)
        <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <x-heroicon-o-map class="mx-auto mb-4 h-16 w-16 text-gray-400" />
            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">No project selected</h3>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Please open a project to annotate its site map.</p>
            <a href="{{ \Webkul\RovInspection\Filament\Resources\RovProjectResource::getUrl('index') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">
                <x-heroicon-m-arrow-left class="h-4 w-4" />
                Back to Projects
            </a>
        </div>
    @else
        <div class="space-y-4">
            {{-- Project Header --}}
            <div class="flex items-center justify-between rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $project->name }}</h2>
                    @if ($project->location)
                        <p class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                            <x-heroicon-s-map-pin class="h-3.5 w-3.5" />
                            {{ $project->location }}
                        </p>
                    @endif
                </div>
                <a href="{{ \Webkul\RovInspection\Filament\Resources\RovProjectResource::getUrl('inspection-points', ['record' => $project]) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    <x-heroicon-m-list-bullet class="h-4 w-4" />
                    List View
                </a>
            </div>

            @if ($project->site_map_path)
                {{-- Map Canvas --}}
                <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden"
                     x-data="{
                         points: {{ json_encode($points) }},
                         addMode: false,
                         selectedPoint: null,
                         mapWidth: 0,
                         mapHeight: 0,
                         init() {
                             const img = this.$refs.mapImage;
                             const setDims = () => {
                                 this.mapWidth = img.naturalWidth;
                                 this.mapHeight = img.naturalHeight;
                             };
                             img.complete ? setDims() : img.addEventListener('load', setDims);
                         },
                         handleMapClick(event) {
                             if (! this.addMode) return;
                             const rect = this.$refs.mapContainer.getBoundingClientRect();
                             const x = ((event.clientX - rect.left) / rect.width) * 100;
                             const y = ((event.clientY - rect.top) / rect.height) * 100;
                             $wire.handleMapPointPlaced(x.toFixed(2), y.toFixed(2));
                             this.addMode = false;
                         },
                         severityColor(severity) {
                             const colors = { low: '#22c55e', medium: '#3b82f6', high: '#f97316', critical: '#ef4444' };
                             return colors[severity] || '#6b7280';
                         },
                     }"
                     @points-updated.window="points = $event.detail.points">

                    <div class="flex items-center justify-between border-b border-gray-200 p-3 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Map Annotations</span>
                            <span class="rounded-full bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-900 dark:text-primary-300"
                                  x-text="points.length + ' points'"></span>
                        </div>
                        <button type="button"
                                @click="addMode = !addMode"
                                :class="addMode ? 'bg-danger-600 text-white hover:bg-danger-500' : 'bg-primary-600 text-white hover:bg-primary-500'"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold transition-colors">
                            <x-heroicon-m-plus class="h-4 w-4" />
                            <span x-text="addMode ? 'Cancel - Click to cancel' : 'Add Point'"></span>
                        </button>
                    </div>

                    <div class="p-3">
                        <p x-show="addMode" class="mb-2 text-sm font-medium text-primary-600 dark:text-primary-400">
                            Click anywhere on the map to place an inspection point
                        </p>

                        <div class="relative select-none overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                             :class="addMode ? 'cursor-crosshair' : 'cursor-default'"
                             @click="handleMapClick($event)"
                             x-ref="mapContainer">
                            <img x-ref="mapImage"
                                 src="{{ asset('storage/'.$project->site_map_path) }}"
                                 alt="Site Map"
                                 class="w-full object-contain" />

                            {{-- Render points --}}
                            <template x-for="point in points" :key="point.id">
                                <div class="absolute -translate-x-1/2 -translate-y-1/2 group"
                                     :style="`left: ${point.x}%; top: ${point.y}%;`">
                                    <div class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-white shadow-lg cursor-pointer transition-transform group-hover:scale-125"
                                         :style="`background-color: ${severityColor(point.severity)}`"
                                         @click.stop="selectedPoint = selectedPoint?.id === point.id ? null : point">
                                        <span class="text-xs font-bold text-white" x-text="point.point_number"></span>
                                    </div>

                                    {{-- Tooltip --}}
                                    <div x-show="selectedPoint?.id === point.id"
                                         @click.stop
                                         class="absolute bottom-8 left-1/2 z-10 -translate-x-1/2 min-w-48 rounded-lg bg-white p-3 shadow-xl ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                                        <p class="font-semibold text-gray-900 dark:text-white" x-text="point.label"></p>
                                        <p x-show="point.defect_type" class="text-xs text-gray-500" x-text="point.defect_type"></p>
                                        <p x-show="point.description" class="mt-1 text-xs text-gray-600 dark:text-gray-400" x-text="point.description"></p>
                                        <div class="mt-2 flex gap-2">
                                            <button type="button"
                                                    @click="$wire.handleMapPointDeleted(point.id); selectedPoint = null;"
                                                    class="text-xs text-danger-600 hover:text-danger-500">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Legend --}}
                    <div class="flex flex-wrap items-center gap-4 border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Severity:</span>
                        @foreach ([['low','#22c55e','Low'],['medium','#3b82f6','Medium'],['high','#f97316','High'],['critical','#ef4444','Critical']] as [$key,$color,$label])
                            <div class="flex items-center gap-1.5">
                                <div class="h-3.5 w-3.5 rounded-full border border-white shadow" style="background-color: {{ $color }}"></div>
                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <x-heroicon-o-photo class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                    <h3 class="mb-2 font-semibold text-gray-900 dark:text-white">No site map uploaded</h3>
                    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Upload a site map or blueprint first to begin annotating inspection points.</p>
                    <a href="{{ \Webkul\RovInspection\Filament\Resources\RovProjectResource::getUrl('edit', ['record' => $project]) }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">
                        <x-heroicon-m-arrow-up-tray class="h-4 w-4" />
                        Upload Site Map
                    </a>
                </div>
            @endif

            {{-- Points List --}}
            @if (count($points))
                <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">All Inspection Points ({{ count($points) }})</h3>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($points as $point)
                            <div class="flex items-start gap-3 px-4 py-3">
                                <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold text-white"
                                     style="background-color: {{ match($point['severity'] ?? null) {
                                         'low'      => '#22c55e',
                                         'medium'   => '#3b82f6',
                                         'high'     => '#f97316',
                                         'critical' => '#ef4444',
                                         default    => '#6b7280',
                                     } }}">{{ $point['point_number'] }}</div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $point['label'] }}</p>
                                    @if ($point['defect_type'])
                                        <p class="text-xs text-gray-500">{{ $point['defect_type'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
