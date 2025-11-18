<?php

namespace Database\Factories;

use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KycVerification>
 */
class KycVerificationFactory extends Factory
{
    protected $model = KycVerification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_wallet_address' => '0x' . fake()->sha1(),
            'full_name' => fake()->firstName() . ' ' . fake()->lastName(),
            'national_id' => fake()->numerify('#########'), // 9 digits for Slovak ID
            'date_of_birth' => Carbon::now()->subYears(25)->subDays(rand(0, 3650)), // 25-35 years old
            'nationality' => 'SK',
            'verification_status' => 'pending',
            'verified_at' => null,
            'verified_by' => null,
            'rejection_reason' => null,
            'blockchain_tx_hash' => null,
        ];
    }

    /**
     * Indicate that the KYC verification is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'approved',
            'verified_at' => Carbon::now(),
            'verified_by' => User::factory(),
            'blockchain_tx_hash' => '0x' . fake()->sha256(),
        ]);
    }

    /**
     * Indicate that the KYC verification is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'rejected',
            'verified_at' => Carbon::now(),
            'verified_by' => User::factory(),
            'rejection_reason' => fake()->sentence(10),
        ]);
    }

    /**
     * Indicate that the KYC verification is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'pending',
            'verified_at' => null,
            'verified_by' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Set a specific wallet address.
     */
    public function withWallet(string $walletAddress): static
    {
        return $this->state(fn (array $attributes) => [
            'user_wallet_address' => $walletAddress,
        ]);
    }

    /**
     * Set a specific national ID.
     */
    public function withNationalId(string $nationalId): static
    {
        return $this->state(fn (array $attributes) => [
            'national_id' => $nationalId,
        ]);
    }

    /**
     * Create a minor (under 18).
     */
    public function minor(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_of_birth' => Carbon::now()->subYears(17), // 17 years old
        ]);
    }

    /**
     * Create a non-Slovak nationality.
     */
    public function nonSlovak(): static
    {
        return $this->state(fn (array $attributes) => [
            'nationality' => fake()->randomElement(['CZ', 'PL', 'HU', 'AT', 'DE', 'US']),
        ]);
    }
}
