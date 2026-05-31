<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@crown-bom.test')],
            [
                'name'              => env('ADMIN_NAME', 'مدير النظام'),
                'password'          => env('ADMIN_PASSWORD', 'password'),
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

    }
}
