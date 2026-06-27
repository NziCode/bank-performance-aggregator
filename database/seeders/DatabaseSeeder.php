<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ZoneSeeder::class,
            BranchSeeder::class,
            BranchOfficeSeeder::class,
            StaffUnitSeeder::class,
            UserSeeder::class,
            ServiceTypeSeeder::class,
        ]);
    }
}
