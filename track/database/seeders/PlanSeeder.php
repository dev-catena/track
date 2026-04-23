<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = ['Basic', 'Professional', 'Enterprise'];

        foreach ($plans as $planName) {
            Plan::updateOrCreate(
                ['name' => $planName],
            );
        }
    }
}
