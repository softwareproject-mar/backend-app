<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$email = $argv[1] ?? 'galih.ario2014@gmail.com';

$otp = DB::table('email_verifications')
    ->where('email', $email)
    ->orderBy('created_at', 'desc')
    ->first();

if ($otp) {
    echo "\n=== OTP INFORMATION ===\n";
    echo "Email: " . $otp->email . "\n";
    
    // Loop through all fields to find OTP code
    foreach ((array)$otp as $key => $value) {
        echo ucfirst($key) . ": " . $value . "\n";
    }
    echo "\n✅ Copy OTP code di atas untuk register!\n";
} else {
    echo "\n❌ No OTP found for: $email\n";
    echo "Please request OTP first: POST /api/auth/request-otp\n";
}
