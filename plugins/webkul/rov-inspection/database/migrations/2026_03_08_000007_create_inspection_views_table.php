<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_views', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('e.g. VISUAL_1, Dolphin_West_ROV');
            $table->string('view_type')->default('rov')->comment('rov or diver');

            $table->foreignId('structure_id')
                ->constrained('project_structures')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_views');
    }
};
