<?php

namespace Tests\Unit;

use App\Models\Slot;
use App\Services\SlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_confirms_do_not_oversell(): void
    {
        $slot = Slot::query()->create([
            'capacity' => 1,
            'remaining' => 1,
        ]);

        $service = app(SlotService::class);

        $hold1 = $service->createHold($slot->id, fake()->uuid());
        $hold2 = $service->createHold($slot->id, fake()->uuid());

        $service->confirmHold($hold1->id);

        try {
            $service->confirmHold($hold2->id);
        } catch (Exception $e) {
        }

        $slot->refresh();

        $this->assertEquals(0, $slot->remaining);
    }
}