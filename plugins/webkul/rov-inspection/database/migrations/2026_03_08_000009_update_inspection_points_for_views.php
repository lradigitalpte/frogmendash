<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_points', function (Blueprint $table) {
            // Add the new FK to inspection_views (nullable initially to allow data migration)
            $table->foreignId('inspection_view_id')
                ->nullable()
                ->after('id')
                ->constrained('inspection_views')
                ->cascadeOnDelete();

            // Rename defect_type to finding_type for clarity, add missing domain columns
            $table->string('observation_id')->nullable()->after('point_number')
                ->comment('Sequential display label: O1, O2, O3...');
            $table->string('finding_type')->nullable()->after('defect_type')
                ->comment('Corrosion, Marine Growth, Surface Deformation...');
            $table->string('dive_location')->nullable()->after('finding_type')
                ->comment('e.g. Plank A1, Pile 1A');
            $table->decimal('depth_m', 6, 2)->nullable()->after('dive_location')
                ->comment('Water depth at observation in metres');
            $table->string('dimension_mm')->nullable()->after('depth_m')
                ->comment('e.g. 67.00 x 28.18');
        });

        // Drop the old project-level FK — points now belong to inspection_views
        Schema::table('inspection_points', function (Blueprint $table) {
            $table->dropForeign(['rov_project_id']);
            $table->dropColumn('rov_project_id');
        });
    }

    public function down(): void
    {
        Schema::table('inspection_points', function (Blueprint $table) {
            $table->foreignId('rov_project_id')
                ->nullable()
                ->constrained('rov_projects')
                ->cascadeOnDelete();
        });

        Schema::table('inspection_points', function (Blueprint $table) {
            $table->dropForeign(['inspection_view_id']);
            $table->dropColumn([
                'inspection_view_id',
                'observation_id',
                'finding_type',
                'dive_location',
                'depth_m',
                'dimension_mm',
            ]);
        });
    }
};
