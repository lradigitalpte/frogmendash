<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Stats Row --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
            <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-2">
                    <div class="fi-wi-stats-overview-stat-icon flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-950">
                        <x-heroicon-o-clipboard-document-list class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                    </div>
                </div>
                <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">Total Projects</p>
                <p class="text-3xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $stats['total_projects'] }}</p>
            </div>

            <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-2">
                    <div class="fi-wi-stats-overview-stat-icon flex h-10 w-10 items-center justify-center rounded-lg bg-info-50 dark:bg-info-950">
                        <x-heroicon-o-arrow-path class="h-6 w-6 text-info-600 dark:text-info-400" />
                    </div>
                </div>
                <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">In Progress</p>
                <p class="text-3xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $stats['active_projects'] }}</p>
            </div>

            <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-2">
                    <div class="fi-wi-stats-overview-stat-icon flex h-10 w-10 items-center justify-center rounded-lg bg-success-50 dark:bg-success-950">
                        <x-heroicon-o-check-circle class="h-6 w-6 text-success-600 dark:text-success-400" />
                    </div>
                </div>
                <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">Completed</p>
                <p class="text-3xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $stats['completed_projects'] }}</p>
            </div>

            <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-2">
                    <div class="fi-wi-stats-overview-stat-icon flex h-10 w-10 items-center justify-center rounded-lg bg-warning-50 dark:bg-warning-950">
                        <x-heroicon-o-document-text class="h-6 w-6 text-warning-600 dark:text-warning-400" />
                    </div>
                </div>
                <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">Total Reports</p>
                <p class="text-3xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $stats['total_reports'] }}</p>
            </div>

            <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-2">
                    <div class="fi-wi-stats-overview-stat-icon flex h-10 w-10 items-center justify-center rounded-lg bg-success-50 dark:bg-success-950">
                        <x-heroicon-o-share class="h-6 w-6 text-success-600 dark:text-success-400" />
                    </div>
                </div>
                <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">Shared Reports</p>
                <p class="text-3xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $stats['shared_reports'] }}</p>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="mb-4 text-base font-semibold text-gray-950 dark:text-white">Quick Actions</h2>
            <div class="flex flex-wrap gap-3">
                <a href="{{ \Webkul\RovInspection\Filament\Resources\RovProjectResource::getUrl('create') }}"
                   class="fi-btn fi-btn-size-md inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:bg-primary-500 dark:hover:bg-primary-400">
                    <x-heroicon-m-plus class="h-4 w-4" />
                    New Inspection Project
                </a>
                <a href="{{ \Webkul\RovInspection\Filament\Resources\RovProjectResource::getUrl('index') }}"
                   class="fi-btn fi-btn-size-md inline-flex items-center gap-1.5 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-white/10 dark:text-white dark:ring-white/20 dark:hover:bg-white/20">
                    <x-heroicon-m-clipboard-document-list class="h-4 w-4" />
                    View All Projects
                </a>
                <a href="{{ \Webkul\RovInspection\Filament\Resources\InspectionReportResource::getUrl('index') }}"
                   class="fi-btn fi-btn-size-md inline-flex items-center gap-1.5 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-white/10 dark:text-white dark:ring-white/20 dark:hover:bg-white/20">
                    <x-heroicon-m-document-text class="h-4 w-4" />
                    All Reports
                </a>
            </div>
        </div>

        {{-- Module Overview --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="mb-4 text-base font-semibold text-gray-950 dark:text-white">Module Overview</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <div class="flex items-center gap-3 mb-2">
                        <x-heroicon-o-clipboard-document-list class="h-5 w-5 text-primary-600" />
                        <h3 class="font-medium text-gray-900 dark:text-white">Inspection Projects</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Create and manage ROV inspection projects. Upload site maps, track project status and assign clients.</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <div class="flex items-center gap-3 mb-2">
                        <x-heroicon-o-map-pin class="h-5 w-5 text-danger-600" />
                        <h3 class="font-medium text-gray-900 dark:text-white">Inspection Points</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Add findings and observations to each project. Classify severity and attach media files (images/videos).</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <div class="flex items-center gap-3 mb-2">
                        <x-heroicon-o-share class="h-5 w-5 text-success-600" />
                        <h3 class="font-medium text-gray-900 dark:text-white">Client Reports</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Generate professional inspection reports, create shareable links and let clients view their findings online.</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
