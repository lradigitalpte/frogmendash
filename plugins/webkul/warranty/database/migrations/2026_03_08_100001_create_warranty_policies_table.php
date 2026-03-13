<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_months')->default(12);

            // When does the warranty clock start?
            // delivery_date | invoice_date | commissioning_date | manual
            $table->string('start_trigger')->default('delivery_date');

            // JSON array of coverage tags: ["hull", "electronics", "camera", "labour"]
            $table->json('coverage_json')->nullable();

            $table->boolean('include_spare_parts')->default(false);
            $table->boolean('include_labour')->default(false);
            $table->unsignedTinyInteger('max_visits_per_year')->nullable();

            $table->boolean('is_active')->default(true)->index();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();

            $table->foreignId('creator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_policies');
    }
};
