<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tenancy fix: inspection reports have a top-level resource but no company link,
 * so every tenant could see every tenant's reports. Add company_id and backfill
 * from the owning ROV project's company. See TENANCY_REDESIGN_PLAN.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('inspection_reports', 'company_id')) {
            Schema::table('inspection_reports', function (Blueprint $table) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('rov_project_id')
                    ->constrained('companies')
                    ->nullOnDelete();
            });
        }

        // Backfill from the owning project's company.
        DB::statement('
            UPDATE inspection_reports r
            JOIN rov_projects p ON p.id = r.rov_project_id
            SET r.company_id = p.company_id
            WHERE r.company_id IS NULL AND p.company_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        if (Schema::hasColumn('inspection_reports', 'company_id')) {
            Schema::table('inspection_reports', function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }
    }
};
