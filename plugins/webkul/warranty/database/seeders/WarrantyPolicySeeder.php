<?php

namespace Webkul\Warranty\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Warranty\Enums\StartTrigger;

class WarrantyPolicySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $user = User::first();

        if (! $company) {
            return;
        }

        $policies = [
            [
                'name' => 'Standard 12-Month Product Warranty',
                'description' => 'Covers manufacturing defects for standard equipment.',
                'duration_months' => 12,
                'start_trigger' => StartTrigger::DeliveryDate->value,
                'coverage_json' => json_encode(['electronics', 'labour']),
                'include_spare_parts' => false,
                'include_labour' => true,
                'max_visits_per_year' => null,
                'is_active' => true,
            ],
            [
                'name' => 'ROV Full Coverage 24-Month',
                'description' => 'Extended warranty for ROV systems including parts and labour.',
                'duration_months' => 24,
                'start_trigger' => StartTrigger::DeliveryDate->value,
                'coverage_json' => json_encode(['hull', 'electronics', 'cameras', 'thrusters', 'lights', 'tether', 'labour', 'spare-parts']),
                'include_spare_parts' => true,
                'include_labour' => true,
                'max_visits_per_year' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Commissioning-Based 18-Month Warranty',
                'description' => 'Warranty starts at commissioning date for installation projects.',
                'duration_months' => 18,
                'start_trigger' => StartTrigger::CommissioningDate->value,
                'coverage_json' => json_encode(['electronics', 'labour', 'hydraulics']),
                'include_spare_parts' => true,
                'include_labour' => true,
                'max_visits_per_year' => 1,
                'is_active' => true,
            ],
        ];

        foreach ($policies as $policy) {
            $exists = DB::table('warranty_policies')
                ->where('company_id', $company->id)
                ->where('name', $policy['name'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('warranty_policies')->insert([
                'name' => $policy['name'],
                'description' => $policy['description'],
                'duration_months' => $policy['duration_months'],
                'start_trigger' => $policy['start_trigger'],
                'coverage_json' => $policy['coverage_json'],
                'include_spare_parts' => $policy['include_spare_parts'],
                'include_labour' => $policy['include_labour'],
                'max_visits_per_year' => $policy['max_visits_per_year'],
                'is_active' => $policy['is_active'],
                'company_id' => $company->id,
                'creator_id' => $user?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
