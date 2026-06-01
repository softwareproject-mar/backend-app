<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'superadmin@obormas.com');
        $password = env('SUPER_ADMIN_PASSWORD', 'SuperAdmin@123');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'password' => Hash::make($password),
                'role' => 'super_admin',
                'is_active' => true,
                'registration_status' => User::REGISTRATION_APPROVED,
                'email_verified_at' => now(),
                'no_agt' => null,
                'registration_reviewed_at' => now(),
            ]
        );

        $user->forceFill([
            'registration_reviewed_by' => $user->id,
        ])->save();
    }
}
