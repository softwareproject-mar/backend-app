<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Update password untuk user yang sudah ada
$users = [
    'galih.ario2014@gmail.com' => 'passwordbaru123.',
    'galih@gmail.com' => 'password123',
    'test@example.com' => 'password123',
];

echo "Updating user passwords...\n\n";

foreach ($users as $email => $password) {
    $user = User::where('email', $email)->first();
    
    if ($user) {
        $user->password = Hash::make($password);
        $user->save();
        
        echo "✓ Updated: {$email}\n";
        echo "  Password: {$password}\n\n";
    } else {
        echo "✗ Not found: {$email}\n\n";
    }
}

echo "Done!\n";
