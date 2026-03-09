<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_access_logs', function (Blueprint $table) {
            $table->id();
            $table->string('accessed_by')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->integer('duration')->nullable();

            $table->foreignId('report_id')
                ->constrained('inspection_reports')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_access_logs');
    }
};
