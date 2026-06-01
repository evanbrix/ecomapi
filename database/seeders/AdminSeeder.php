<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('AdminSeeder: ADMIN_EMAIL / ADMIN_PASSWORD not set — skipping.');

            return;
        }

        if (app()->isProduction() && strlen($password) < 12) {
            $this->command?->error('AdminSeeder: ADMIN_PASSWORD must be at least 12 chars in production. Skipping.');

            return;
        }

        Admin::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Admin'),
                'password' => Hash::make($password),
            ]
        );

        $this->command?->info("AdminSeeder: ensured admin {$email}.");
    }
}
