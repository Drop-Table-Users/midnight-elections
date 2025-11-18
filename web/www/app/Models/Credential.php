<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Credential Model
 *
 * Represents a verifiable credential issued for an approved KYC verification.
 * Stores encrypted credential data and tracks lifecycle (issuance, expiration, revocation).
 *
 * @property int $id
 * @property int $kyc_verification_id
 * @property string $credential_data
 * @property Carbon $issued_at
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @package App\Models
 */
class Credential extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'kyc_verification_id',
        'credential_data',
        'issued_at',
        'expires_at',
        'revoked_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'credential_data' => 'encrypted',
    ];

    /**
     * Get the KYC verification associated with this credential.
     *
     * @return BelongsTo
     */
    public function kycVerification(): BelongsTo
    {
        return $this->belongsTo(KycVerification::class);
    }

    /**
     * Check if the credential is currently valid.
     *
     * A credential is valid if it is not revoked and has not expired.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }

    /**
     * Check if the credential is revoked.
     *
     * @return bool
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Check if the credential has expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Get the number of days until expiration.
     *
     * @return int
     */
    public function daysUntilExpiration(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return (int) Carbon::now()->diffInDays($this->expires_at, false);
    }

    /**
     * Scope a query to only include valid credentials.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query)
    {
        return $query->whereNull('revoked_at')
                    ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope a query to only include revoked credentials.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    /**
     * Scope a query to only include expired credentials.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', Carbon::now());
    }
}
