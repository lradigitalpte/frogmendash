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
    class="space-y-3"
>
    <p class="text-sm text-gray-600 dark:text-gray-300">
        Share this link:
    </p>

    <div class="flex items-center gap-2">
        <input
            x-ref="input"
            type="text"
            :value="url"
            readonly
            class="fi-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:border-primary-400 dark:focus:ring-primary-400 sm:text-sm"
            @focus="$event.target.select()"
        />

        <button
            type="button"
            class="fi-btn fi-btn-size-md inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:bg-primary-500 dark:hover:bg-primary-400"
            @click="copy()"
        >
            Copy
        </button>
    </div>

    <div class="flex items-center justify-between gap-3">
        <a
            :href="url"
            target="_blank"
            rel="noreferrer"
            class="fi-link text-sm font-medium text-primary-600 hover:underline dark:text-primary-400"
        >
            Open in new tab
        </a>

        <span
            x-show="copied"
            x-cloak
            class="text-xs font-medium text-success-600 dark:text-success-400"
        >
            Copied
        </span>
    </div>
</div>
