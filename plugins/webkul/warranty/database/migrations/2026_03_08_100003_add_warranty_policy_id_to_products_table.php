<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products_products', function (Blueprint $table) {
            $table->foreignId('warranty_policy_id')
                ->nullable()
                ->after('is_configurable')
                ->constrained('warranty_policies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products_products', function (Blueprint $table) {
            $table->dropForeign(['warranty_policy_id']);
            $table->dropColumn('warranty_policy_id');
        });
    }
};
