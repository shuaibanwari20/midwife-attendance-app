<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // firstOrCreate: the default password is set ONLY when the account
        // is first created — re-seeding must never overwrite a password
        // the admin has since changed in Settings.
        User::firstOrCreate(
            ['email' => 'admin@anwari.tech'],
            [
                'name' => 'مدیر موسسه',
                'password' => Hash::make('admin1234'),
                'is_admin' => true,
            ]
        );
    }
}
