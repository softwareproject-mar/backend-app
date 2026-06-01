<?php

namespace Tests\Feature;

use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RejectedUserCanReregisterWithOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_otp_allows_email_when_user_was_rejected(): void
    {
        User::factory()->create([
            'email' => 'tolak@example.test',
            'role' => 'user',
            'registration_status' => User::REGISTRATION_REJECTED,
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/request-otp', [
            'email' => 'tolak@example.test',
        ]);

        $response->assertOk();
    }

    public function test_request_otp_rejects_email_when_user_is_pending(): void
    {
        User::factory()->create([
            'email' => 'tunggu@example.test',
            'role' => 'user',
            'registration_status' => User::REGISTRATION_PENDING,
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/request-otp', [
            'email' => 'tunggu@example.test',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_register_after_otp_resets_rejected_user_to_pending(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'ulang@example.test',
            'name' => 'Lama',
            'role' => 'user',
            'registration_status' => User::REGISTRATION_REJECTED,
            'is_active' => false,
            'registration_reviewed_at' => now(),
            'registration_reviewed_by' => null,
        ]);

        EmailVerification::create([
            'email' => 'ulang@example.test',
            'purpose' => 'register',
            'otp_code' => '123456',
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $response = $this->postJson('/api/auth/register', [
            'email' => 'ulang@example.test',
            'otp' => '123456',
            'name' => 'Baru',
            'password' => 'password12',
            'password_confirmation' => 'password12',
            'device_id' => 'device-ulang-001',
        ]);

        $response->assertCreated();

        $user->refresh();
        $this->assertSame('Baru', $user->name);
        $this->assertSame(User::REGISTRATION_PENDING, $user->registration_status);
        $this->assertNull($user->registration_reviewed_at);
        $this->assertNull($user->registration_reviewed_by);
        $this->assertSame('device-ulang-001', $user->device_id);
        $this->assertTrue(Hash::check('password12', $user->password));
    }
}
