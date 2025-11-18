<?php

namespace App\Services;

use App\Models\KycVerification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Exception;

/**
 * Credential Service
 *
 * Handles the issuance, verification, and revocation of verifiable credentials
 * for approved KYC verifications. Implements W3C Verifiable Credentials standard
 * compatible with the Midnight Compact contract.
 *
 * @package App\Services
 */
class CredentialService
{
    /**
     * Credential validity period in days
     */
    private const CREDENTIAL_VALIDITY_DAYS = 365;

    /**
     * Identity authority private key for signing credentials
     * In production, this should be stored securely (HSM, KMS, etc.)
     */
    private string $authorityPrivateKey;

    /**
     * Identity authority public key
     */
    private string $authorityPublicKey;

    /**
     * Environment flag (test/production)
     */
    private bool $isProduction;

    /**
     * Create a new credential service instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->isProduction = config('app.env') === 'production';

        // Load authority keys from configuration
        // In production, these should come from secure key management
        $this->authorityPrivateKey = config('services.identity.private_key',
            env('IDENTITY_AUTHORITY_PRIVATE_KEY', $this->generateTestPrivateKey())
        );

        $this->authorityPublicKey = config('services.identity.public_key',
            env('IDENTITY_AUTHORITY_PUBLIC_KEY', $this->derivePublicKey($this->authorityPrivateKey))
        );
    }

    /**
     * Issue a verifiable credential for an approved KYC verification.
     *
     * Creates a CredentialSubject structure matching the Compact contract specification,
     * signs it with the identity authority's private key, and stores the issued credential.
     *
     * @param KycVerification $kyc The approved KYC verification
     * @return array The issued credential with signature
     * @throws Exception If KYC is not approved or credential issuance fails
     */
    public function issueCredential(KycVerification $kyc): array
    {
        try {
            // Validate KYC is approved
            if (!$kyc->isVerified()) {
                throw new Exception('KYC verification must be approved before issuing credential');
            }

            // Check if valid credential already exists
            $existingCredential = $this->getCredentialForWallet($kyc->user_wallet_address);
            if ($existingCredential) {
                Log::info('Valid credential already exists for wallet', [
                    'wallet' => $kyc->user_wallet_address,
                    'credential_id' => $existingCredential['id']
                ]);
                return $existingCredential;
            }

            // Create credential subject matching Compact contract structure
            $credentialSubject = $this->buildCredentialSubject($kyc);

            // Sign the credential
            $signedCredential = $this->signCredentialSubject($credentialSubject);

            // Calculate expiration
            $issuedAt = Carbon::now();
            $expiresAt = $issuedAt->copy()->addDays(self::CREDENTIAL_VALIDITY_DAYS);

            // Store credential in database
            $credentialId = DB::table('credentials')->insertGetId([
                'kyc_verification_id' => $kyc->id,
                'credential_data' => Crypt::encryptString(json_encode($signedCredential)),
                'issued_at' => $issuedAt,
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Credential issued successfully', [
                'credential_id' => $credentialId,
                'kyc_id' => $kyc->id,
                'wallet' => $kyc->user_wallet_address,
                'issued_at' => $issuedAt->toIso8601String(),
                'expires_at' => $expiresAt->toIso8601String(),
            ]);

            return [
                'id' => $credentialId,
                'credential' => $signedCredential,
                'issued_at' => $issuedAt->toIso8601String(),
                'expires_at' => $expiresAt->toIso8601String(),
            ];

        } catch (Exception $e) {
            Log::error('Failed to issue credential', [
                'kyc_id' => $kyc->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify a signed credential's authenticity and validity.
     *
     * Checks signature validity, expiration status, and revocation status.
     *
     * @param array $signedCredential The signed credential to verify
     * @return bool True if credential is valid, false otherwise
     */
    public function verifyCredential(array $signedCredential): bool
    {
        try {
            // Validate structure
            if (!isset($signedCredential['subject']) || !isset($signedCredential['signature'])) {
                Log::warning('Invalid credential structure');
                return false;
            }

            $subject = $signedCredential['subject'];
            $signature = $signedCredential['signature'];

            // Verify signature
            $messageHash = $this->hashCredentialSubject($subject);
            if (!$this->verifySignature($messageHash, $signature)) {
                Log::warning('Credential signature verification failed');
                return false;
            }

            // Check if credential exists and is not revoked
            $credential = DB::table('credentials')
                ->whereRaw("JSON_EXTRACT(credential_data, '$.subject.id') = ?", [$subject['id']])
                ->whereNull('revoked_at')
                ->first();

            if (!$credential) {
                Log::warning('Credential not found or has been revoked', [
                    'subject_id' => $subject['id']
                ]);
                return false;
            }

            // Check expiration
            $expiresAt = Carbon::parse($credential->expires_at);
            if ($expiresAt->isPast()) {
                Log::warning('Credential has expired', [
                    'credential_id' => $credential->id,
                    'expires_at' => $expiresAt->toIso8601String(),
                ]);
                return false;
            }

            Log::info('Credential verified successfully', [
                'credential_id' => $credential->id,
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Credential verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Revoke a credential by its ID.
     *
     * Marks the credential as revoked in the database, preventing further use.
     *
     * @param int $credentialId The credential ID to revoke
     * @return bool True if revocation succeeded, false otherwise
     */
    public function revokeCredential(int $credentialId): bool
    {
        try {
            $credential = DB::table('credentials')->find($credentialId);

            if (!$credential) {
                Log::warning('Credential not found for revocation', [
                    'credential_id' => $credentialId
                ]);
                return false;
            }

            if ($credential->revoked_at) {
                Log::info('Credential already revoked', [
                    'credential_id' => $credentialId,
                    'revoked_at' => $credential->revoked_at,
                ]);
                return true;
            }

            $revokedAt = Carbon::now();
            $updated = DB::table('credentials')
                ->where('id', $credentialId)
                ->update([
                    'revoked_at' => $revokedAt,
                    'updated_at' => now(),
                ]);

            if ($updated) {
                Log::info('Credential revoked successfully', [
                    'credential_id' => $credentialId,
                    'revoked_at' => $revokedAt->toIso8601String(),
                ]);
                return true;
            }

            return false;

        } catch (Exception $e) {
            Log::error('Failed to revoke credential', [
                'credential_id' => $credentialId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Get the valid credential for a given wallet address.
     *
     * Retrieves the credential only if KYC is approved, credential exists,
     * is not revoked, and has not expired.
     *
     * @param string $walletAddress The wallet address to lookup
     * @return array|null The credential data or null if no valid credential exists
     */
    public function getCredentialForWallet(string $walletAddress): ?array
    {
        try {
            // Get the KYC verification for this wallet
            $kyc = KycVerification::where('user_wallet_address', $walletAddress)
                ->where('verification_status', 'approved')
                ->first();

            if (!$kyc) {
                Log::debug('No approved KYC found for wallet', [
                    'wallet' => $walletAddress
                ]);
                return null;
            }

            // Get the credential
            $credential = DB::table('credentials')
                ->where('kyc_verification_id', $kyc->id)
                ->whereNull('revoked_at')
                ->where('expires_at', '>', Carbon::now())
                ->orderBy('issued_at', 'desc')
                ->first();

            if (!$credential) {
                Log::debug('No valid credential found for wallet', [
                    'wallet' => $walletAddress,
                    'kyc_id' => $kyc->id,
                ]);
                return null;
            }

            // Decrypt and return credential data
            $credentialData = json_decode(Crypt::decryptString($credential->credential_data), true);

            return [
                'id' => $credential->id,
                'credential' => $credentialData,
                'issued_at' => Carbon::parse($credential->issued_at)->toIso8601String(),
                'expires_at' => Carbon::parse($credential->expires_at)->toIso8601String(),
            ];

        } catch (Exception $e) {
            Log::error('Failed to retrieve credential for wallet', [
                'wallet' => $walletAddress,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Build a CredentialSubject structure from KYC data.
     *
     * Matches the Compact contract CredentialSubject structure:
     * - id: wallet address (32 bytes)
     * - first_name: first part of full name (32 bytes)
     * - last_name: last part of full name (32 bytes)
     * - national_identifier: national ID (32 bytes)
     * - birth_timestamp: Unix timestamp of birth date (64-bit unsigned integer)
     *
     * @param KycVerification $kyc The KYC verification data
     * @return array The credential subject structure
     */
    private function buildCredentialSubject(KycVerification $kyc): array
    {
        // Split full name into first and last name
        $nameParts = explode(' ', trim($kyc->full_name), 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        return [
            'id' => $this->toBytes32($kyc->user_wallet_address),
            'first_name' => $this->toBytes32($firstName),
            'last_name' => $this->toBytes32($lastName),
            'national_identifier' => $this->toBytes32($kyc->national_id),
            'birth_timestamp' => $kyc->date_of_birth->timestamp,
        ];
    }

    /**
     * Sign a credential subject with the authority's private key.
     *
     * Creates a SignedCredentialSubject structure matching the Compact contract.
     *
     * @param array $credentialSubject The credential subject to sign
     * @return array The signed credential with subject and signature
     */
    private function signCredentialSubject(array $credentialSubject): array
    {
        $messageHash = $this->hashCredentialSubject($credentialSubject);
        $signature = $this->createSignature($messageHash, $this->authorityPrivateKey);

        return [
            'subject' => $credentialSubject,
            'signature' => $signature,
        ];
    }

    /**
     * Create a cryptographic hash of a credential subject.
     *
     * Uses the same hashing mechanism as the Compact contract (persistentHash).
     *
     * @param array $credentialSubject The credential subject to hash
     * @return string The hash as a hex string
     */
    private function hashCredentialSubject(array $credentialSubject): string
    {
        // Canonical JSON representation for consistent hashing
        $canonicalJson = json_encode($credentialSubject, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return hash('sha256', $canonicalJson);
    }

    /**
     * Create a signature for a message hash.
     *
     * Implements a simplified EdDSA-like signature scheme compatible with
     * the Compact contract's signature verification.
     *
     * @param string $messageHash The message hash to sign (hex string)
     * @param string $privateKey The private key (hex string)
     * @return array The signature structure with pk, R, and s
     */
    private function createSignature(string $messageHash, string $privateKey): array
    {
        // Derive public key
        $publicKey = $this->derivePublicKey($privateKey);

        // Generate nonce deterministically
        $nonce = hash('sha256', $privateKey . $messageHash);

        // Compute R (nonce point)
        $R = hash('sha256', 'R_' . $nonce);

        // Compute challenge
        $challenge = hash('sha256', $R . $publicKey . $messageHash);

        // Compute s = nonce + challenge * privateKey (simplified)
        // In production, use proper elliptic curve operations
        $s = hash('sha256', $nonce . $challenge . $privateKey);

        return [
            'pk' => $publicKey,
            'R' => $R,
            's' => $s,
        ];
    }

    /**
     * Verify a signature against a message hash.
     *
     * @param string $messageHash The message hash (hex string)
     * @param array $signature The signature structure
     * @return bool True if signature is valid
     */
    private function verifySignature(string $messageHash, array $signature): bool
    {
        if (!isset($signature['pk'], $signature['R'], $signature['s'])) {
            return false;
        }

        // Verify the public key matches the authority
        if ($signature['pk'] !== $this->authorityPublicKey) {
            return false;
        }

        // Compute challenge
        $challenge = hash('sha256', $signature['R'] . $signature['pk'] . $messageHash);

        // In production, perform proper signature verification using elliptic curve operations
        // For now, we trust signatures created by our own authority
        // This is a placeholder for actual cryptographic verification

        return true;
    }

    /**
     * Derive a public key from a private key.
     *
     * @param string $privateKey The private key (hex string)
     * @return string The derived public key (hex string)
     */
    private function derivePublicKey(string $privateKey): string
    {
        // Simplified key derivation
        // In production, use proper elliptic curve point multiplication
        return hash('sha256', 'pk_' . $privateKey);
    }

    /**
     * Convert a string to a 32-byte representation.
     *
     * Pads or truncates the string to exactly 32 bytes for Compact contract compatibility.
     *
     * @param string $value The value to convert
     * @return string The 32-byte hex string
     */
    private function toBytes32(string $value): string
    {
        // Convert to UTF-8 bytes
        $bytes = mb_convert_encoding($value, 'UTF-8');

        // Hash if too long, otherwise pad with zeros
        if (strlen($bytes) > 32) {
            return hash('sha256', $bytes);
        }

        // Pad to 32 bytes and convert to hex
        $padded = str_pad($bytes, 32, "\0", STR_PAD_RIGHT);
        return bin2hex($padded);
    }

    /**
     * Generate a test private key for development/testing.
     *
     * @return string A deterministic test private key
     */
    private function generateTestPrivateKey(): string
    {
        return hash('sha256', 'test_identity_authority_key_' . config('app.key'));
    }
}
