<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\PlanSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            PlanSeeder::class,
            SuperAdminSeeder::class,
            BotConfigurationSeeder::class,
            ProfileSeeder::class,
            FunctionalitySeeder::class,
        ]);
    }
}
