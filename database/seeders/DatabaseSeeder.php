<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            JabatanSeeder::class,
            DepartemenSeeder::class,
            CabangSeeder::class,
            JamKerjaSeeder::class,
            MasterCutiSeeder::class,
            KPIMasterSeeder::class,
            AdminUserSeeder::class,
            KaryawanSeeder::class,
            KonfigurasiUmumSeeder::class,
        ]);
    }
}
