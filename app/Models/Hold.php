<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $slot_id
 * @property string $status
 * @property string $idempotency_key
 * @property ?Carbon $expires_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 *
 * @property Slot $slot
 */
class Hold extends Model
{
    protected $fillable = [
        'slot_id',
        'status',
        'idempotency_key',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }
}
