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
        if (Schema::hasTable('purchases_order_account_moves')) {
            return;
        }

        Schema::create('purchases_order_account_moves', function (Blueprint $table) {
            $table->foreignId('order_id')
                ->constrained('purchases_orders')
                ->cascadeOnDelete();

            if (Schema::hasTable('accounts_account_moves')) {
                $table->foreignId('move_id')
                    ->constrained('accounts_account_moves')
                    ->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('move_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases_order_account_moves');
    }
};
