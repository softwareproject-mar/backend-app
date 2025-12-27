<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetService
{
    /**
    * Generate a reset token (plain) and store its hash with timestamp.
    */
    public function generateToken(string $email): string
    {
        $plainToken = Str::random(64);
        $hashedToken = Hash::make($plainToken);
        $now = now();

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $hashedToken,
                'created_at' => $now,
            ]
        );

        return $plainToken;
    }

    /**
    * Verify reset token validity (hash match + TTL).
    */
    public function verifyToken(string $email, string $token): bool
    {
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record) {
            return false;
        }

        $ttlMinutes = config('password_reset.ttl_minutes', 60);
        $createdAt = $record->created_at ? Carbon::parse($record->created_at) : null;

        if (! $createdAt || $createdAt->lt(now()->subMinutes($ttlMinutes))) {
            return false;
        }

        return Hash::check($token, $record->token);
    }

    /**
    * Revoke/reset token for an email.
    */
    public function revokeToken(string $email): void
    {
        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }
}
