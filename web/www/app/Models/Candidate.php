<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candidate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'election_id',
        'name_en',
        'name_sk',
        'description_en',
        'description_sk',
        'blockchain_candidate_id',
        'display_order',
    ];

    /**
     * Get the election that owns the candidate.
     */
    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    /**
     * Scope a query to order by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    /**
     * Get the name in the specified locale.
     */
    public function getName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return $locale === 'sk' ? $this->name_sk : $this->name_en;
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
