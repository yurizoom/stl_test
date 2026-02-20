<?php

use App\Models\Slot;
use App\Services\SlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlotServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * идемпотентность hold
     */
    public function test_create_hold_is_idempotent(): void
    {
        $slot = Slot::query()->create([
            'capacity' => 5,
            'remaining' => 5,
        ]);

        $service = app(SlotService::class);

        $key = fake()->uuid();

        $hold1 = $service->createHold($slot->id, $key);
        $hold2 = $service->createHold($slot->id, $key);

        $this->assertEquals($hold1->id, $hold2->id);
    }

    /**
     * confirm уменьшает remaining
     */
    public function test_confirm_hold_decreases_remaining(): void
    {
        $slot = Slot::query()->create([
            'capacity' => 5,
            'remaining' => 5,
        ]);

        $service = app(SlotService::class);

        $hold = $service->createHold($slot->id, fake()->uuid());

        $service->confirmHold($hold->id);

        $slot->refresh();

        $this->assertEquals(4, $slot->remaining);
    }

    /**
     * идемпотентность confirm
     */
    public function test_confirm_is_idempotent(): void
    {
        $slot = Slot::query()->create([
            'capacity' => 5,
            'remaining' => 5,
        ]);

        $service = app(SlotService::class);

        $hold = $service->createHold($slot->id, fake()->uuid());

        $service->confirmHold($hold->id);
        $service->confirmHold($hold->id);

        $slot->refresh();

        $this->assertEquals(4, $slot->remaining);
    }

    /**
     * Подтверждение отменённого холда
     */
    public function test_cannot_confirm_cancelled_hold(): void
    {
        $slot = Slot::query()->create([
            'capacity' => 5,
            'remaining' => 4,
        ]);

        $service = app(SlotService::class);

        $hold = $service->createHold($slot->id, fake()->uuid());

        $service->cancelHold($hold->id);

        $this->expectException(ConflictHttpException::class);

        $service->confirmHold($hold->id);
    }
}