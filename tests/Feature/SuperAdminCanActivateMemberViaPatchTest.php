<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperAdminCanActivateMemberViaPatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_patch_is_active_true_on_pending_member_activates_and_approves(): void
    {
        $super = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        $member = User::factory()->create([
            'role' => 'user',
            'is_active' => false,
            'registration_status' => User::REGISTRATION_PENDING,
        ]);

        Sanctum::actingAs($super);

        $response = $this->patchJson("/api/super-admin/users/{$member->id}", [
            'is_active' => true,
        ]);

        $response->assertOk();

        $member->refresh();
        $this->assertTrue($member->is_active);
        $this->assertSame(User::REGISTRATION_APPROVED, $member->registration_status);
    }

    public function test_super_admin_patch_is_active_true_on_rejected_member_activates_and_approves(): void
    {
        $super = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        $member = User::factory()->create([
            'role' => 'user',
            'is_active' => false,
            'registration_status' => User::REGISTRATION_REJECTED,
        ]);

        Sanctum::actingAs($super);

        $response = $this->patchJson("/api/super-admin/users/{$member->id}", [
            'is_active' => true,
        ]);

        $response->assertOk();

        $member->refresh();
        $this->assertTrue($member->is_active);
        $this->assertSame(User::REGISTRATION_APPROVED, $member->registration_status);
    }
}
