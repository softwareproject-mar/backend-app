<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@obormas.com');
        $password = env('ADMIN_PASSWORD', 'Admin@123');
        $superAdminEmail = env('SUPER_ADMIN_EMAIL', 'superadmin@obormas.com');
        $reviewerId = User::query()->where('email', $superAdminEmail)->value('id');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator',
                'password' => Hash::make($password),
                'role' => 'admin',
                'is_active' => true,
                'registration_status' => User::REGISTRATION_APPROVED,
                'email_verified_at' => now(),
                'no_agt' => null,
                'registration_reviewed_at' => now(),
                'registration_reviewed_by' => $reviewerId,
            ]
        );
    }
}
