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
        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();

            // User identification
            $table->string('user_wallet_address')->unique()->index();

            // Encrypted PII
            $table->text('full_name');
            $table->text('national_id')->unique();
            $table->text('date_of_birth');

            // Non-sensitive user info
            $table->char('nationality', 2)->default('SK');

            // Verification tracking
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Rejection details
            $table->text('rejection_reason')->nullable();

            // Blockchain integration
            $table->string('blockchain_tx_hash')->nullable()->unique();

            // Timestamps
            $table->timestamps();

            // Indexes for common queries
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_verifications');
    }
};
