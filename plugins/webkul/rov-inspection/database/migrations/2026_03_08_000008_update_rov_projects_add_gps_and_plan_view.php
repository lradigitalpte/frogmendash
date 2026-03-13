<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rov_projects', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('location')
                ->comment('GPS latitude for satellite map pin');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude')
                ->comment('GPS longitude for satellite map pin');
            $table->string('plan_view_path')->nullable()->after('site_map_path')
                ->comment('Top-down CAD/engineering drawing shown in Plan View modal');
        });
    }

    public function down(): void
    {
        Schema::table('rov_projects', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'plan_view_path']);
        });
    }
};
