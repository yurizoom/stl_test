<?php

namespace App\Http\Controllers;

use App\Services\SlotService;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly SlotService $slotService
    ) {}

    /**
     * Получение доступных слотов
     *
     * @return JsonResponse
     *
     * @example
     * [
     * { "slot_id": 1, "capacity": 10, "remaining": 6 },
     * { "slot_id": 2, "capacity": 5, "remaining": 0 }
     * ]
     */
    public function index(): JsonResponse
    {
        return response()->json($this->slotService->getAvailability());
    }
}
