<?php

namespace Webkul\PluginManager\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Security\Models\User;

/**
 * Set a user as platform admin (is_default=1) so they see the Companies menu
 * and can create tenants. Run with the email of the user who should see Companies.
 */
class MakePlatformAdmin extends Command
{
    protected $signature = 'erp:platform-admin
        {email? : User email to make platform admin (optional; lists users if omitted)}';

    protected $description = 'Set a user as platform admin so they see Companies and can create tenants.';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (! $email) {
            $users = User::query()
                ->select('id', 'name', 'email', 'is_default', 'default_company_id')
                ->orderBy('id')
                ->get();

            if ($users->isEmpty()) {
                $this->warn('No users found.');

                return self::FAILURE;
            }

            $this->table(
                ['ID', 'Name', 'Email', 'Platform admin (is_default)', 'Default company ID'],
                $users->map(fn ($u) => [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->is_default ? 'Yes' : 'No',
                    $u->default_company_id ?? '—',
                ])->toArray()
            );
            $this->newLine();
            $this->info('Run: php artisan erp:platform-admin user@example.com');
            $this->info('Then log in as that user and refresh the admin panel to see Companies.');

            return self::SUCCESS;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email \"{$email}\" not found.");

            return self::FAILURE;
        }

        $user->is_default = true;
        $user->save();

        $this->info("Done. \"{$user->name}\" ({$user->email}) is now a platform admin.");
        $this->info('Log in as this user and refresh the page (or clear cache) to see the Companies menu under Settings.');

        return self::SUCCESS;
    }
}
