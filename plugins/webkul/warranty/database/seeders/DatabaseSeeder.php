<?php

namespace Webkul\Warranty\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            WarrantyPolicySeeder::class,
        ]);
    }
}
