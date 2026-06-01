<?php

namespace App\Jobs;

use App\Mail\PendingRegistrationAdminMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyAdminsPendingRegistrationReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        // Guard business rule: reminder hanya untuk akun yang sudah pending >= 3 hari.
        if (! $pendingUser->created_at || $pendingUser->created_at->gt(now()->subDays(3))) {
            return;
        }

        $version = (string) optional($pendingUser->updated_at)->timestamp;
        $lockKey = "pending_notify:reminder:user:{$this->pendingUserId}:{$version}";
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
            Log::warning('No admin/super_admin emails for pending registration reminder', [
                'pending_user_id' => $this->pendingUserId,
            ]);

            return;
        }

        $sent = false;
        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new PendingRegistrationAdminMail($pendingUser, true));
                $sent = true;

                Log::info('Pending registration reminder sent to admin', [
                    'pending_user_id' => $pendingUser->id,
                    'admin_email' => $email,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send pending registration reminder to admin', [
                    'pending_user_id' => $pendingUser->id,
                    'admin_email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($sent) {
            Cache::put($lockKey, true, now()->addHours(12));
        }
    }
}
