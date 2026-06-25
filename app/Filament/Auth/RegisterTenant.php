<?php

namespace App\Filament\Auth;

use BezhanSalleh\FilamentShield\Support\Utils;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Events\Registered;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Public self-signup that provisions a NEW tenant (company + first admin user),
 * but in a PENDING state: the records are created inactive and the user is NOT
 * logged in. A platform admin must approve the company (which activates it,
 * activates the first user, and seeds it via TenantProvisioner) before they can
 * log in. This is the temporary approval gate that the payment hook will later
 * replace/augment. See TENANCY_REDESIGN_PLAN.md.
 */
class RegisterTenant extends Register
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_name')
                    ->label('Company name')
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),
                $this->getNameFormComponent()->label('Your name'),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    /**
     * Create the pending company + first admin user. No seeding yet — that runs
     * on approval so we never provision an unapproved tenant.
     */
    protected function handleRegistration(array $data): Model
    {
        $company = Company::create([
            'name'      => $data['company_name'],
            'is_active' => false,
        ]);

        $user = User::create([
            'name'               => $data['name'],
            'email'              => $data['email'],
            'password'           => $data['password'],
            'default_company_id' => $company->id,
            'is_active'          => false,
        ]);

        $user->assignRole(Utils::getPanelUserRoleName());
        $user->allowedCompanies()->syncWithoutDetaching([$company->id]);

        return $user;
    }

    /**
     * Same as the parent flow, but DO NOT authenticate the new user — they are
     * pending approval. Show a clear "submitted for approval" message instead.
     */
    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $user = $this->wrapInDatabaseTransaction(function (): Model {
            $this->callHook('beforeValidate');
            $data = $this->form->getState();
            $this->callHook('afterValidate');
            $data = $this->mutateFormDataBeforeRegister($data);
            $this->callHook('beforeRegister');
            $user = $this->handleRegistration($data);
            $this->form->model($user)->saveRelationships();
            $this->callHook('afterRegister');

            return $user;
        });

        event(new Registered($user));

        $this->sendEmailVerificationNotification($user);

        Notification::make()
            ->success()
            ->title('Registration received')
            ->body('Your company has been submitted for approval. You will be able to sign in once it has been approved.')
            ->persistent()
            ->send();

        // No auto-login: the account is inactive until approved.
        return null;
    }
}
