@php
    $statePath = $getStatePath();
    $accept = $getAcceptedFileTypes();
    $maxBytes = $getMaxSizeBytes();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="s3MultipartUpload({
            state: $wire.$entangle('{{ $statePath }}'),
            accept: @js($accept),
            maxBytes: @js($maxBytes),
            csrf: @js(csrf_token()),
            urls: {
                create: @js(route('rov-inspection.s3-multipart.create')),
                sign: @js(route('rov-inspection.s3-multipart.sign')),
                complete: @js(route('rov-inspection.s3-multipart.complete')),
                abort: @js(route('rov-inspection.s3-multipart.abort')),
            },
        })"
        class="fi-fo-field-wrp space-y-2"
    >
        {{-- Picker --}}
        <label
            x-show="status === 'idle' || status === 'error'"
            class="flex flex-col items-center justify-center gap-1 rounded-xl border-2 border-dashed border-gray-300 px-6 py-8 text-center cursor-pointer hover:border-primary-500 dark:border-gray-600"
        >
            <x-filament::icon icon="heroicon-o-arrow-up-tray" class="h-6 w-6 text-gray-400" />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Click to choose a file</span>
            <span class="text-xs text-gray-400" x-text="hint"></span>
            <input
                type="file"
                class="hidden"
                :accept="accept.length ? accept.join(',') : '*/*'"
                @change="start($event.target.files[0])"
            />
        </label>

        {{-- Progress --}}
        <div x-show="status === 'uploading'" x-cloak class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <div class="mb-2 flex items-center justify-between text-sm">
                <span class="font-medium text-gray-700 dark:text-gray-200 truncate" x-text="fileName"></span>
                <span class="text-gray-500" x-text="percent + '%'"></span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div class="h-full bg-primary-600 transition-all" :style="`width: ${percent}%`"></div>
            </div>
            <button type="button" @click="cancel()" class="mt-3 text-xs text-danger-600 hover:underline">Cancel</button>
        </div>

        {{-- Done --}}
        <div x-show="status === 'done'" x-cloak class="flex items-center justify-between rounded-xl border border-success-300 bg-success-50 px-4 py-3 dark:border-success-700 dark:bg-success-900/20">
            <div class="flex items-center gap-2 text-sm text-success-700 dark:text-success-300">
                <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5" />
                <span class="font-medium truncate" x-text="fileName"></span>
                <span class="text-xs text-success-600" x-show="humanSize" x-text="'(' + humanSize + ')'"></span>
            </div>
            <button type="button" @click="reset()" class="text-xs text-gray-500 hover:underline">Replace</button>
        </div>

        {{-- Error --}}
        <p x-show="status === 'error'" x-cloak class="text-sm text-danger-600" x-text="error"></p>
    </div>
</x-dynamic-component>
