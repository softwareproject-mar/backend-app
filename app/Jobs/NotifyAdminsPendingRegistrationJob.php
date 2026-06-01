<?php

namespace App\Jobs;

use App\Mail\PendingRegistrationAdminMail;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyAdminsPendingRegistrationJob
{
    use Dispatchable, SerializesModels;

    /** @var array<int, string> */
    private const BLOCKED_ADMIN_EMAILS = [
        'admin@obormas.com',
        'superadmin@obormas.com',
    ];

    public function __construct(
        public int $pendingUserId
    ) {}

    public function handle(): void
    {
        $pendingUser = User::find($this->pendingUserId);

        if (! $pendingUser) {
            return;
        }

        if ($pendingUser->registration_status !== User::REGISTRATION_PENDING) {
            return;
        }

        $version = (string) optional($pendingUser->updated_at)->timestamp;
        $lockKey = "pending_notify:initial:user:{$this->pendingUserId}:{$version}";
        if (Cache::has($lockKey)) {
            return;
        }

        $emails = User::query()
            ->whereIn('role', ['admin', 'super_admin'])
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn ($email) => mb_strtolower(trim((string) $email)))
            ->unique()
            ->filter(fn ($email) => $email !== '' && ! in_array($email, self::BLOCKED_ADMIN_EMAILS, true))
            ->values();

        if ($emails->isEmpty()) {
            Log::warning('No admin/super_admin emails for pending registration notify', [
                'pending_user_id' => $this->pendingUserId,
            ]);

            return;
        }

        $sent = false;
        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new PendingRegistrationAdminMail($pendingUser, false));
                $sent = true;

                Log::info('Pending registration notify sent to admin', [
                    'pending_user_id' => $pendingUser->id,
                    'admin_email' => $email,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send pending registration notify to admin', [
                    'pending_user_id' => $pendingUser->id,
                    'admin_email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Kunci idempotensi hanya jika pengiriman benar-benar terjadi.
        if ($sent) {
            Cache::put($lockKey, true, now()->addMinutes(30));
        }
    }
}
