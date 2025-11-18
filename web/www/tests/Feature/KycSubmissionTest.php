<?php

namespace Tests\Feature;

use App\Models\KycVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Carbon\Carbon;

class KycSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected string $walletAddress = '0xABCDEF1234567890ABCDEF1234567890ABCDEF12';

    /**
     * Test user can view KYC form.
     */
    public function test_user_can_view_kyc_form(): void
    {
        // Test Slovak route
        $response = $this->withSession(['wallet_address' => $this->walletAddress])
            ->get('/kyc');

        $response->assertStatus(200);
        $response->assertViewIs('kyc.index');
        $response->assertViewHas('walletConnected', true);
        $response->assertViewHas('alreadyVerified', false);

        // Test English route
        $response = $this->withSession(['wallet_address' => $this->walletAddress])
            ->get('/en/kyc');

        $response->assertStatus(200);
        $response->assertViewIs('kyc.index');
    }

    /**
     * Test user cannot submit without wallet connection.
     */
    public function test_user_cannot_submit_without_wallet_connection(): void
    {
        $kycData = [
            'full_name' => 'John Doe',
            'national_id' => '123456789',
            'date_of_birth' => '1990-01-01',
            'nationality' => 'SK',
        ];

        // Test without session
        $response = $this->postJson('/kyc', $kycData);

        $response->assertStatus(400);
        $response->assertJson([
            'status' => 'error',
        ]);
        $response->assertJsonPath('message', function ($message) {
            return str_contains($message, 'Wallet') || str_contains($message, 'connected');
        });
    }

    /**
     * Test user cannot submit if under 18.
     */
    public function test_user_cannot_submit_if_under_18(): void
    {
        $kycData = [
            'full_name' => 'Young Person',
            'national_id' => '123456789',
            'date_of_birth' => Carbon::now()->subYears(17)->format('Y-m-d'), // 17 years old
            'nationality' => 'SK',
        ];

        $response = $this->withSession(['wallet_address' => $this->walletAddress])
            ->postJson('/kyc', $kycData);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
        ]);
        $response->assertJsonPath('message', function ($message) {
            return str_contains($message, '18') || str_contains(strtolower($message), 'age');
        });

        $this->assertDatabaseMissing('kyc_verifications', [
            'user_wallet_address' => $this->walletAddress,
        ]);
    }

    /**
     * Test user cannot submit if not Slovak nationality.
     */
    public function test_user_cannot_submit_if_not_slovak_nationality(): void
    {
        $kycData = [
            'full_name' => 'Foreign Person',
            'national_id' => '123456789',
            'date_of_birth' => '1990-01-01',
            'nationality' => 'CZ', // Czech nationality, not Slovak
        ];

        $response = $this->withSession(['wallet_address' => $this->walletAddress])
            ->postJson('/kyc', $kycData);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
        ]);

        $this->assertDatabaseMissing('kyc_verifications', [
            'user_wallet_address' => $this->walletAddress,
        ]);
    }

    /**
     * Test user can successfully submit valid KYC data.
     */
    public function test_user_can_successfully_submit_valid_kyc_data(): void
    {
        $kycData = [
            'full_name' => 'Jan Novak',
            'national_id' => '123456789',
            'date_of_birth' => '1990-06-15',
            'nationality' => 'SK',
        ];

        $response = $this->withSession(['wallet_address' => $this->walletAddress])
            ->postJson('/kyc', $kycData);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => 'success',
        ]);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'kyc_id',
                'status',
                'submitted_at',
            ],
        ]);

        $this->assertDatabaseHas('kyc_verifications', [
            'user_wallet_address' => $this->walletAddress,
            'verification_status' => 'pending',
            'nationality' => 'SK',
        ]);

        // Verify the record exists
        $kyc = KycVerification::where('user_wallet_address', $this->walletAddress)->first();
        $this->assertNotNull($kyc);
        $this->assertEquals('Jan Novak', $kyc->full_name);
        $this->assertEquals('123456789', $kyc->national_id);
        $this->assertEquals('1990-06-15', $kyc->date_of_birth->format('Y-m-d'));
    }

    /**
     * Test data is properly encrypted in database.
     */
    public function test_data_is_properly_encrypted_in_database(): void
    {
        $kycData = [
            'full_name' => 'Maria Kovacova',
            'national_id' => '987654321',
            'date_of_birth' => '1985-03-20',
            'nationality' => 'SK',
        ];

        $response = $this->withSession(['wallet_address' => $this->walletAddress])
            ->postJson('/kyc', $kycData);

        $response->assertStatus(201);

        // Get the raw database record
        $rawRecord = DB::table('kyc_verifications')
            ->where('user_wallet_address', $this->walletAddress)
            ->first();

        // The encrypted fields should NOT be plaintext in the database
        $this->assertNotEquals('Maria Kovacova', $rawRecord->full_name);
        $this->assertNotEquals('987654321', $rawRecord->national_id);

        // But when accessed through the model, they should be decrypted
        $kyc = KycVerification::where('user_wallet_address', $this->walletAddress)->first();
        $this->assertEquals('Maria Kovacova', $kyc->full_name);
        $this->assertEquals('987654321', $kyc->national_id);
    }

    /**
     * Test duplicate submissions are prevented - same wallet address.
     */
    public function test_duplicate_submission_same_wallet_is_updated(): void
    {
        // First submission
        $firstData = [
            'full_name' => 'Peter Slovak',
            'national_id' => '111111111',
            'date_of_birth' => '1992-01-01',
            'nationality' => 'SK',
        ];

        $response = $this->withSession(['wallet_address' => $this->walletAddress])
            ->postJson('/kyc', $firstData);

        $response->assertStatus(201);

        // Get the first KYC ID
        $firstKycId = KycVerification::where('user_wallet_address', $this->walletAddress)->first()->id;

        // Second submission with same wallet but different data
        $secondData = [
            'full_name' => 'Peter Slovak Updated',
            'national_id' => '222222222',
            'date_of_birth' => '1992-01-01',
            'nationality' => 'SK',
        ];

        $response = $this->withSession(['wallet_address' => $this->walletAddress])
            ->postJson('/kyc', $secondData);

        $response->assertStatus(201);

        // Should still have only one record
        $this->assertEquals(1, KycVerification::where('user_wallet_address', $this->walletAddress)->count());

        // The record should be updated
        $kyc = KycVerification::where('user_wallet_address', $this->walletAddress)->first();
        $this->assertEquals($firstKycId, $kyc->id); // Same ID
        $this->assertEquals('Peter Slovak Updated', $kyc->full_name);
        $this->assertEquals('222222222', $kyc->national_id);
    }

    /**
     * Test duplicate submissions are prevented - same national ID.
     */
    public function test_duplicate_submission_same_national_id_is_rejected(): void
    {
        $sharedNationalId = '555555555';

        // First submission from first wallet
        $firstWallet = '0x1111111111111111111111111111111111111111';
        $firstData = [
            'full_name' => 'First Person',
            'national_id' => $sharedNationalId,
            'date_of_birth' => '1990-05-10',
            'nationality' => 'SK',
        ];

        $response = $this->withSession(['wallet_address' => $firstWallet])
            ->postJson('/kyc', $firstData);

        $response->assertStatus(201);

        // Second submission from different wallet with same national ID
        $secondWallet = '0x2222222222222222222222222222222222222222';
        $secondData = [
            'full_name' => 'Second Person',
            'national_id' => $sharedNationalId, // Same national ID
            'date_of_birth' => '1995-08-15',
            'nationality' => 'SK',
        ];

        $response = $this->withSession(['wallet_address' => $secondWallet])
            ->postJson('/kyc', $secondData);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
        ]);
        $response->assertJsonPath('message', function ($message) {
            return str_contains(strtolower($message), 'national') || str_contains(strtolower($message), 'used');
        });

        // Only the first KYC should exist
        $this->assertEquals(1, KycVerification::count());
        $this->assertDatabaseHas('kyc_verifications', [
            'user_wallet_address' => $firstWallet,
        ]);
        $this->assertDatabaseMissing('kyc_verifications', [
            'user_wallet_address' => $secondWallet,
        ]);
    }

    /**
     * Test that already verified users cannot submit again.
     */
    public function test_already_verified_user_cannot_submit_again(): void
    {
        // Create an already verified KYC
        KycVerification::factory()
            ->approved()
            ->withWallet($this->walletAddress)
            ->create();

        $kycData = [
            'full_name' => 'New Name',
            'national_id' => '999999999',
            'date_of_birth' => '1990-01-01',
            'nationality' => 'SK',
        ];

        $response = $this->withSession(['wallet_address' => $this->walletAddress])
            ->postJson('/kyc', $kycData);

        $response->assertStatus(400);
        $response->assertJson([
            'status' => 'error',
        ]);
        $response->assertJsonPath('message', function ($message) {
            return str_contains(strtolower($message), 'verified') || str_contains(strtolower($message), 'already');
        });
    }

    /**
     * Test KYC status endpoint returns correct information.
     */
    public function test_kyc_status_endpoint_returns_correct_information(): void
    {
        // Test without KYC
        $response = $this->withSession(['wallet_address' => $this->walletAddress])
            ->getJson('/kyc/status');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'kyc_status' => 'not_submitted',
            ],
        ]);

        // Create a pending KYC
        KycVerification::factory()
            ->pending()
            ->withWallet($this->walletAddress)
            ->create();

        $response = $this->withSession(['wallet_address' => $this->walletAddress])
            ->getJson('/kyc/status');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'kyc_status' => 'pending',
                'is_verified' => false,
            ],
        ]);

        $response->assertJsonStructure([
            'status',
            'data' => [
                'kyc_id',
                'kyc_status',
                'submitted_at',
                'verified_at',
                'rejection_reason',
                'is_verified',
            ],
        ]);
    }

    /**
     * Test invalid national ID format is rejected.
     */
    public function test_invalid_national_id_format_is_rejected(): void
    {
        $invalidFormats = [
            '12345', // Too short
            '12345678901234', // Too long
            'ABCDEFGHI', // Letters
            '123-456-789', // Contains dashes
        ];

        foreach ($invalidFormats as $invalidId) {
            $kycData = [
                'full_name' => 'Test Person',
                'national_id' => $invalidId,
                'date_of_birth' => '1990-01-01',
                'nationality' => 'SK',
            ];

            $response = $this->withSession(['wallet_address' => $this->walletAddress])
                ->postJson('/kyc', $kycData);

            $response->assertStatus(422);
            $response->assertJson([
                'status' => 'error',
            ]);
        }
    }

    /**
     * Test required fields validation.
     */
    public function test_required_fields_validation(): void
    {
        $requiredFields = ['full_name', 'national_id', 'date_of_birth', 'nationality'];

        foreach ($requiredFields as $field) {
            $kycData = [
                'full_name' => 'Test Person',
                'national_id' => '123456789',
                'date_of_birth' => '1990-01-01',
                'nationality' => 'SK',
            ];

            // Remove the field being tested
            unset($kycData[$field]);

            $response = $this->withSession(['wallet_address' => $this->walletAddress])
                ->postJson('/kyc', $kycData);

            $response->assertStatus(422);
            $response->assertJson([
                'status' => 'error',
            ]);
        }
    }

    /**
     * Test English language route works correctly.
     */
    public function test_english_language_route_works_correctly(): void
    {
        $kycData = [
            'full_name' => 'John Smith',
            'national_id' => '123456789',
            'date_of_birth' => '1990-01-01',
            'nationality' => 'SK',
        ];

        $response = $this->withSession(['wallet_address' => $this->walletAddress])
            ->postJson('/en/kyc', $kycData);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('kyc_verifications', [
            'user_wallet_address' => $this->walletAddress,
        ]);
    }

    /**
     * Test rejected KYC can be resubmitted.
     */
    public function test_rejected_kyc_can_be_resubmitted(): void
    {
        // Create a rejected KYC
        KycVerification::factory()
            ->rejected()
            ->withWallet($this->walletAddress)
            ->create([
                'rejection_reason' => 'Invalid documents',
            ]);

        $kycData = [
            'full_name' => 'Corrected Name',
            'national_id' => '888888888',
            'date_of_birth' => '1990-01-01',
            'nationality' => 'SK',
        ];

        $response = $this->withSession(['wallet_address' => $this->walletAddress])
            ->postJson('/kyc', $kycData);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => 'success',
        ]);

        // Check that the KYC was updated
        $kyc = KycVerification::where('user_wallet_address', $this->walletAddress)->first();
        $this->assertEquals('pending', $kyc->verification_status);
        $this->assertNull($kyc->rejection_reason);
        $this->assertEquals('Corrected Name', $kyc->full_name);
    }
}
