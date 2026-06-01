<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserDeviceLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_login_succeeds_when_device_matches(): void
    {
        User::factory()->create([
            'email' => 'user-lock@example.test',
            'password' => bcrypt('password12'),
            'role' => 'user',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'device_id' => 'device-a',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user-lock@example.test',
            'password' => 'password12',
            'device_id' => 'device-a',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'user',
                'token',
            ]);
    }

    public function test_user_login_is_rejected_when_device_mismatches(): void
    {
        User::factory()->create([
            'email' => 'user-mismatch@example.test',
            'password' => bcrypt('password12'),
            'role' => 'user',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'device_id' => 'device-a',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user-mismatch@example.test',
            'password' => 'password12',
            'device_id' => 'device-b',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['device_id']);
    }

    public function test_user_login_auto_binds_device_when_null(): void
    {
        $user = User::factory()->create([
            'email' => 'user-rebind@example.test',
            'password' => bcrypt('password12'),
            'role' => 'user',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'device_id' => null,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user-rebind@example.test',
            'password' => 'password12',
            'device_id' => 'device-new',
        ]);

        $response->assertOk();
        $user->refresh();
        $this->assertSame('device-new', $user->device_id);
    }

    public function test_admin_app_login_auto_binds_device_when_null(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-lock@example.test',
            'password' => bcrypt('password12'),
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'device_id' => null,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin-lock@example.test',
            'password' => 'password12',
            'device_id' => 'admin-device',
        ]);

        $response->assertOk();
        $admin->refresh();
        $this->assertSame('admin-device', $admin->device_id);
    }

    public function test_admin_app_login_is_rejected_when_device_mismatches(): void
    {
        User::factory()->create([
            'email' => 'admin-mismatch@example.test',
            'password' => bcrypt('password12'),
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'device_id' => 'device-a',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin-mismatch@example.test',
            'password' => 'password12',
            'device_id' => 'device-b',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['device_id']);
    }

    public function test_admin_web_login_succeeds_without_device_id_when_already_bound(): void
    {
        User::factory()->create([
            'email' => 'admin-web-bound@example.test',
            'password' => bcrypt('password12'),
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'device_id' => 'phone-bound',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin-web-bound@example.test',
            'password' => 'password12',
        ]);

        $response->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_admin_login_succeeds_without_device_id(): void
    {
        User::factory()->create([
            'email' => 'admin-web@example.test',
            'password' => bcrypt('password12'),
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'device_id' => null,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin-web@example.test',
            'password' => 'password12',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'user',
                'token',
            ]);
    }

    public function test_user_login_requires_device_id(): void
    {
        User::factory()->create([
            'email' => 'user-nodevice@example.test',
            'password' => bcrypt('password12'),
            'role' => 'user',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'device_id' => 'device-a',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user-nodevice@example.test',
            'password' => 'password12',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['device_id']);
    }

    public function test_admin_can_reset_device_for_approved_user(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        $member = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'device_id' => 'device-a',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/users/{$member->id}/reset-device");

        $response->assertOk();
        $member->refresh();
        $this->assertNull($member->device_id);
    }

    public function test_admin_cannot_reset_device_for_non_approved_user(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        $member = User::factory()->create([
            'role' => 'user',
            'is_active' => false,
            'registration_status' => User::REGISTRATION_PENDING,
            'device_id' => 'device-a',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/users/{$member->id}/reset-device");

        $response->assertUnprocessable();
        $member->refresh();
        $this->assertSame('device-a', $member->device_id);
    }
}
