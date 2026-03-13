<?php

namespace Webkul\Accounting\Filament\Widgets;

use Filament\Widgets\Widget;
use Webkul\Account\Models\Journal;

class JournalChartsWidget extends Widget
{
    protected string $view = 'accounting::filament.widgets.journal-charts-widget';

    protected int|string|array $columnSpan = 'full';

    public string $activeTab = 'all';

    public function getJournals()
    {
        $journals = Journal::query()
            ->withCount([
                'moves as moves_count',
                'moves as posted_moves_count' => fn ($q) => $q->where('state', 'posted'),
            ])
            ->when($this->activeTab !== 'all', function ($query) {
                $query->where('type', $this->activeTab);
            })
            ->orderBy('id', 'asc')
            ->get()
            ->filter(function ($journal) {
                return (bool) $journal->show_on_dashboard
                    || (int) $journal->moves_count > 0
                    || (int) $journal->posted_moves_count > 0;
            });

        if ($this->activeTab !== 'all') {
            return $journals->values();
        }

        return $journals
            ->groupBy(fn ($journal) => is_object($journal->type) ? $journal->type->value : $journal->type)
            ->map(function ($group) {
                return $group
                    ->sortByDesc('posted_moves_count')
                    ->sortByDesc('moves_count')
                    ->sortByDesc(fn ($j) => (int) $j->show_on_dashboard)
                    ->sortBy('id')
                    ->first();
            })
            ->sortBy('id')
            ->values();
    }
}
