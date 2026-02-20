<?php

use App\Models\Hold;
use App\Models\Slot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HoldTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Создание холда
     */
    public function test_it_creates_hold_successfully(): void
    {
        $slot = Slot::query()->create([
            'capacity' => 5,
            'remaining' => 5,
        ]);

        $response = $this->postJson(
            "/api/slots/{$slot->id}/hold",
            headers: ['Idempotency-Key' => fake()->uuid()],
        );

        $response->assertCreated();

        $this->assertDatabaseCount('holds', 1);
    }

    /**
     * 409 при отсутствии мест
     */
    public function test_id_return_409_when_no_remaining_capacity(): void
    {
        $slot = Slot::query()->create([
            'capacity' => 1,
            'remaining' => 0,
        ]);

        $response = $this->postJson(
            "/api/slots/{$slot->id}/hold",
            headers: ['Idempotency-Key' => fake()->uuid()],
        );

        $response->assertStatus(409);
    }

    /**
     * confirm уменьшает remaining
     */
    public function test_id_confirms_hold_and_decreases_remaining(): void
    {
        $slot = Slot::query()->create([
            'capacity' => 5,
            'remaining' => 5,
        ]);

        $hold = Hold::query()->create([
            'slot_id' => $slot->id,
            'status' => 'held',
            'idempotency_key' => fake()->uuid(),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->postJson("/api/holds/{$hold->id}/confirm")->assertOk();

        $slot->refresh();

        $this->assertEquals(4, $slot->remaining);
    }

    /**
     * delete увеличивает remaining
     */
    public function test_it_restores_remaining_when_cancelling_confirmed_hold(): void
    {
        $slot = Slot::query()->create([
            'capacity' => 5,
            'remaining' => 4,
        ]);

        $hold = Hold::query()->create([
            'slot_id' => $slot->id,
            'status' => 'confirmed',
            'idempotency_key' => fake()->uuid(),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->deleteJson("/api/holds/{$hold->id}")->assertNoContent();

        $slot->refresh();

        $this->assertEquals(5, $slot->remaining);
    }
}