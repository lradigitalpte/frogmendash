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
        if (! Schema::hasTable('products_categories')) {
            return;
        }

        Schema::table('products_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('products_categories', 'product_properties_definition')) {
                $table->json('product_properties_definition')->nullable()->comment('Product Properties Definition');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('products_categories')) {
            return;
        }

        Schema::table('products_categories', function (Blueprint $table) {
            if (Schema::hasColumn('products_categories', 'product_properties_definition')) {
                $table->dropColumn('product_properties_definition');
            }
        });
    }
};
