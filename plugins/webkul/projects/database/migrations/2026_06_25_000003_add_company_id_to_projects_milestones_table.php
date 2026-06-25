<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tenancy fix: project milestones had no company link (only project_id), so a
 * top-level milestone listing could cross companies. Add company_id and backfill
 * from the owning project's company. See TENANCY_REDESIGN_PLAN.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('projects_milestones', 'company_id')) {
            Schema::table('projects_milestones', function (Blueprint $table) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('project_id')
                    ->constrained('companies')
                    ->nullOnDelete();
            });
        }

        DB::statement('
            UPDATE projects_milestones m
            JOIN projects_projects p ON p.id = m.project_id
            SET m.company_id = p.company_id
            WHERE m.company_id IS NULL AND p.company_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        if (Schema::hasColumn('projects_milestones', 'company_id')) {
            Schema::table('projects_milestones', function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }
    }
};
