<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $capacity
 * @property int $remaining
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 *
 * @property Collection<Hold> $holds
 */
class Slot extends Model
{
    protected $fillable = [
        'capacity',
        'remaining',
    ];

    public function holds(): HasMany
    {
        return $this->hasMany(Hold::class);
    }
}
