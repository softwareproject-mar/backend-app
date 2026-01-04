<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "Creating test user...\n";

$user = User::updateOrCreate(
    ['email' => 'admin@test.com'],
    [
        'name' => 'Admin Test',
        'password' => Hash::make('password123'),
        'role' => 'admin',
        'is_active' => true,
        'email_verified_at' => now()
    ]
);

echo "Created/Updated user:\n";
echo "Email: {$user->email}\n";
echo "Name: {$user->name}\n";
echo "Password: password123\n";
