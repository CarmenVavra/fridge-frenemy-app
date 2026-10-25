<?php

namespace App\Models;

use App\Enums\FridgeItemStatus;
use Database\Factories\FridgeItemFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single product stored in a household's fridge.
 *
 * @property string $id
 * @property string $household_id
 * @property string|null $barcode
 * @property string $name
 * @property string|null $category
 * @property int $quantity
 * @property string $unit
 * @property Carbon $expires_at
 * @property FridgeItemStatus $status
 * @property int $annoyance_level
 * @property-read int $days_until_expiration
 * @property-read int $urgency_stage
 */
class FridgeItem extends Model
{
    /** @use HasFactory<FridgeItemFactory> */
    use HasFactory, HasUuids;

    /** Items with this many days left or fewer count as "critical". */
    public const CRITICAL_DAYS = 3;

    /** Items with this many days left or fewer trigger the daily butler alert. */
    public const ALERT_DAYS = 2;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'barcode',
        'name',
        'category',
        'quantity',
        'unit',
        'expires_at',
        'status',
        'annoyance_level',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'quantity' => 1,
        'unit' => 'Stück',
        'status' => 'fresh',
        'annoyance_level' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (FridgeItem $item): void {
            $item->syncStatus();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
            'status' => FridgeItemStatus::class,
            'quantity' => 'integer',
            'annoyance_level' => 'integer',
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
     * Items that are still in the fridge (neither eaten nor thrown away).
     *
     * @param  Builder<FridgeItem>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', FridgeItemStatus::active());
    }

    /**
     * Days left until expiry; negative when already expired.
     *
     * @return Attribute<int, never>
     */
    protected function daysUntilExpiration(): Attribute
    {
        return Attribute::get(
            fn (): int => (int) Carbon::today()->diffInDays($this->expires_at->copy()->startOfDay(), false)
        );
    }

    /**
     * Danger scale 1–5 (see stageForDays()).
     *
     * @return Attribute<int, never>
     */
    protected function urgencyStage(): Attribute
    {
        return Attribute::get(fn (): int => self::stageForDays($this->days_until_expiration));
    }

    /**
     * Danger scale: >7 days = 1, 4–7 = 2, 2–3 = 3, 0–1 = 4, expired = 5.
     */
    public static function stageForDays(int $days): int
    {
        return match (true) {
            $days < 0 => 5,
            $days <= 1 => 4,
            $days <= 3 => 3,
            $days <= 7 => 2,
            default => 1,
        };
    }

    /**
     * Derive fresh/warning/expired from the expiry date unless the item is resolved.
     */
    public function syncStatus(): void
    {
        if ($this->status->isResolved()) {
            return;
        }

        $days = $this->days_until_expiration;

        $this->status = match (true) {
            $days < 0 => FridgeItemStatus::Expired,
            $days <= self::CRITICAL_DAYS => FridgeItemStatus::Warning,
            default => FridgeItemStatus::Fresh,
        };
    }
}
