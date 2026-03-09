<x-filament-panels::page>
    <main class="w-full max-w-lg px-6 py-12 bg-white shadow-sm fi-simple-main place-self-center ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 sm:rounded-xl sm:px-12">
        @if (filament()->hasLogin())
            <header class="flex flex-col items-center mb-6 fi-simple-header">
                <h1 class="text-2xl font-bold tracking-tight text-center fi-simple-header-heading text-gray-950 dark:text-white">
                    {{ $this->getHeading() }}
                </h1>
                <p class="mt-2 text-sm text-center text-gray-500 fi-simple-header-subheading dark:text-gray-400">
                    {{ __('security::filament/auth/register.actions.before') }}
                    <a href="{{ filament()->getLoginUrl() }}" class="fi-link font-semibold text-primary-600 hover:underline dark:text-primary-400">
                        {{ $this->loginAction->getLabel() }}
                    </a>
                </p>
            </header>
        @endif

        <form
            id="form"
            wire:submit="register"
            x-data="{ isProcessing: false }"
            x-on:submit="if (isProcessing) $event.preventDefault()"
            x-on:form-processing-started="isProcessing = true"
            x-on:form-processing-finished="isProcessing = false"
            class="grid fi-form gap-y-6"
        >
            <div class="flex flex-col gap-8">
                {{-- Manual fields (Filament form schema often does not render on admin auth pages) --}}
                <div class="fi-fo field-wrp grid gap-y-6 sm:grid-cols-1" x-data="{ showPassword: false, showPasswordConfirmation: false }">
                    <div class="fi-fo-field-wrp">
                        <label for="company_name" class="fi-fo-field-wrp-label inline-flex items-center gap-x-2">
                            <span class="fi-fo-field-wrp-label-text text-sm font-medium text-gray-950 dark:text-white">
                                {{ __('security::filament/auth/register.form.company_name') }}
                            </span>
                            <span class="fi-fo-field-wrp-label-required text-danger-600 dark:text-danger-400">*</span>
                        </label>
                        <input type="text" id="company_name" wire:model="data.company_name" name="company_name" required
                            class="fi-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:focus:border-primary-400 dark:focus:ring-primary-400 sm:text-sm"
                            placeholder="{{ __('security::filament/auth/register.form.company_name') }}" autofocus>
                        @error('data.company_name')
                            <p class="fi-fo-field-wrp-error-message mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="fi-fo-field-wrp">
                        <label for="name" class="fi-fo-field-wrp-label inline-flex items-center gap-x-2">
                            <span class="fi-fo-field-wrp-label-text text-sm font-medium text-gray-950 dark:text-white">
                                {{ __('security::filament/auth/register.form.name') }}
                            </span>
                            <span class="fi-fo-field-wrp-label-required text-danger-600 dark:text-danger-400">*</span>
                        </label>
                        <input type="text" id="name" wire:model="data.name" name="name" required
                            class="fi-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:focus:border-primary-400 dark:focus:ring-primary-400 sm:text-sm"
                            placeholder="{{ __('security::filament/auth/register.form.name') }}">
                        @error('data.name')
                            <p class="fi-fo-field-wrp-error-message mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="fi-fo-field-wrp">
                        <label for="email" class="fi-fo-field-wrp-label inline-flex items-center gap-x-2">
                            <span class="fi-fo-field-wrp-label-text text-sm font-medium text-gray-950 dark:text-white">
                                {{ __('security::filament/auth/register.form.email') }}
                            </span>
                            <span class="fi-fo-field-wrp-label-required text-danger-600 dark:text-danger-400">*</span>
                        </label>
                        <input type="email" id="email" wire:model="data.email" name="email" required
                            class="fi-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:focus:border-primary-400 dark:focus:ring-primary-400 sm:text-sm"
                            placeholder="{{ __('security::filament/auth/register.form.email') }}">
                        @error('data.email')
                            <p class="fi-fo-field-wrp-error-message mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="fi-fo-field-wrp">
                        <label for="password" class="fi-fo-field-wrp-label inline-flex items-center gap-x-2">
                            <span class="fi-fo-field-wrp-label-text text-sm font-medium text-gray-950 dark:text-white">
                                {{ __('security::filament/auth/register.form.password') }}
                            </span>
                            <span class="fi-fo-field-wrp-label-required text-danger-600 dark:text-danger-400">*</span>
                        </label>
                        <input :type="showPassword ? 'text' : 'password'" id="password" wire:model="data.password" name="password" required
                            class="fi-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:focus:border-primary-400 dark:focus:ring-primary-400 sm:text-sm"
                            placeholder="{{ __('security::filament/auth/register.form.password') }}">
                        @error('data.password')
                            <p class="fi-fo-field-wrp-error-message mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="fi-fo-field-wrp">
                        <label for="passwordConfirmation" class="fi-fo-field-wrp-label inline-flex items-center gap-x-2">
                            <span class="fi-fo-field-wrp-label-text text-sm font-medium text-gray-950 dark:text-white">
                                {{ __('security::filament/auth/register.form.password_confirmation') }}
                            </span>
                            <span class="fi-fo-field-wrp-label-required text-danger-600 dark:text-danger-400">*</span>
                        </label>
                        <input :type="showPasswordConfirmation ? 'text' : 'password'" id="passwordConfirmation" wire:model="data.passwordConfirmation" name="passwordConfirmation" required
                            class="fi-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:focus:border-primary-400 dark:focus:ring-primary-400 sm:text-sm"
                            placeholder="{{ __('security::filament/auth/register.form.password_confirmation') }}">
                        @error('data.passwordConfirmation')
                            <p class="fi-fo-field-wrp-error-message mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <x-filament::actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />
            </div>
        </form>
    </main>
</x-filament-panels::page>
