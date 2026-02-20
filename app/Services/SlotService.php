<?php

namespace App\Services;

use App\Models\Hold;
use App\Models\Slot;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SlotService {
    private const CACHE_KEY = 'slot_availability';
    private const CACHE_TTL = 10;

    /**
     * Получение доступных слотов
     *
     * @return array
     */
    public function getAvailability(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if ($cached !== null) {
            return $cached;
        }

        try {
            return Cache::lock('slots_availability_lock', 5)
                ->block(5, function () {
                    $data = $this->calculateAvailability();
                    Cache::put(self::CACHE_KEY, $data, self::CACHE_TTL);
                    return $data;
                });
        } catch (LockTimeoutException) {
            return Cache::get(self::CACHE_KEY)
                ?? $this->calculateAvailability();
        }
    }

    /**
     * Получение доступных слотов из базы
     *
     * @return array
     */
    public function calculateAvailability(): array
    {
        return Slot::query()
            ->select('id as slot_id', 'capacity', 'remaining')
            ->get()
            ->toArray();
    }

    /**
     * Инвалидация кэша доступных слотов
     *
     * @return void
     */
    public function invalidateAvailabilityCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Создание холда
     *
     * @param  int  $slotId
     * @param  string  $idempotencyKey
     * @return Hold
     */
    public function createHold(int $slotId, string $idempotencyKey): Hold
    {
        $existing = $this->getHoldByIdempotencyKey($slotId, $idempotencyKey);

        if ($existing) {
            return $existing;
        }

        $slot = Slot::query()->findOrFail($slotId);

        if ($slot->remaining <= 0) {
            throw new ConflictHttpException('No remaining capacity.');
        }

        try {
            return Hold::query()->create([
                'slot_id' => $slotId,
                'status' => 'held',
                'idempotency_key' => $idempotencyKey,
                'expires_at' => now()->addMinutes(5),
            ]);
        } catch (QueryException) {
            return $this->getHoldByIdempotencyKey($slotId, $idempotencyKey);
        }
    }

    /**
     * Получение холда из базы по ключу идемпотентности
     *
     * @param  int  $slotId
     * @param  string  $idempotencyKey
     * @return Hold|null
     */
    public function getHoldByIdempotencyKey(int $slotId, string $idempotencyKey): ?Hold
    {
        return Hold::query()
            ->where('slot_id', $slotId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    /**
     * Подтверждение холда
     *
     * @param  int  $holdId
     * @return Hold
     */
    public function confirmHold(int $holdId): Hold
    {
        return DB::transaction(function () use ($holdId) {
            $hold = Hold::query()->lockForUpdate()->findOrFail($holdId);

            if ($hold->status === 'confirmed') {
                return $hold;
            }

            if ($hold->status === 'cancelled') {
                throw new ConflictHttpException('Hold already cancelled.');
            }

            $slot = Slot::query()->lockForUpdate()->findOrFail($hold->slot_id);

            if ($slot->remaining <= 0) {
                throw new ConflictHttpException('No remaining capacity.');
            }

            $slot->decrement('remaining');

            $hold->update([
                'status' => 'confirmed',
            ]);

            $this->invalidateAvailabilityCache();

            return $hold;
        });
    }

    /**
     * Отмена холда
     *
     * @param  int  $holdId
     * @return void
     */
    public function cancelHold(int $holdId): void
    {
        DB::transaction(function () use ($holdId) {
            $hold = Hold::query()->lockForUpdate()->findOrFail($holdId);

            if ($hold->status === 'cancelled') {
                return;
            }

            if ($hold->status === 'confirmed') {
                $slot = Slot::query()->lockForUpdate()->findOrFail($hold->slot_id);
                $slot->increment('remaining');
                $this->invalidateAvailabilityCache();
            }

            $hold->update([
                'status' => 'cancelled',
            ]);
        });
    }
}