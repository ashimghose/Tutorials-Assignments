<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Safe to run in production: it only
     * adds content that isn't there yet. Make the first admin with
     * `php artisan app:make-admin`.
     */
    public function run(): void
    {
        $this->call([ContentSeeder::class, AcademicCatalogueSeeder::class]);
    }
}
