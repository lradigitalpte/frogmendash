<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventories_category_routes', function (Blueprint $table) {
            if (Schema::hasTable('products_categories')) {
                $table->foreignId('category_id')
                    ->constrained('products_categories')
                    ->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('category_id');
            }

            $table->foreignId('route_id')
                ->constrained('inventories_routes')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories_category_routes');
    }
};
