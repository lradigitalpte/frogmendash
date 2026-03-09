<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('summary')->nullable();
            $table->longText('full_report')->nullable();
            $table->text('conclusions')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('shared_link_hash')->unique()->nullable();
            $table->string('shared_link_password')->nullable();
            $table->timestamp('shared_link_expires_at')->nullable();
            $table->boolean('client_can_download')->default(false);
            $table->boolean('client_can_print')->default(false);
            $table->timestamp('shared_date')->nullable();

            $table->foreignId('rov_project_id')
                ->constrained('rov_projects')
                ->cascadeOnDelete();

            $table->foreignId('shared_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_reports');
    }
};
