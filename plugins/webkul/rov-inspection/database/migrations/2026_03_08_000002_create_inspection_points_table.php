<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_points', function (Blueprint $table) {
            $table->id();
            $table->integer('point_number')->nullable();
            $table->string('label')->nullable();
            $table->float('x_coordinate')->nullable();
            $table->float('y_coordinate')->nullable();
            $table->string('severity')->nullable()->index();
            $table->string('defect_type')->nullable();
            $table->text('description')->nullable();
            $table->text('recommendations')->nullable();

            $table->foreignId('rov_project_id')
                ->constrained('rov_projects')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_points');
    }
};
