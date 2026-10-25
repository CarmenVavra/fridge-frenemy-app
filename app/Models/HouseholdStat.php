<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hero and guilt counters of a household.
 *
 * @property string $household_id
 * @property int $saved_count
 * @property int $trashed_count
 */
class HouseholdStat extends Model
{
    protected $primaryKey = 'household_id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'saved_count',
        'trashed_count',
    ];

    /**
     * @var array<string, int>
     */
    protected $attributes = [
        'saved_count' => 0,
        'trashed_count' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'saved_count' => 'integer',
            'trashed_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Household, $this>
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /**
     * Share of rescued items between 0 and 1, or null while nothing has been eaten or thrown away yet.
     */
    public function rescueRatio(): ?float
    {
        $resolved = $this->saved_count + $this->trashed_count;

        return $resolved === 0 ? null : $this->saved_count / $resolved;
    }
}
