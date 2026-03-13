<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_media', function (Blueprint $table) {
            // Media now belongs to a structure (its logical container)
            $table->foreignId('structure_id')
                ->nullable()
                ->after('id')
                ->constrained('project_structures')
                ->nullOnDelete();

            // inspection_point_id becomes optional — media can exist without a pin
            // (shown in Inspection Data gallery) or be linked to one pin
            // Drop old required FK first, then re-add as nullable
        });

        // Re-create the inspection_point_id FK as nullable
        Schema::table('inspection_media', function (Blueprint $table) {
            $table->dropForeign(['inspection_point_id']);
            $table->foreignId('inspection_point_id')
                ->nullable()
                ->change();
        });

        Schema::table('inspection_media', function (Blueprint $table) {
            $table->foreign('inspection_point_id')
                ->references('id')
                ->on('inspection_points')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inspection_media', function (Blueprint $table) {
            $table->dropForeign(['structure_id']);
            $table->dropColumn('structure_id');

            $table->dropForeign(['inspection_point_id']);
        });

        Schema::table('inspection_media', function (Blueprint $table) {
            $table->foreignId('inspection_point_id')
                ->constrained('inspection_points')
                ->cascadeOnDelete()
                ->change();
        });
    }
};
