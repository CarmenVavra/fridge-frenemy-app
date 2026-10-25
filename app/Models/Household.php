<?php

namespace App\Models;

use App\Enums\ButlerTone;
use Database\Factories\HouseholdFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * A fridge shared by one or more users (single person, WG or family).
 *
 * @property string $id
 * @property string $name
 * @property ButlerTone $passive_aggressive_tone
 * @property string $invite_code
 * @property-read HouseholdStat|null $stats
 */
class Household extends Model
{
    /** @use HasFactory<HouseholdFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'passive_aggressive_tone',
        'invite_code',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'passive_aggressive_tone' => 'classic',
    ];

    protected static function booted(): void
    {
        static::creating(function (Household $household): void {
            $household->invite_code ??= self::generateInviteCode();
        });

        static::created(function (Household $household): void {
            $household->stats()->create();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'passive_aggressive_tone' => ButlerTone::class,
        ];
    }

    /**
     * Unique, human-typeable code other members use to join the household.
     */
    public static function generateInviteCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (self::where('invite_code', $code)->exists());

        return $code;
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<FridgeItem, $this>
     */
    public function fridgeItems(): HasMany
    {
        return $this->hasMany(FridgeItem::class);
    }

    /**
     * @return HasOne<HouseholdStat, $this>
     */
    public function stats(): HasOne
    {
        return $this->hasOne(HouseholdStat::class);
    }

    /**
     * @return HasMany<ButlerAlert, $this>
     */
    public function butlerAlerts(): HasMany
    {
        return $this->hasMany(ButlerAlert::class);
    }

    /**
     * The stats row, created on demand if it is missing.
     */
    public function statsOrCreate(): HouseholdStat
    {
        return $this->stats()->firstOrCreate();
    }
}
