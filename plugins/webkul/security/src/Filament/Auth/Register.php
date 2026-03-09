<?php

namespace Webkul\Security\Filament\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Auth\Events\Registered;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Webkul\Support\Models\Company;

class Register extends Page
{
    use CanUseDatabaseTransactions;
    use InteractsWithFormActions;
    use InteractsWithForms;
    use WithRateLimiting;

    protected string $view = 'security::filament.auth.register';

    public ?array $data = [];

    protected string $userModel;

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();
    }

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $this->form->validate();

        $data = $this->getRegistrationFormData();

        $user = $this->wrapInDatabaseTransaction(function () use ($data) {
            $company = Company::create([
                'name'      => $data['company_name'],
                'is_active' => true,
            ]);

            $user = $this->getUserModel()::create([
                'name'                => $data['name'],
                'email'               => $data['email'],
                'password'            => $data['password'],
                'default_company_id'  => $company->id,
            ]);

            $user->allowedCompanies()->attach($company->id);

            $company->update(['creator_id' => $user->id]);

            return $user;
        });

        event(new Registered($user));

        Filament::auth()->login($user);

        session()->regenerate();
        session()->save(); // Ensure session is persisted before redirect so /admin sees the user

        return app(RegistrationResponse::class);
    }

    /**
     * Collect registration form data from form state and component state.
     * Filament/Livewire can store form state in $this->form->getState() or $this->data.
     * Throws ValidationException if required fields are missing or empty.
     */
    protected function getRegistrationFormData(): array
    {
        $fromForm = is_array($this->form->getState()) ? $this->form->getState() : [];
        $fromComponent = is_array($this->data) ? $this->data : [];
        if (isset($fromComponent['data']) && is_array($fromComponent['data'])) {
            $fromComponent = array_merge($fromComponent, $fromComponent['data']);
        }
        $data = array_merge($fromForm, $fromComponent);

        $required = ['company_name', 'name', 'email', 'password', 'passwordConfirmation'];
        foreach ($required as $key) {
            $value = $data[$key] ?? null;
            if ($value === null || (is_string($value) && trim($value) === '')) {
                throw ValidationException::withMessages([
                    "data.{$key}" => [__('validation.required', ['attribute' => $key])],
                ]);
            }
        }

        if (($data['password'] ?? '') !== ($data['passwordConfirmation'] ?? '')) {
            throw ValidationException::withMessages([
                'data.passwordConfirmation' => [__('validation.confirmed', ['attribute' => __('security::filament/auth/register.form.password')])],
            ]);
        }

        $data['company_name'] = trim((string) $data['company_name']);
        $data['name'] = trim((string) $data['name']);
        $data['email'] = trim((string) $data['email']);
        $data['password'] = $data['password'];

        return $data;
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('security::filament/auth/register.notifications.throttled'))
            ->danger();
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeSchema()
                    ->components([
                        $this->getCompanyNameFormComponent(),
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getCompanyNameFormComponent(): Component
    {
        return TextInput::make('company_name')
            ->label(__('security::filament/auth/register.form.company_name'))
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label(__('security::filament/auth/register.form.name'))
            ->required()
            ->maxLength(255);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('security::filament/auth/register.form.email'))
            ->email()
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel());
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('security::filament/auth/register.form.password'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rule(Password::default())
            ->same('passwordConfirmation');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label(__('security::filament/auth/register.form.password_confirmation'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->dehydrated(false);
    }

    public function loginAction(): Action
    {
        return Action::make('login')
            ->link()
            ->label(__('security::filament/auth/register.actions.login'))
            ->url(filament()->getLoginUrl());
    }

    protected function getUserModel(): string
    {
        if (isset($this->userModel)) {
            return $this->userModel;
        }

        /** @var SessionGuard $authGuard */
        $authGuard = Filament::auth();

        /** @var EloquentUserProvider $provider */
        $provider = $authGuard->getProvider();

        return $this->userModel = $provider->getModel();
    }

    public function getTitle(): string|Htmlable
    {
        return __('security::filament/auth/register.title');
    }

    public function getHeading(): string|Htmlable
    {
        return __('security::filament/auth/register.heading');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getRegisterFormAction(),
        ];
    }

    public function getRegisterFormAction(): Action
    {
        return Action::make('register')
            ->label(__('security::filament/auth/register.actions.register'))
            ->submit('register');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }
}
