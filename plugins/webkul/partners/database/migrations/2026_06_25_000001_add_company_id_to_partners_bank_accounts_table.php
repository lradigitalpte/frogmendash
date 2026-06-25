<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tenancy fix: bank accounts were global (no company_id), so every tenant could
 * see and edit every other tenant's bank accounts. Add a company link and
 * backfill existing rows from the owning partner's company (falling back to the
 * creator's default company). See TENANCY_REDESIGN_PLAN.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('partners_bank_accounts', 'company_id')) {
            Schema::table('partners_bank_accounts', function (Blueprint $table) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('partner_id')
                    ->constrained('companies')
                    ->nullOnDelete();
            });
        }

        // Backfill from the owning partner's company.
        DB::statement('
            UPDATE partners_bank_accounts ba
            JOIN partners_partners p ON p.id = ba.partner_id
            SET ba.company_id = p.company_id
            WHERE ba.company_id IS NULL AND p.company_id IS NOT NULL
        ');

        // Fall back to the creator's default company for any still unassigned.
        DB::statement('
            UPDATE partners_bank_accounts ba
            JOIN users u ON u.id = ba.creator_id
            SET ba.company_id = u.default_company_id
            WHERE ba.company_id IS NULL AND u.default_company_id IS NOT NULL
        ');

        // Any rows still null cannot be attributed to a company; with CompanyScope
        // they become invisible to tenants (fail-safe) rather than leaking.
    }

    public function down(): void
    {
        if (Schema::hasColumn('partners_bank_accounts', 'company_id')) {
            Schema::table('partners_bank_accounts', function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }
    }
};
