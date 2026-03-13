<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_structures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('diagram_path')->nullable()->comment('Engineering elevation/cross-section drawing — the annotatable map');
            $table->string('photo_path')->nullable()->comment('Surface/above-water photo shown in gallery');
            $table->unsignedInteger('sort')->default(0);

            $table->foreignId('rov_project_id')
                ->constrained('rov_projects')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_structures');
    }
};
