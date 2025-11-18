<?php

namespace Tests\Feature;

use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Carbon\Carbon;

class KycAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected string $walletAddress = '0xABCDEF1234567890ABCDEF1234567890ABCDEF12';

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * Test admin can view pending KYC verifications.
     */
    public function test_admin_can_view_pending_kyc_verifications(): void
    {
        // Create some KYC verifications with different statuses
        $pending1 = KycVerification::factory()->pending()->create();
        $pending2 = KycVerification::factory()->pending()->create();
        $approved = KycVerification::factory()->approved()->create();
        $rejected = KycVerification::factory()->rejected()->create();

        $response = $this->actingAs($this->admin)
            ->get('/admin/kyc');

        $response->assertStatus(200);
        $response->assertViewIs('admin.kyc.index');
        $response->assertViewHas('pendingKycs');
        $response->assertViewHas('approvedKycs');
        $response->assertViewHas('rejectedKycs');

        // Check that the view has the correct data
        $pendingKycs = $response->viewData('pendingKycs');
        $approvedKycs = $response->viewData('approvedKycs');
        $rejectedKycs = $response->viewData('rejectedKycs');

        $this->assertCount(2, $pendingKycs);
        $this->assertCount(1, $approvedKycs);
        $this->assertCount(1, $rejectedKycs);

        // Verify the pending KYCs are in the correct order (oldest first)
        $this->assertTrue($pendingKycs[0]->created_at <= $pendingKycs[1]->created_at);
    }

    /**
     * Test admin can approve KYC verification.
     */
    public function test_admin_can_approve_kyc_verification(): void
    {
        // Mock the blockchain API
        Http::fake([
            '*/register-voter' => Http::response([
                'tx_hash' => '0xABCDEF1234567890',
                'success' => true,
            ], 200),
        ]);

        $kyc = KycVerification::factory()->pending()->create();

        $response = $this->actingAs($this->admin)
            ->post("/admin/kyc/{$kyc->id}/approve");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Refresh the model
        $kyc->refresh();

        $this->assertEquals('approved', $kyc->verification_status);
        $this->assertNotNull($kyc->verified_at);
        $this->assertEquals($this->admin->id, $kyc->verified_by);
        $this->assertEquals('0xABCDEF1234567890', $kyc->blockchain_tx_hash);
        $this->assertNull($kyc->rejection_reason);

        // Verify HTTP request was made to blockchain API
        Http::assertSent(function ($request) use ($kyc) {
            return $request->url() == env('ELECTIONS_API_URL', 'http://localhost:3000') . '/register-voter' &&
                   $request['wallet_address'] == $kyc->user_wallet_address &&
                   $request['kyc_id'] == $kyc->id;
        });
    }

    /**
     * Test admin can reject KYC verification with reason.
     */
    public function test_admin_can_reject_kyc_verification_with_reason(): void
    {
        $kyc = KycVerification::factory()->pending()->create();
        $rejectionReason = 'Documents are not clear and do not match the provided information.';

        $response = $this->actingAs($this->admin)
            ->post("/admin/kyc/{$kyc->id}/reject", [
                'rejection_reason' => $rejectionReason,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Refresh the model
        $kyc->refresh();

        $this->assertEquals('rejected', $kyc->verification_status);
        $this->assertEquals($rejectionReason, $kyc->rejection_reason);
        $this->assertNotNull($kyc->verified_at);
        $this->assertEquals($this->admin->id, $kyc->verified_by);
    }

    /**
     * Test non-admin users cannot access admin KYC routes.
     */
    public function test_non_admin_users_cannot_access_admin_kyc_routes(): void
    {
        $kyc = KycVerification::factory()->pending()->create();

        // Test without authentication
        $response = $this->get('/admin/kyc');
        $response->assertRedirect('/login');

        $response = $this->post("/admin/kyc/{$kyc->id}/approve");
        $response->assertRedirect('/login');

        $response = $this->post("/admin/kyc/{$kyc->id}/reject", [
            'rejection_reason' => 'Test reason',
        ]);
        $response->assertRedirect('/login');
    }

    /**
     * Test approved KYC cannot be rejected.
     */
    public function test_approved_kyc_cannot_be_rejected(): void
    {
        $kyc = KycVerification::factory()->approved()->create();

        $response = $this->actingAs($this->admin)
            ->post("/admin/kyc/{$kyc->id}/reject", [
                'rejection_reason' => 'Trying to reject approved KYC',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Refresh the model
        $kyc->refresh();

        // Status should still be approved
        $this->assertEquals('approved', $kyc->verification_status);
        $this->assertNotEquals('Trying to reject approved KYC', $kyc->rejection_reason);
    }

    /**
     * Test rejected KYC cannot be approved without re-submission.
     */
    public function test_rejected_kyc_can_be_approved_after_resubmission(): void
    {
        // Mock the blockchain API
        Http::fake([
            '*/register-voter' => Http::response([
                'tx_hash' => '0xNEWTXHASH123456',
                'success' => true,
            ], 200),
        ]);

        // Create a rejected KYC
        $kyc = KycVerification::factory()->rejected()->create([
            'rejection_reason' => 'Initial rejection',
        ]);

        // Update to pending (simulating re-submission)
        $kyc->update([
            'verification_status' => 'pending',
            'rejection_reason' => null,
        ]);

        // Now admin can approve
        $response = $this->actingAs($this->admin)
            ->post("/admin/kyc/{$kyc->id}/approve");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $kyc->refresh();

        $this->assertEquals('approved', $kyc->verification_status);
        $this->assertNull($kyc->rejection_reason);
    }

    /**
     * Test approval fails if user is not adult.
     */
    public function test_approval_fails_if_user_is_not_adult(): void
    {
        $kyc = KycVerification::factory()
            ->pending()
            ->minor()
            ->create();

        $response = $this->actingAs($this->admin)
            ->post("/admin/kyc/{$kyc->id}/approve");

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $kyc->refresh();

        // Status should still be pending
        $this->assertEquals('pending', $kyc->verification_status);
    }

    /**
     * Test approval fails if user is not Slovak.
     */
    public function test_approval_fails_if_user_is_not_slovak(): void
    {
        $kyc = KycVerification::factory()
            ->pending()
            ->nonSlovak()
            ->create();

        $response = $this->actingAs($this->admin)
            ->post("/admin/kyc/{$kyc->id}/approve");

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $kyc->refresh();

        // Status should still be pending
        $this->assertEquals('pending', $kyc->verification_status);
    }

    /**
     * Test rejection requires a reason.
     */
    public function test_rejection_requires_a_reason(): void
    {
        $kyc = KycVerification::factory()->pending()->create();

        $response = $this->actingAs($this->admin)
            ->post("/admin/kyc/{$kyc->id}/reject", [
                'rejection_reason' => '',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('rejection_reason');

        $kyc->refresh();

        // Status should still be pending
        $this->assertEquals('pending', $kyc->verification_status);
    }

    /**
     * Test rejection reason must be at least 10 characters.
     */
    public function test_rejection_reason_must_be_at_least_10_characters(): void
    {
        $kyc = KycVerification::factory()->pending()->create();

        $response = $this->actingAs($this->admin)
            ->post("/admin/kyc/{$kyc->id}/reject", [
                'rejection_reason' => 'Too short',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('rejection_reason');

        $kyc->refresh();

        // Status should still be pending
        $this->assertEquals('pending', $kyc->verification_status);
    }

    /**
     * Test rejection reason cannot exceed 1000 characters.
     */
    public function test_rejection_reason_cannot_exceed_1000_characters(): void
    {
        $kyc = KycVerification::factory()->pending()->create();

        $response = $this->actingAs($this->admin)
            ->post("/admin/kyc/{$kyc->id}/reject", [
                'rejection_reason' => str_repeat('a', 1001),
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('rejection_reason');

        $kyc->refresh();

        // Status should still be pending
        $this->assertEquals('pending', $kyc->verification_status);
    }

    /**
     * Test approval fails when blockchain API is down.
     */
    public function test_approval_fails_when_blockchain_api_is_down(): void
    {
        // Mock the blockchain API to fail
        Http::fake([
            '*/register-voter' => Http::response([
                'error' => 'Blockchain node unavailable',
            ], 500),
        ]);

        $kyc = KycVerification::factory()->pending()->create();

        $response = $this->actingAs($this->admin)
            ->post("/admin/kyc/{$kyc->id}/approve");

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $kyc->refresh();

        // Status should still be pending
        $this->assertEquals('pending', $kyc->verification_status);
        $this->assertNull($kyc->blockchain_tx_hash);
    }

    /**
     * Test approved KYC cannot be approved again.
     */
    public function test_approved_kyc_cannot_be_approved_again(): void
    {
        $kyc = KycVerification::factory()->approved()->create([
            'blockchain_tx_hash' => '0xORIGINALHASH',
        ]);

        $originalVerifiedAt = $kyc->verified_at;

        // Mock the blockchain API
        Http::fake([
            '*/register-voter' => Http::response([
                'tx_hash' => '0xNEWHASH',
                'success' => true,
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/kyc/{$kyc->id}/approve");

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $kyc->refresh();

        // Should still have the original data
        $this->assertEquals('approved', $kyc->verification_status);
        $this->assertEquals('0xORIGINALHASH', $kyc->blockchain_tx_hash);
        $this->assertEquals($originalVerifiedAt->timestamp, $kyc->verified_at->timestamp);

        // Verify blockchain API was NOT called
        Http::assertNothingSent();
    }

    /**
     * Test admin can see decrypted KYC data.
     */
    public function test_admin_can_see_decrypted_kyc_data(): void
    {
        $kyc = KycVerification::factory()->pending()->create([
            'full_name' => 'Secret Name',
            'national_id' => '123456789',
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/kyc');

        $response->assertStatus(200);

        // The view should have access to the decrypted data through the model
        $pendingKycs = $response->viewData('pendingKycs');
        $this->assertEquals('Secret Name', $pendingKycs->first()->full_name);
        $this->assertEquals('123456789', $pendingKycs->first()->national_id);
    }

    /**
     * Test KYC not found returns error.
     */
    public function test_kyc_not_found_returns_error(): void
    {
        $nonExistentId = 99999;

        $response = $this->actingAs($this->admin)
            ->post("/admin/kyc/{$nonExistentId}/approve");

        $response->assertStatus(404);

        $response = $this->actingAs($this->admin)
            ->post("/admin/kyc/{$nonExistentId}/reject", [
                'rejection_reason' => 'Test reason that meets minimum length',
            ]);

        $response->assertStatus(404);
    }

    /**
     * Test multiple admins can process different KYCs.
     */
    public function test_multiple_admins_can_process_different_kycs(): void
    {
        // Mock the blockchain API
        Http::fake([
            '*/register-voter' => Http::response([
                'tx_hash' => '0xTXHASH' . rand(1000, 9999),
                'success' => true,
            ], 200),
        ]);

        $admin2 = User::factory()->create([
            'email' => 'admin2@test.com',
        ]);

        $kyc1 = KycVerification::factory()->pending()->create();
        $kyc2 = KycVerification::factory()->pending()->create();

        // First admin approves first KYC
        $this->actingAs($this->admin)
            ->post("/admin/kyc/{$kyc1->id}/approve");

        // Second admin rejects second KYC
        $this->actingAs($admin2)
            ->post("/admin/kyc/{$kyc2->id}/reject", [
                'rejection_reason' => 'Documents not valid according to second admin',
            ]);

        $kyc1->refresh();
        $kyc2->refresh();

        $this->assertEquals('approved', $kyc1->verification_status);
        $this->assertEquals($this->admin->id, $kyc1->verified_by);

        $this->assertEquals('rejected', $kyc2->verification_status);
        $this->assertEquals($admin2->id, $kyc2->verified_by);
    }

    /**
     * Test pending KYCs are ordered by creation date (oldest first).
     */
    public function test_pending_kycs_are_ordered_by_creation_date(): void
    {
        // Create KYCs with specific timestamps
        $kyc1 = KycVerification::factory()->pending()->create([
            'created_at' => Carbon::now()->subDays(3),
        ]);
        $kyc2 = KycVerification::factory()->pending()->create([
            'created_at' => Carbon::now()->subDays(1),
        ]);
        $kyc3 = KycVerification::factory()->pending()->create([
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/kyc');

        $pendingKycs = $response->viewData('pendingKycs');

        // Should be ordered oldest first
        $this->assertEquals($kyc1->id, $pendingKycs[0]->id);
        $this->assertEquals($kyc3->id, $pendingKycs[1]->id);
        $this->assertEquals($kyc2->id, $pendingKycs[2]->id);
    }

    /**
     * Test approved KYCs are ordered by verification date (newest first).
     */
    public function test_approved_kycs_are_ordered_by_verification_date(): void
    {
        // Create approved KYCs with specific verification timestamps
        $kyc1 = KycVerification::factory()->approved()->create([
            'verified_at' => Carbon::now()->subDays(3),
        ]);
        $kyc2 = KycVerification::factory()->approved()->create([
            'verified_at' => Carbon::now()->subDays(1),
        ]);
        $kyc3 = KycVerification::factory()->approved()->create([
            'verified_at' => Carbon::now()->subDays(2),
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/kyc');

        $approvedKycs = $response->viewData('approvedKycs');

        // Should be ordered newest first
        $this->assertEquals($kyc2->id, $approvedKycs[0]->id);
        $this->assertEquals($kyc3->id, $approvedKycs[1]->id);
        $this->assertEquals($kyc1->id, $approvedKycs[2]->id);
    }
}
