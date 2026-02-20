<?php

use App\Models\Hold;
use App\Models\Slot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест на cache invalidation
     *
     * availability кэшируется
     * после confirm кеш очищается
     */
    public function test_it_returns_slot_availability(): void
    {
        Slot::query()->create([
            'capacity' => 10,
            'remaining' => 6,
        ]);

        $response = $this->getJson('api/slots/availability');

        $response->assertOk()
            ->assertJsonFragment([
                'capacity' => 10,
                'remaining' => 6,
            ]);
    }

    public function test_cache_is_invalidated_after_confirm(): void
    {
        Cache::flush();

        $slot = Slot::query()->create([
            'capacity' => 5,
            'remaining' => 5,
        ]);

        $this->getJson('/api/slots/availability')->assertOk();

        $this->assertTrue(Cache::has('slot_availability'));

        $hold = Hold::query()->create([
            'slot_id' => $slot->id,
            'status' => 'held',
            'idempotency_key' => fake()->uuid(),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->postJson("/api/holds/{$hold->id}/confirm")->assertOk();

        $this->assertFalse(Cache::has('slot_availability'));
    }
}