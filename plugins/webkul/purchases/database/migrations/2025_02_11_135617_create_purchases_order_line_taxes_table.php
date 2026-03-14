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
        if (Schema::hasTable('purchases_order_line_taxes')) {
            return;
        }

        Schema::create('purchases_order_line_taxes', function (Blueprint $table) {
            $table->foreignId('order_line_id')
                ->constrained('purchases_order_lines')
                ->cascadeOnDelete();

            if (Schema::hasTable('accounts_taxes')) {
                $table->foreignId('tax_id')
                    ->constrained('accounts_taxes')
                    ->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('tax_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases_order_line_taxes');
    }
};
