<div
    x-data="{
        url: @js($url),
        copied: false,
        copy() {
            const fallbackCopy = () => {
                const el = this.$refs.input;
                if (! el) return;
                el.focus();
                el.select();
                try {
                    document.execCommand('copy');
                    this.copied = true;
                } catch (e) {
                    // ignore
                }
            };

            if (! this.url) return;

            if (navigator?.clipboard?.writeText) {
                navigator.clipboard.writeText(this.url)
                    .then(() => { this.copied = true; })
                    .catch(() => fallbackCopy());
            } else {
                fallbackCopy();
            }

            if (this.copied) {
                setTimeout(() => this.copied = false, 1500);
            }
        },
    }"
    style="display: flex; flex-direction: column; gap: 0.75rem;"
>
    <p style="font-size: 0.875rem; opacity: 0.7;">
        Share this link:
    </p>

    <div style="display: flex; align-items: center; gap: 0.5rem;">
        <div style="flex: 1;">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    x-ref="input"
                    x-bind:value="url"
                    readonly
                    x-on:focus="$event.target.select()"
                />
            </x-filament::input.wrapper>
        </div>

        <x-filament::button
            type="button"
            icon="heroicon-m-clipboard-document"
            x-on:click="copy()"
        >
            Copy
        </x-filament::button>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
        <x-filament::link
            x-bind:href="url"
            target="_blank"
            rel="noreferrer"
        >
            Open in new tab
        </x-filament::link>

        <x-filament::badge
            x-show="copied"
            x-cloak
            color="success"
        >
            Copied
        </x-filament::badge>
    </div>
</div>
