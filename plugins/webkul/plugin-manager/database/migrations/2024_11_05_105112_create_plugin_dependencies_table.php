<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plugin_dependencies')) {
            return;
        }

        Schema::create('plugin_dependencies', function (Blueprint $table) {
            $table->unsignedBigInteger('plugin_id');
            $table->unsignedBigInteger('dependency_id');
            $table->primary(['plugin_id', 'dependency_id']);

            if (Schema::hasTable('plugins')) {
                $table->foreign('plugin_id')->references('id')->on('plugins')->cascadeOnDelete();
                $table->foreign('dependency_id')->references('id')->on('plugins')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_dependencies');
    }
};
