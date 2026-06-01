<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApprovalPersistsReviewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_stores_registration_reviewed_by_from_authenticated_user(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'name' => 'Admin Uji',
        ]);

        $member = User::factory()->create([
            'role' => 'user',
            'is_active' => false,
            'registration_status' => User::REGISTRATION_PENDING,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/users/{$member->id}/approve", []);

        $response->assertOk();

        $member->refresh();

        $this->assertSame($admin->id, (int) $member->registration_reviewed_by);
        $this->assertNotNull($member->registration_reviewed_at);
        $this->assertSame(User::REGISTRATION_APPROVED, $member->registration_status);
        $this->assertTrue($member->is_active);
    }

    public function test_approve_ignores_registration_reviewed_by_in_body_when_different_from_actor(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        $otherAdmin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        $member = User::factory()->create([
            'role' => 'user',
            'is_active' => false,
            'registration_status' => User::REGISTRATION_PENDING,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/users/{$member->id}/approve", [
            'registration_reviewed_by' => $otherAdmin->id,
        ]);

        $response->assertOk();

        $member->refresh();

        $this->assertSame($admin->id, (int) $member->registration_reviewed_by);
    }

    public function test_reject_stores_registration_reviewed_by_from_authenticated_user(): void
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
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/users/{$member->id}/reject", []);

        $response->assertOk();

        $member->refresh();

        $this->assertSame($admin->id, (int) $member->registration_reviewed_by);
        $this->assertSame(User::REGISTRATION_REJECTED, $member->registration_status);
    }

    public function test_approve_ignores_invalid_registration_reviewed_by_in_body_and_succeeds(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        $member = User::factory()->create([
            'role' => 'user',
            'is_active' => false,
            'registration_status' => User::REGISTRATION_PENDING,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/users/{$member->id}/approve", [
            'registration_reviewed_by' => 999_999,
        ]);

        $response->assertOk();

        $member->refresh();
        $this->assertSame($admin->id, (int) $member->registration_reviewed_by);
        $this->assertSame(User::REGISTRATION_APPROVED, $member->registration_status);
    }
}
