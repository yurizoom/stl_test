<?php

use App\Models\Slot;
use App\Services\SlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheTest extends TestCase
{
    use RefreshDatabase;

    /**
     * на доступность из кэша
     */
    public function test_availability_is_cached(): void
    {
        Slot::query()->create([
           'capacity' => 10,
           'remaining' => 8,
        ]);

        $service = app(SlotService::class);

        $result1= $service->getAvailability();

        $this->assertTrue(Cache::has('slot_availability'));

        Slot::query()->update(['remaining' => 0]);

        $result2 = $service->getAvailability();

        $this->assertEquals($result1, $result2);
    }
}