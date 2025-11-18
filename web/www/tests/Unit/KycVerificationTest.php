<?php

namespace Tests\Unit;

use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Carbon\Carbon;

class KycVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test model encrypts data correctly when saved.
     */
    public function test_model_encrypts_data_correctly_when_saved(): void
    {
        $kyc = KycVerification::create([
            'user_wallet_address' => '0xTEST123',
            'full_name' => 'Test User',
            'national_id' => '123456789',
            'date_of_birth' => '1990-01-01',
            'nationality' => 'SK',
            'verification_status' => 'pending',
        ]);

        // Get raw database record
        $rawRecord = DB::table('kyc_verifications')->where('id', $kyc->id)->first();

        // Full name and national_id should be encrypted in the database
        $this->assertNotEquals('Test User', $rawRecord->full_name);
        $this->assertNotEquals('123456789', $rawRecord->national_id);

        // The encrypted values should be different from the plaintext
        $this->assertStringStartsWith('eyJpdiI6', $rawRecord->full_name); // Encrypted data starts with this
        $this->assertStringStartsWith('eyJpdiI6', $rawRecord->national_id);
    }

    /**
     * Test model decrypts data correctly when retrieved.
     */
    public function test_model_decrypts_data_correctly_when_retrieved(): void
    {
        $originalName = 'Jan Novak';
        $originalId = '987654321';

        $kyc = KycVerification::create([
            'user_wallet_address' => '0xTEST456',
            'full_name' => $originalName,
            'national_id' => $originalId,
            'date_of_birth' => '1985-05-15',
            'nationality' => 'SK',
            'verification_status' => 'pending',
        ]);

        // Retrieve the model from the database
        $retrieved = KycVerification::find($kyc->id);

        // The decrypted values should match the original
        $this->assertEquals($originalName, $retrieved->full_name);
        $this->assertEquals($originalId, $retrieved->national_id);
    }

    /**
     * Test isVerified method returns true for approved status.
     */
    public function test_is_verified_returns_true_for_approved_status(): void
    {
        $kyc = KycVerification::factory()->approved()->create();

        $this->assertTrue($kyc->isVerified());
    }

    /**
     * Test isVerified method returns false for pending status.
     */
    public function test_is_verified_returns_false_for_pending_status(): void
    {
        $kyc = KycVerification::factory()->pending()->create();

        $this->assertFalse($kyc->isVerified());
    }

    /**
     * Test isVerified method returns false for rejected status.
     */
    public function test_is_verified_returns_false_for_rejected_status(): void
    {
        $kyc = KycVerification::factory()->rejected()->create();

        $this->assertFalse($kyc->isVerified());
    }

    /**
     * Test isAdult method returns true for 18+ years old.
     */
    public function test_is_adult_returns_true_for_18_plus_years_old(): void
    {
        $kyc = KycVerification::factory()->create([
            'date_of_birth' => Carbon::now()->subYears(18), // Exactly 18
        ]);

        $this->assertTrue($kyc->isAdult());

        // Test with older age
        $kyc->date_of_birth = Carbon::now()->subYears(25);
        $kyc->save();

        $this->assertTrue($kyc->isAdult());
    }

    /**
     * Test isAdult method returns false for under 18 years old.
     */
    public function test_is_adult_returns_false_for_under_18_years_old(): void
    {
        $kyc = KycVerification::factory()->minor()->create();

        $this->assertFalse($kyc->isAdult());

        // Test with someone who will turn 18 tomorrow
        $kyc->date_of_birth = Carbon::now()->subYears(18)->addDay();
        $kyc->save();

        $this->assertFalse($kyc->isAdult());
    }

    /**
     * Test isAdult method returns false when date_of_birth is null.
     */
    public function test_is_adult_returns_false_when_date_of_birth_is_null(): void
    {
        $kyc = new KycVerification([
            'user_wallet_address' => '0xTEST789',
            'full_name' => 'Test User',
            'national_id' => '123456789',
            'nationality' => 'SK',
        ]);

        $this->assertFalse($kyc->isAdult());
    }

    /**
     * Test isSlovak method returns true for SK nationality.
     */
    public function test_is_slovak_returns_true_for_sk_nationality(): void
    {
        $kyc = KycVerification::factory()->create([
            'nationality' => 'SK',
        ]);

        $this->assertTrue($kyc->isSlovak());
    }

    /**
     * Test isSlovak method returns false for non-SK nationality.
     */
    public function test_is_slovak_returns_false_for_non_sk_nationality(): void
    {
        $nonSlovakNationalities = ['CZ', 'PL', 'HU', 'AT', 'DE', 'US', 'GB'];

        foreach ($nonSlovakNationalities as $nationality) {
            $kyc = KycVerification::factory()->create([
                'nationality' => $nationality,
            ]);

            $this->assertFalse($kyc->isSlovak(), "Failed for nationality: {$nationality}");
        }
    }

    /**
     * Test date_of_birth is cast to Carbon instance.
     */
    public function test_date_of_birth_is_cast_to_carbon_instance(): void
    {
        $kyc = KycVerification::factory()->create([
            'date_of_birth' => '1990-06-15',
        ]);

        $this->assertInstanceOf(Carbon::class, $kyc->date_of_birth);
        $this->assertEquals('1990-06-15', $kyc->date_of_birth->format('Y-m-d'));
    }

    /**
     * Test verified_at is cast to Carbon instance.
     */
    public function test_verified_at_is_cast_to_carbon_instance(): void
    {
        $kyc = KycVerification::factory()->approved()->create();

        $this->assertInstanceOf(Carbon::class, $kyc->verified_at);
    }

    /**
     * Test verified_at is null for pending KYC.
     */
    public function test_verified_at_is_null_for_pending_kyc(): void
    {
        $kyc = KycVerification::factory()->pending()->create();

        $this->assertNull($kyc->verified_at);
    }

    /**
     * Test verifiedBy relationship returns User instance.
     */
    public function test_verified_by_relationship_returns_user_instance(): void
    {
        $admin = User::factory()->create();
        $kyc = KycVerification::factory()->approved()->create([
            'verified_by' => $admin->id,
        ]);

        $this->assertInstanceOf(User::class, $kyc->verifiedBy);
        $this->assertEquals($admin->id, $kyc->verifiedBy->id);
        $this->assertEquals($admin->email, $kyc->verifiedBy->email);
    }

    /**
     * Test verifiedBy relationship is null for pending KYC.
     */
    public function test_verified_by_relationship_is_null_for_pending_kyc(): void
    {
        $kyc = KycVerification::factory()->pending()->create();

        $this->assertNull($kyc->verifiedBy);
    }

    /**
     * Test model has correct fillable attributes.
     */
    public function test_model_has_correct_fillable_attributes(): void
    {
        $expectedFillable = [
            'user_wallet_address',
            'full_name',
            'national_id',
            'date_of_birth',
            'nationality',
            'verification_status',
            'rejection_reason',
        ];

        $kyc = new KycVerification();
        $fillable = $kyc->getFillable();

        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable, "Expected {$attribute} to be fillable");
        }
    }

    /**
     * Test encryption survives database round-trip.
     */
    public function test_encryption_survives_database_round_trip(): void
    {
        $originalData = [
            'user_wallet_address' => '0xABCDEF123',
            'full_name' => 'Peter Mráz',
            'national_id' => '555666777',
            'date_of_birth' => '1988-12-25',
            'nationality' => 'SK',
            'verification_status' => 'pending',
        ];

        $kyc = KycVerification::create($originalData);
        $kycId = $kyc->id;

        // Clear the model from memory
        unset($kyc);

        // Retrieve again from database
        $retrieved = KycVerification::find($kycId);

        $this->assertEquals($originalData['full_name'], $retrieved->full_name);
        $this->assertEquals($originalData['national_id'], $retrieved->national_id);
        $this->assertEquals($originalData['user_wallet_address'], $retrieved->user_wallet_address);
    }

    /**
     * Test updating encrypted fields works correctly.
     */
    public function test_updating_encrypted_fields_works_correctly(): void
    {
        $kyc = KycVerification::factory()->create([
            'full_name' => 'Original Name',
            'national_id' => '111111111',
        ]);

        $kyc->update([
            'full_name' => 'Updated Name',
            'national_id' => '222222222',
        ]);

        $kyc->refresh();

        $this->assertEquals('Updated Name', $kyc->full_name);
        $this->assertEquals('222222222', $kyc->national_id);

        // Check raw database
        $rawRecord = DB::table('kyc_verifications')->where('id', $kyc->id)->first();
        $this->assertNotEquals('Updated Name', $rawRecord->full_name);
        $this->assertNotEquals('222222222', $rawRecord->national_id);
    }

    /**
     * Test age calculation is accurate for edge cases.
     */
    public function test_age_calculation_is_accurate_for_edge_cases(): void
    {
        // Test someone who turns 18 today
        $kyc = KycVerification::factory()->create([
            'date_of_birth' => Carbon::now()->subYears(18),
        ]);
        $this->assertTrue($kyc->isAdult(), 'Person who turns 18 today should be considered adult');

        // Test someone who turns 18 tomorrow
        $kyc->date_of_birth = Carbon::now()->subYears(18)->addDay();
        $kyc->save();
        $this->assertFalse($kyc->isAdult(), 'Person who turns 18 tomorrow should not be considered adult');

        // Test someone who turned 18 yesterday
        $kyc->date_of_birth = Carbon::now()->subYears(18)->subDay();
        $kyc->save();
        $this->assertTrue($kyc->isAdult(), 'Person who turned 18 yesterday should be considered adult');
    }

    /**
     * Test different encryption for same values.
     */
    public function test_different_encryption_for_same_values(): void
    {
        $kyc1 = KycVerification::create([
            'user_wallet_address' => '0xWALLET1',
            'full_name' => 'Same Name',
            'national_id' => '123456789',
            'date_of_birth' => '1990-01-01',
            'nationality' => 'SK',
        ]);

        $kyc2 = KycVerification::create([
            'user_wallet_address' => '0xWALLET2',
            'full_name' => 'Same Name',
            'national_id' => '987654321',
            'date_of_birth' => '1990-01-01',
            'nationality' => 'SK',
        ]);

        // Get raw database records
        $raw1 = DB::table('kyc_verifications')->where('id', $kyc1->id)->first();
        $raw2 = DB::table('kyc_verifications')->where('id', $kyc2->id)->first();

        // Even though the plaintext is the same, encrypted values should be different
        // (due to different initialization vectors)
        $this->assertNotEquals($raw1->full_name, $raw2->full_name);

        // But when decrypted, they should be the same
        $this->assertEquals($kyc1->full_name, $kyc2->full_name);
    }

    /**
     * Test query with where clause on encrypted field.
     */
    public function test_query_with_where_clause_on_encrypted_field(): void
    {
        // Note: Direct WHERE queries on encrypted fields won't work as expected
        // This test demonstrates that limitation
        $targetNationalId = '123456789';

        $kyc = KycVerification::factory()->create([
            'national_id' => $targetNationalId,
        ]);

        // This works because Laravel decrypts after retrieval
        $found = KycVerification::all()->first(function ($k) use ($targetNationalId) {
            return $k->national_id === $targetNationalId;
        });

        $this->assertNotNull($found);
        $this->assertEquals($targetNationalId, $found->national_id);
    }

    /**
     * Test timestamps are automatically managed.
     */
    public function test_timestamps_are_automatically_managed(): void
    {
        $beforeCreate = Carbon::now()->subSecond();

        $kyc = KycVerification::factory()->create();

        $afterCreate = Carbon::now()->addSecond();

        $this->assertNotNull($kyc->created_at);
        $this->assertNotNull($kyc->updated_at);
        $this->assertTrue($kyc->created_at->between($beforeCreate, $afterCreate));
        $this->assertTrue($kyc->updated_at->between($beforeCreate, $afterCreate));

        sleep(2); // Increase sleep to ensure timestamp difference

        $kyc->fresh()->update(['nationality' => 'SK']); // Use fresh() to reload model

        $this->assertTrue($kyc->fresh()->updated_at->greaterThan($kyc->created_at));
    }

    /**
     * Test model can handle special characters in encrypted fields.
     */
    public function test_model_can_handle_special_characters_in_encrypted_fields(): void
    {
        $specialNames = [
            'Ján Kováč',
            'Mária Šťastná',
            'Ľubomír Žák',
            "O'Brien-Smith",
            'François Müller',
        ];

        foreach ($specialNames as $name) {
            $kyc = KycVerification::factory()->create([
                'full_name' => $name,
            ]);

            $retrieved = KycVerification::find($kyc->id);
            $this->assertEquals($name, $retrieved->full_name, "Failed to handle name: {$name}");
        }
    }

    /**
     * Test rejection_reason can be null.
     */
    public function test_rejection_reason_can_be_null(): void
    {
        $kyc = KycVerification::factory()->pending()->create();

        $this->assertNull($kyc->rejection_reason);
    }

    /**
     * Test rejection_reason is set for rejected KYC.
     */
    public function test_rejection_reason_is_set_for_rejected_kyc(): void
    {
        $reason = 'Documents do not match the information provided';
        $kyc = KycVerification::factory()->rejected()->create([
            'rejection_reason' => $reason,
        ]);

        $this->assertEquals($reason, $kyc->rejection_reason);
    }

    /**
     * Test blockchain_tx_hash is set for approved KYC.
     */
    public function test_blockchain_tx_hash_is_set_for_approved_kyc(): void
    {
        $txHash = '0xABCDEF1234567890';
        $kyc = KycVerification::factory()->approved()->create([
            'blockchain_tx_hash' => $txHash,
        ]);

        $this->assertEquals($txHash, $kyc->blockchain_tx_hash);
    }

    /**
     * Test blockchain_tx_hash is null for pending KYC.
     */
    public function test_blockchain_tx_hash_is_null_for_pending_kyc(): void
    {
        $kyc = KycVerification::factory()->pending()->create();

        $this->assertNull($kyc->blockchain_tx_hash);
    }
}
