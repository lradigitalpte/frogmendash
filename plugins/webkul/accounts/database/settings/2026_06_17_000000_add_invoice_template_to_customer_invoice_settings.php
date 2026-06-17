<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('accounts_customer_invoice.invoice_template', 'tax');
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('accounts_customer_invoice.invoice_template');
    }
};
