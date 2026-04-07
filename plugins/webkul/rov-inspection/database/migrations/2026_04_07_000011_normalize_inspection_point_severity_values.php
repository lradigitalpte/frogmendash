<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('inspection_points')->where('severity', 'critical')->update(['severity' => 'major']);
        DB::table('inspection_points')->where('severity', 'high')->update(['severity' => 'major']);
        DB::table('inspection_points')->where('severity', 'medium')->update(['severity' => 'moderate']);
        DB::table('inspection_points')->where('severity', 'low')->update(['severity' => 'minor']);
    }

    public function down(): void
    {
        // Intentionally left as no-op to avoid lossy reverse mapping.
    }
};
