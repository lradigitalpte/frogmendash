<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Webkul\Inventory\Enums\ProductTracking;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('products_products')) {
            return;
        }

        Schema::table('products_products', function (Blueprint $table) {
            if (! Schema::hasColumn('products_products', 'sale_delay')) {
                $table->integer('sale_delay')->nullable();
            }

            if (! Schema::hasColumn('products_products', 'tracking')) {
                $table->string('tracking')->nullable()->default(ProductTracking::QTY);
            }

            if (! Schema::hasColumn('products_products', 'description_picking')) {
                $table->text('description_picking')->nullable();
            }

            if (! Schema::hasColumn('products_products', 'description_pickingout')) {
                $table->text('description_pickingout')->nullable();
            }

            if (! Schema::hasColumn('products_products', 'description_pickingin')) {
                $table->text('description_pickingin')->nullable();
            }

            if (! Schema::hasColumn('products_products', 'is_storable')) {
                $table->boolean('is_storable')->nullable()->default(0);
            }

            if (! Schema::hasColumn('products_products', 'expiration_time')) {
                $table->integer('expiration_time')->nullable()->default(0);
            }

            if (! Schema::hasColumn('products_products', 'use_time')) {
                $table->integer('use_time')->nullable()->default(0);
            }

            if (! Schema::hasColumn('products_products', 'removal_time')) {
                $table->integer('removal_time')->nullable()->default(0);
            }

            if (! Schema::hasColumn('products_products', 'alert_time')) {
                $table->integer('alert_time')->nullable()->default(0);
            }

            if (! Schema::hasColumn('products_products', 'use_expiration_date')) {
                $table->boolean('use_expiration_date')->nullable()->default(0);
            }

            if (Schema::hasTable('users') && ! Schema::hasColumn('products_products', 'responsible_id')) {
                $table->foreignId('responsible_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('products_products')) {
            return;
        }

        Schema::table('products_products', function (Blueprint $table) {
            if (Schema::hasColumn('products_products', 'responsible_id')) {
                $table->dropForeign(['responsible_id']);

                $table->dropColumn('responsible_id');
            }

            $columnsToDrop = [
                'sale_delay',
                'tracking',
                'description_picking',
                'description_pickingout',
                'description_pickingin',
                'is_storable',
                'expiration_time',
                'use_time',
                'removal_time',
                'alert_time',
                'use_expiration_date',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('products_products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
