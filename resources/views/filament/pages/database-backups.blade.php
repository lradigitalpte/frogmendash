<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">{{ __('On-demand database backup') }}</x-slot>
        <x-slot name="description">{{ __('Generates a full SQL dump of the database and downloads it to your computer.') }}</x-slot>

        <div style="font-size: 0.875rem; line-height: 1.6;">
            <p>{{ __('Click "Download backup" above to get a complete .sql snapshot of the database (structure + data).') }}</p>
            <p style="margin-top: 0.75rem; opacity: 0.75;">
                {{ __('Keep this file private — it contains all data. It is streamed directly to you and is never stored in cloud storage. To restore, import the .sql with your MySQL client.') }}
            </p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
