<x-filament-panels::page>
    @if (! $structure)
        {{-- No structure selected --}}
        <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <x-heroicon-o-map class="mx-auto mb-4 h-16 w-16 text-gray-400" />
            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">No structure selected</h3>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                Open a project, go to the <strong>Structures</strong> tab, and click <strong>Annotate</strong> on a structure that has a diagram uploaded.
            </p>
            <a href="{{ \Webkul\RovInspection\Filament\Resources\RovProjectResource::getUrl('index') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">
                <x-heroicon-m-arrow-left class="h-4 w-4" />
                Back to Projects
            </a>
        </div>
    @else
        @php $diagramUrl = $structure->diagram_path ? asset('storage/'.$structure->diagram_path) : null; @endphp

        <div
            class="space-y-4"
            x-data="{
                points: @js($points),
                structureMedia: @js($this->structureMedia),
                addMode: false,
                selectedPoint: null,
                editingPoint: null,
                showMediaPanel: false,
                showNewViewForm: false,
                newViewName: '',
                newViewType: 'rov',

                init() {
                    window.addEventListener('points-updated', (e) => {
                        this.points = e.detail.points;
                        if (this.selectedPoint) {
                            this.selectedPoint = this.points.find(p => p.id === this.selectedPoint.id) ?? null;
                        }
                        if (this.editingPoint) {
                            this.editingPoint = this.points.find(p => p.id === this.editingPoint.id) ?? null;
                        }
                    });
                },

                handleMapClick(event) {
                    if (! this.addMode) return;
                    const rect = this.$refs.mapContainer.getBoundingClientRect();
                    const x = ((event.clientX - rect.left) / rect.width * 100).toFixed(2);
                    const y = ((event.clientY - rect.top)  / rect.height * 100).toFixed(2);
                    $wire.dispatch('map-point-placed', { x: parseFloat(x), y: parseFloat(y) });
                    this.addMode = false;
                },

                severityColor(severity) {
                    return { major: '#ef4444', moderate: '#f97316', minor: '#eab308' }[severity] ?? '#6b7280';
                },

                severityLabel(severity) {
                    return { major: 'Major', moderate: 'Moderate', minor: 'Minor' }[severity] ?? '—';
                },

                selectPoint(point) {
                    if (this.selectedPoint?.id === point.id) {
                        this.selectedPoint = null;
                        this.editingPoint = null;
                    } else {
                        this.selectedPoint = point;
                        this.editingPoint = JSON.parse(JSON.stringify(point));
                    }
                },

                savePoint() {
                    if (! this.editingPoint) return;
                    $wire.dispatch('map-point-updated', { pointId: this.editingPoint.id, data: this.editingPoint });
                    this.selectedPoint = null;
                    this.editingPoint = null;
                },

                deletePoint(pointId) {
                    $wire.dispatch('map-point-deleted', { pointId });
                    this.selectedPoint = null;
                    this.editingPoint = null;
                },

                linkMedia(mediaId) {
                    if (! this.selectedPoint) return;
                    $wire.dispatch('map-point-media-linked', { pointId: this.selectedPoint.id, mediaId });
                },

                unlinkMedia(mediaId) {
                    $wire.dispatch('map-point-media-unlinked', { mediaId });
                },

                isLinkedToSelected(media) {
                    return this.selectedPoint && media.inspection_point_id === this.selectedPoint.id;
                },

                createView() {
                    if (! this.newViewName.trim()) return;
                    $wire.createView(this.newViewName.trim(), this.newViewType);
                    this.newViewName = '';
                    this.showNewViewForm = false;
                },
            }"
        >

            {{-- ── Header ──────────────────────────────────────────────────── --}}
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div>
                    <div class="flex items-center gap-2">
                        <a href="{{ \Webkul\RovInspection\Filament\Resources\RovProjectResource::getUrl('structures', ['record' => $structure->rov_project_id]) }}"
                           class="text-sm text-gray-500 hover:text-primary-600 dark:text-gray-400">
                            {{ $structure->project->name }}
                        </a>
                        <x-heroicon-m-chevron-right class="h-3.5 w-3.5 text-gray-400" />
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ $structure->name }}</h2>
                    </div>
                    @if ($structure->description)
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $structure->description }}</p>
                    @endif
                </div>
                <a href="{{ \Webkul\RovInspection\Filament\Resources\RovProjectResource::getUrl('structures', ['record' => $structure->rov_project_id]) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700">
                    <x-heroicon-m-list-bullet class="h-4 w-4" />
                    Back to Structures
                </a>
            </div>

            {{-- ── Inspection View selector ─────────────────────────────────── --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Inspection View:</span>
                    @forelse ($availableViews as $v)
                        <button type="button"
                                wire:click="switchView({{ $v['id'] }})"
                                class="rounded-full px-3 py-1 text-sm font-medium transition-colors {{ $viewId == $v['id'] ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}">
                            {{ $v['name'] }}
                            <span class="ml-1 text-xs opacity-70">({{ ucfirst($v['view_type']) }})</span>
                        </button>
                    @empty
                        <span class="text-sm text-gray-400 italic">No views yet</span>
                    @endforelse

                    {{-- New view button --}}
                    <button type="button"
                            @click="showNewViewForm = !showNewViewForm"
                            class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300">
                        <x-heroicon-m-plus class="h-3.5 w-3.5" />
                        New View
                    </button>
                </div>

                {{-- Inline new-view form --}}
                <div x-show="showNewViewForm" x-cloak class="flex flex-wrap items-end gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/50">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">View Name</label>
                        <input type="text" x-model="newViewName" placeholder="e.g. VISUAL_1, Dolphin_West_ROV"
                               class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Type</label>
                        <select x-model="newViewType"
                                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="rov">ROV</option>
                            <option value="diver">Diver</option>
                        </select>
                    </div>
                    <button type="button" @click="createView()"
                            class="rounded-lg bg-primary-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-primary-500">
                        Create
                    </button>
                    <button type="button" @click="showNewViewForm = false"
                            class="rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Cancel
                    </button>
                </div>
            </div>

            @if ($diagramUrl)
                {{-- ── Main canvas + side panel ────────────────────────────── --}}
                <div class="flex gap-4">

                    {{-- Canvas --}}
                    <div class="flex-1 min-w-0 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">

                        {{-- Toolbar --}}
                        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    @if ($currentView)
                                        Annotating: <strong>{{ $currentView->name }}</strong>
                                    @else
                                        Select a view above to annotate
                                    @endif
                                </span>
                                <span class="rounded-full bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-900 dark:text-primary-300"
                                      x-text="points.length + ' pins'"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($viewId)
                                    <button type="button"
                                            @click="showMediaPanel = !showMediaPanel"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300">
                                        <x-heroicon-m-film class="h-4 w-4" />
                                        <span x-text="showMediaPanel ? 'Hide Media' : 'Show Media'"></span>
                                    </button>
                                    <button type="button"
                                            @click="addMode = !addMode"
                                            :class="addMode ? 'bg-danger-600 text-white hover:bg-danger-500' : 'bg-primary-600 text-white hover:bg-primary-500'"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold transition-colors">
                                        <x-heroicon-m-plus class="h-4 w-4" />
                                        <span x-text="addMode ? 'Cancel' : 'Place Pin'"></span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <p x-show="addMode" x-cloak
                           class="bg-primary-50 px-4 py-2 text-sm font-medium text-primary-700 dark:bg-primary-950 dark:text-primary-300">
                            <x-heroicon-s-cursor-arrow-ripple class="inline h-4 w-4" />
                            Click anywhere on the diagram to place a pin
                        </p>

                        {{-- The annotatable image --}}
                        <div class="relative select-none overflow-hidden"
                             :class="addMode ? 'cursor-crosshair' : 'cursor-default'"
                             @click="handleMapClick($event)"
                             x-ref="mapContainer">
                            <img src="{{ $diagramUrl }}" alt="{{ $structure->name }} diagram"
                                 class="w-full object-contain max-h-[70vh]" />

                            {{-- Pins --}}
                            <template x-for="point in points" :key="point.id">
                                <div class="absolute -translate-x-1/2 -translate-y-full group pointer-events-auto"
                                     :style="`left: ${point.x}%; top: ${point.y}%;`">
                                    {{-- Pin marker --}}
                                    <div class="relative">
                                        <div class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-white shadow-lg cursor-pointer transition-all group-hover:scale-125"
                                             :class="selectedPoint?.id === point.id ? 'ring-2 ring-offset-1 ring-white scale-125' : ''"
                                             :style="`background-color: ${severityColor(point.severity)}`"
                                             @click.stop="selectPoint(point)">
                                            <span class="text-[10px] font-bold text-white leading-none"
                                                  x-text="point.observation_id ?? point.point_number"></span>
                                        </div>
                                        {{-- Pin stem --}}
                                        <div class="mx-auto h-2 w-0.5 opacity-70"
                                             :style="`background-color: ${severityColor(point.severity)}`"></div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Severity legend --}}
                        <div class="flex flex-wrap items-center gap-4 border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                            <span class="text-xs font-medium uppercase tracking-wide text-gray-400">Severity:</span>
                            @foreach ([['major','#ef4444'],['moderate','#f97316'],['minor','#eab308']] as [$key,$col])
                                <div class="flex items-center gap-1.5">
                                    <div class="h-3 w-3 rounded-full shadow" style="background-color:{{ $col }}"></div>
                                    <span class="text-xs capitalize text-gray-600 dark:text-gray-400">{{ $key }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ── Right side panel: edit pin OR media ────────────────── --}}
                    <div class="w-80 flex-shrink-0 space-y-4">

                        {{-- Edit observation panel (shows when a pin is selected) --}}
                        <div x-show="selectedPoint" x-cloak
                             class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white"
                                    x-text="'Pin ' + (selectedPoint?.observation_id ?? selectedPoint?.point_number)"></h3>
                                <button @click="selectedPoint = null; editingPoint = null;"
                                        class="text-gray-400 hover:text-gray-600">
                                    <x-heroicon-m-x-mark class="h-4 w-4" />
                                </button>
                            </div>
                            <div class="space-y-3 p-4" x-show="editingPoint">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Severity</label>
                                    <select x-model="editingPoint.severity"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                        <option value="major">🔴 Major</option>
                                        <option value="moderate">🟠 Moderate</option>
                                        <option value="minor">🟡 Minor</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Finding Type</label>
                                    <input type="text" x-model="editingPoint.finding_type"
                                           placeholder="e.g. Corrosion, Marine Growth"
                                           class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Dive Location</label>
                                        <input type="text" x-model="editingPoint.dive_location"
                                               placeholder="Plank A1"
                                               class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Depth (m)</label>
                                        <input type="number" x-model="editingPoint.depth_m" step="0.1"
                                               class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Dimension (mm)</label>
                                    <input type="text" x-model="editingPoint.dimension_mm"
                                           placeholder="67.00 x 28.18"
                                           class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Description</label>
                                    <textarea x-model="editingPoint.description" rows="2"
                                              placeholder="Describe the finding…"
                                              class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Recommendations</label>
                                    <textarea x-model="editingPoint.recommendations" rows="2"
                                              placeholder="Recommended action…"
                                              class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" @click="savePoint()"
                                            class="flex-1 rounded-lg bg-primary-600 py-1.5 text-sm font-semibold text-white hover:bg-primary-500">
                                        Save
                                    </button>
                                    <button type="button"
                                            @click="if(confirm('Delete this pin?')) deletePoint(selectedPoint.id)"
                                            class="rounded-lg bg-danger-50 px-3 py-1.5 text-sm font-medium text-danger-600 hover:bg-danger-100 dark:bg-danger-950 dark:text-danger-400">
                                        Delete
                                    </button>
                                </div>

                                {{-- Attached media for this pin --}}
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Attached Media</label>
                                    <template x-if="selectedPoint && selectedPoint.media && selectedPoint.media.length">
                                        <div class="space-y-1">
                                            <template x-for="m in selectedPoint.media" :key="m.id">
                                                <div class="flex items-center gap-2 rounded-lg bg-gray-50 px-2 py-1.5 dark:bg-gray-800">
                                                    <span class="flex-1 truncate text-xs" x-text="m.file_name"></span>
                                                    <span class="rounded-full bg-gray-200 px-1.5 py-0.5 text-[10px] capitalize dark:bg-gray-700" x-text="m.media_type"></span>
                                                    <button @click="unlinkMedia(m.id)"
                                                            class="text-danger-500 hover:text-danger-700">
                                                        <x-heroicon-m-x-mark class="h-3.5 w-3.5" />
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <p x-show="!selectedPoint?.media?.length"
                                       class="text-xs text-gray-400 italic">No media linked yet.</p>
                                </div>
                            </div>
                        </div>

                        {{-- Observations list (always visible) --}}
                        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white"
                                    x-text="'Observations (' + points.length + ')'"></h3>
                            </div>
                            <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                                <template x-for="point in points" :key="point.id">
                                    <button type="button"
                                            @click="selectPoint(point)"
                                            :class="selectedPoint?.id === point.id ? 'bg-primary-50 dark:bg-primary-950/40' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50'"
                                            class="flex w-full items-start gap-3 px-4 py-2.5 text-left transition-colors">
                                        <div class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white"
                                             :style="`background-color: ${severityColor(point.severity)}`"
                                             x-text="point.observation_id ?? point.point_number"></div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium text-gray-900 dark:text-white truncate"
                                               x-text="point.finding_type || point.description || 'No finding type'"></p>
                                            <p class="text-[10px] text-gray-400 truncate"
                                               x-text="(point.dive_location ? point.dive_location + ' · ' : '') + severityLabel(point.severity)"></p>
                                        </div>
                                        <span x-show="point.media && point.media.length"
                                              class="ml-auto flex-shrink-0 rounded-full bg-info-100 px-1.5 py-0.5 text-[10px] font-medium text-info-700 dark:bg-info-900 dark:text-info-300"
                                              x-text="point.media.length + '🎬'"></span>
                                    </button>
                                </template>
                                <div x-show="!points.length"
                                     class="px-4 py-6 text-center text-sm text-gray-400 italic">
                                    No pins yet. Select a view and click "Place Pin".
                                </div>
                            </div>
                        </div>

                        {{-- Media library panel (toggle) --}}
                        <div x-show="showMediaPanel" x-cloak
                             class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                    Structure Media Library
                                </h3>
                                <p class="text-xs text-gray-400">Click a file to link it to the selected pin.</p>
                            </div>
                            <div class="max-h-72 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                                <template x-for="m in structureMedia" :key="m.id">
                                    <div class="flex items-center gap-2 px-3 py-2">
                                        <div class="flex h-9 w-12 flex-shrink-0 items-center justify-center overflow-hidden rounded bg-gray-100 dark:bg-gray-800">
                                            <template x-if="m.thumbnail_url">
                                                <img :src="m.thumbnail_url" class="h-full w-full object-cover" />
                                            </template>
                                            <template x-if="!m.thumbnail_url && m.media_type === 'video'">
                                                <x-heroicon-o-play-circle class="h-5 w-5 text-gray-400" />
                                            </template>
                                            <template x-if="!m.thumbnail_url && m.media_type === 'image'">
                                                <x-heroicon-o-photo class="h-5 w-5 text-gray-400" />
                                            </template>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-xs font-medium text-gray-800 dark:text-gray-200" x-text="m.file_name"></p>
                                            <p class="text-[10px] text-gray-400 capitalize" x-text="m.media_type"></p>
                                        </div>
                                        <template x-if="isLinkedToSelected(m)">
                                            <span class="rounded-full bg-success-100 px-2 py-0.5 text-[10px] font-medium text-success-700">Linked</span>
                                        </template>
                                        <template x-if="!isLinkedToSelected(m) && m.inspection_point_id">
                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-500">Other pin</span>
                                        </template>
                                        <template x-if="!m.inspection_point_id && selectedPoint">
                                            <button @click="linkMedia(m.id)"
                                                    class="rounded-full bg-primary-100 px-2 py-0.5 text-[10px] font-medium text-primary-700 hover:bg-primary-200">
                                                Link
                                            </button>
                                        </template>
                                    </div>
                                </template>
                                <div x-show="!structureMedia.length"
                                     class="px-4 py-6 text-center text-sm text-gray-400 italic">
                                    No media uploaded for this structure yet.
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            @else
                {{-- No diagram uploaded --}}
                <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <x-heroicon-o-photo class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                    <h3 class="mb-2 font-semibold text-gray-900 dark:text-white">No diagram uploaded for {{ $structure->name }}</h3>
                    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                        Go back to the Structures tab, edit this structure, and upload an engineering diagram or elevation drawing.
                    </p>
                    <a href="{{ \Webkul\RovInspection\Filament\Resources\RovProjectResource::getUrl('structures', ['record' => $structure->rov_project_id]) }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">
                        <x-heroicon-m-arrow-left class="h-4 w-4" />
                        Back to Structures
                    </a>
                </div>
            @endif

        </div>
    @endif
</x-filament-panels::page>
