<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Isi database dengan data contoh warung dan satu akun pengelola contoh.
     */
    public function run(): void
    {
        $this->call(WarungSeeder::class);
    }
}
