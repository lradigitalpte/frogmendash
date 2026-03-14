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
        if (! Schema::hasTable('products_packagings')) {
            return;
        }

        Schema::table('products_packagings', function (Blueprint $table) {
            if (! Schema::hasColumn('products_packagings', 'package_type_id')) {
                $table->foreignId('package_type_id')
                    ->nullable()
                    ->constrained('inventories_package_types')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('products_packagings')) {
            return;
        }

        Schema::table('products_packagings', function (Blueprint $table) {
            if (Schema::hasColumn('products_packagings', 'package_type_id')) {
                $table->dropForeign(['package_type_id']);

                $table->dropColumn('package_type_id');
            }
        });
    }
};
