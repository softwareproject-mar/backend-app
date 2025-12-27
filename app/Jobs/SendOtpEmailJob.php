<?php

namespace App\Jobs;

use App\Mail\SendOtpMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOtpEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $email,
        public string $otp,
        public string $purpose = 'register'
    ) {
    }

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

            // Re-throw to trigger job retry mechanism
            throw $e;
        }
    }
}
