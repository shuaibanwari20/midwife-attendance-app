<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Production seed — REAL deployment (Shuaib's order, 2026-11-26):
     * only the admin account is created. No demo clusters, midwives,
     * attendance, leave, notifications or problem reports — the admin
     * adds the real clusters and midwives from the admin panel.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
        ]);
    }
}
