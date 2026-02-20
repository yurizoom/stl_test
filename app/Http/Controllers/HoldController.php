<?php

namespace App\Http\Controllers;

use App\Services\SlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HoldController extends Controller
{
    public function __construct(
        private readonly SlotService $slotService
    ) {}

    /**
     * Создание холда
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $key = $request->header('Idempotency-Key');

        if (!$key) {
            return response()->json(
                ['message' => 'Idempotency-Key header required'],
                400
            );
        }

        $hold = $this->slotService->createHold($id, $key);

        return response()->json($hold, 201);
    }

    /**
     * Подтверждение холда
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function confirm(int $id): JsonResponse
    {
        $hold = $this->slotService->confirmHold($id);

        return response()->json($hold);
    }

    /**
     * Отмена холда
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $this->slotService->cancelHold($id);

        return response()->json(null, 204);
    }
}
