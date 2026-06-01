<?php

namespace App\Services;

use App\Models\EmailVerification;
use Illuminate\Support\Facades\Cache;

class OtpService
{
    /**
     * Generate a 6-digit random OTP.
     */
    public function generateOtp(string $email): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Store OTP in database with expiry time.
     */
    public function storeOtp(string $email, string $otp, string $purpose = 'register'): EmailVerification
    {
        // Delete any existing OTP for this email & purpose
        EmailVerification::where('email', $email)
            ->where('purpose', $purpose)
            ->delete();

        $expiryMinutes = config('otp.expiry_minutes', 5);

        return EmailVerification::create([
            'email' => $email,
            'purpose' => $purpose,
            'otp_code' => $otp,
            'expires_at' => now()->addMinutes($expiryMinutes),
            'attempts' => 0,
        ]);
    }

    /**
     * Verify OTP code for given email.
     */
    public function verifyOtp(string $email, string $otp, string $purpose = 'register'): bool
    {
        $verification = EmailVerification::forEmail($email)
            ->forPurpose($purpose)
            ->notExpired()
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $verification) {
            return false;
        }

        // Increment attempts
        $verification->incrementAttempts();

        // Check if OTP matches and is still valid
        if ($verification->otp_code === $otp && $verification->isValid()) {
            $verification->markAsVerified();

            return true;
        }

        return false;
    }

    /**
     * Check rate limit for OTP requests (per purpose + email; max N per 10 minutes).
     */
    public function checkRateLimit(string $email, string $purpose = 'register'): bool
    {
        $normalized = mb_strtolower(trim($email));
        $key = sprintf('otp_request:%s:%s', $purpose, $normalized);
        $attempts = Cache::get($key, 0);
        $maxAttempts = config('otp.rate_limit', 10);

        if ($attempts >= $maxAttempts) {
            return false;
        }

        // Increment and set TTL to 10 minutes
        Cache::put($key, $attempts + 1, now()->addMinutes(10));

        return true;
    }

    /**
     * Delete expired OTP records.
     */
    public function cleanupExpired(): int
    {
        return EmailVerification::where('expires_at', '<', now())->delete();
    }
}
