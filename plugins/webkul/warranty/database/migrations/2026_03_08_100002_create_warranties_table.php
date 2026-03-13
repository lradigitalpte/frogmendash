<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranties', function (Blueprint $table) {
            $table->id();

            // Policy snapshot (nullable so warranties can be created without a policy)
            $table->foreignId('warranty_policy_id')
                ->nullable()
                ->constrained('warranty_policies')
                ->nullOnDelete();

            // What was sold
            $table->foreignId('product_id')
                ->constrained('products_products')
                ->cascadeOnDelete();

            $table->string('serial_number')->nullable()->index();
            $table->string('asset_tag')->nullable()->index();

            // Who bought it
            $table->foreignId('customer_id')
                ->constrained('partners_partners')
                ->cascadeOnDelete();

            // Tenant
            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();

            // Source documents (all nullable — can be created manually)
            $table->foreignId('sales_order_id')
                ->nullable()
                ->constrained('sales_orders')
                ->nullOnDelete();

            $table->foreignId('delivery_id')
                ->nullable()
                ->constrained('inventories_operations')
                ->nullOnDelete();

            // Warranty period
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Copied from policy at creation time (for audit — policy may change later)
            $table->string('start_trigger')->default('delivery_date');
            $table->unsignedSmallInteger('duration_months')->default(12);
            $table->json('coverage_snapshot_json')->nullable();

            // Status: draft | active | expired | void
            $table->string('status')->default('draft')->index();

            $table->text('notes')->nullable();

            $table->foreignId('creator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Fast lookups
            $table->index(['serial_number', 'product_id', 'customer_id']);
            $table->index(['customer_id', 'status', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranties');
    }
};
