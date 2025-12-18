<?php

namespace App\Console\Commands;

use App\Services\OtpService;
use Illuminate\Console\Command;

class CleanupExpiredOtps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired OTP records from database';

    /**
     * Execute the console command.
     */
    public function handle(OtpService $otpService): int
    {
        $this->info('Cleaning up expired OTP records...');

        $deletedCount = $otpService->cleanupExpired();

        $this->info("Deleted {$deletedCount} expired OTP record(s).");

        return Command::SUCCESS;
    }
}
