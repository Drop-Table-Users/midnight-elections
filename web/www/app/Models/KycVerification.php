<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class KycVerification extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_wallet_address',
        'full_name',
        'national_id',
        'date_of_birth',
        'nationality',
        'verification_status',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'full_name' => 'encrypted',
        'national_id' => 'encrypted',
    ];

    /**
     * Check if the KYC verification is approved.
     *
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'approved';
    }

    /**
     * Check if the user is 18 years or older.
     *
     * @return bool
     */
    public function isAdult(): bool
    {
        if (!$this->date_of_birth) {
            return false;
        }

        return $this->date_of_birth->diffInYears(Carbon::now()) >= 18;
    }

    /**
     * Check if the user's nationality is Slovak (SK).
     *
     * @return bool
     */
    public function isSlovak(): bool
    {
        return $this->nationality === 'SK';
    }

    /**
     * Get the user who verified this KYC.
     *
     * @return BelongsTo
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the credentials associated with this KYC verification.
     *
     * @return HasMany
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    /**
     * Get the currently valid credential for this KYC verification.
     *
     * @return Credential|null
     */
    public function validCredential(): ?Credential
    {
        return $this->credentials()
            ->valid()
            ->orderBy('issued_at', 'desc')
            ->first();
    }
}
