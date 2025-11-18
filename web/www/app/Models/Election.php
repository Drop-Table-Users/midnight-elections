<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Election extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title_en',
        'title_sk',
        'description_en',
        'description_sk',
        'contract_address',
        'blockchain_election_id',
        'start_date',
        'end_date',
        'status',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    /**
     * Get the candidates for the election.
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class)->orderBy('display_order');
    }

    /**
     * Get the user who created the election.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the election is currently open.
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Check if the election is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Check if the election is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Scope a query to only include open elections.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope a query to only include active elections (currently running).
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'open')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Get the title in the specified locale.
     */
    public function getTitle(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return $locale === 'sk' ? $this->title_sk : $this->title_en;
    }

    /**
     * Get the description in the specified locale.
     */
    public function getDescription(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        return $locale === 'sk' ? $this->description_sk : $this->description_en;
    }
}
