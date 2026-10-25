<?php

namespace App\Models;

use App\Enums\ButlerTone;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A generated butler message (daily alert or manual test alert).
 *
 * @property string $id
 * @property string $household_id
 * @property string|null $fridge_item_id
 * @property ButlerTone $tone
 * @property string $message
 * @property string $source
 * @property Carbon|null $dismissed_at
 * @property Carbon $created_at
 */
class ButlerAlert extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fridge_item_id',
        'tone',
        'message',
        'source',
        'dismissed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tone' => ButlerTone::class,
            'dismissed_at' => 'datetime',
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
     * @return BelongsTo<FridgeItem, $this>
     */
    public function fridgeItem(): BelongsTo
    {
        return $this->belongsTo(FridgeItem::class);
    }
}
