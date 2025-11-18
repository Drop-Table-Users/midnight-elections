<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('credentials', function (Blueprint $table) {
            $table->id();

            // Foreign key to KYC verification
            $table->foreignId('kyc_verification_id')
                ->constrained('kyc_verifications')
                ->cascadeOnDelete();

            // Encrypted credential data (JSON structure)
            $table->text('credential_data');

            // Credential lifecycle timestamps
            $table->timestamp('issued_at');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();

            // Standard timestamps
            $table->timestamps();

            // Indexes for efficient queries
            $table->index('kyc_verification_id');
            $table->index('issued_at');
            $table->index('expires_at');
            $table->index('revoked_at');
            $table->index(['kyc_verification_id', 'revoked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credentials');
    }
};
