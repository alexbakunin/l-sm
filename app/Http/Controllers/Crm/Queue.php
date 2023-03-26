<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\Queue\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class Queue extends Controller
{
    /**
     * @var \App\Services\Queue\QueueService
     */
    private QueueService $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function size(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data'   => $this->queueService->getSize(),
        ]);
    }

    /**
     * @param $id
     * @return bool
     */
    public function retry($id): bool
    {
        Artisan::call('queue:retry ' . $id);
        return true;
    }
}
