<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            'TechCorp Indústria',
            'Manufatura Avançada Ltda',
            'AutoParts Brasil',
            'Smart Factory Solutions',
            'Indústria 4.0 Inovações'
        ];

        foreach ($companies as $companyName) {
            Company::updateOrCreate(
                ['name' => $companyName],
                ['name' => $companyName]
            );
        }

        $this->command->info('✅ Companies seeded successfully!');
    }
}
