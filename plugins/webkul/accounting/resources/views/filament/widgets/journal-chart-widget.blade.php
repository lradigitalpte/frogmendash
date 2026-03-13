<x-filament::section>
    <x-slot name="heading">
        @if ($journalUrl = $this->getUrl('index'))
            <x-filament::link
                tag="a"
                :href="$journalUrl"
            >
                {{ $journal->name }}
            </x-filament::link>
        @else
            {{ $journal->name }}
        @endif
    </x-slot>

    <x-slot name="afterHeader">
        <x-filament::button
            :href="$this->getUrl('create')"
            tag="a"
        >
            New
        </x-filament::button>
    </x-slot>

    @php
        $visibleStats = collect($dashboard['stats'])->filter(function ($stat) {
            $value = (float) ($stat['value'] ?? 0);
            $amount = (float) ($stat['amount'] ?? 0);

            return $value > 0 || abs($amount) > 0;
        });
    @endphp

    @if ($visibleStats->isNotEmpty())
        <div class="mb-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
            @foreach ($visibleStats as $stat)
                <x-filament::link
                    tag="a"
                    href="{{ $stat['url'] ?? '#' }}"
                    class="rounded-lg border border-gray-200/30 bg-gray-50/40 p-3 dark:border-gray-700/40 dark:bg-gray-900/30"
                >
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $stat['label'] ?? '' }}
                    </div>

                    <div class="mt-1 flex items-baseline justify-between gap-2">
                        <div class="text-lg font-semibold leading-none text-gray-900 dark:text-gray-100">
                            {{ $stat['value'] ?? 0 }}
                        </div>

                        @if (isset($stat['formatted_amount']))
                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                {{ $stat['formatted_amount'] }}
                            </div>
                        @endif
                    </div>
                </x-filament::link>
            @endforeach
        </div>
    @else
        <div class="mb-4 rounded-lg border border-dashed border-gray-300/40 p-3 text-xs text-gray-500 dark:border-gray-700/50 dark:text-gray-400">
            No summary metrics yet for this journal.
        </div>
    @endif

    {{-- Chart --}}
    <div class="mt-4" style="height: 300px; position: relative;">
        <canvas 
            id="journal-chart-{{ $journal->id }}"
            wire:ignore
        ></canvas>
    </div>
</x-filament::section>

@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
@endassets

@script
<script>
    setTimeout(() => {
        const ctx = document.getElementById('journal-chart-{{ $journal->id }}');
        if (ctx && !ctx.chart && window.Chart) {
            const chartData = @js($this->getChartData());
            
            ctx.chart = new Chart(ctx, {
                type: chartData.type,
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                callback: function(value) {
                                    if (chartData.valueMode === 'count') {
                                        return value;
                                    }

                                    return new Intl.NumberFormat(undefined, {
                                        notation: 'compact',
                                        maximumFractionDigits: 1,
                                    }).format(value);
                                }
                            }
                        }
                    }
                }
            });
        }
    }, 100);
</script>
@endscript
