<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->unsignedBigInteger('company_id')->nullable()->after('name');
        });

        Schema::table('settings', function (Blueprint $table): void {
            $table->dropUnique(['group', 'name']);
        });

        Schema::table('settings', function (Blueprint $table): void {
            $table->unique(['group', 'name', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropUnique(['group', 'name', 'company_id']);
        });

        Schema::table('settings', function (Blueprint $table): void {
            $table->unique(['group', 'name']);
        });

        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn('company_id');
        });
    }
};
