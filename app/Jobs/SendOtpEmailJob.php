<?php

namespace App\Jobs;

use App\Mail\SendOtpMail;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * OTP harus sampai ke inbox pengguna; tanpa ShouldQueue job ini dijalankan
 * langsung saat dispatch() sehingga tidak bergantung pada queue worker / tabel JOBS.
 * (Notifikasi admin lain tetap bisa antri via ShouldQueue.)
 */
class SendOtpEmailJob
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $email,
        public string $otp,
        public string $purpose = 'register'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::to($this->email)->send(new SendOtpMail($this->otp, $this->email, $this->purpose));

            Log::info('OTP email sent successfully', [
                'email' => $this->email,
                'purpose' => $this->purpose,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email', [
                'email' => $this->email,
                'purpose' => $this->purpose,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
