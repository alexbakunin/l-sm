<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Jobs\DreamkasAdd;
use App\Jobs\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Class Dreamkas
 *
 * @package App\Http\Controllers\Crm
 */
class Dreamkas extends Controller
{

    /**
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \JsonException
     */
    public function add(Request $request): \Illuminate\Http\JsonResponse
    {
        $log = Log::stack(['dreamkas']);

        $log->debug("add: Получены данные: " . json_encode($request->all(), JSON_THROW_ON_ERROR));

        DreamkasAdd::dispatch($request->all())->onQueue(Queue::LOW);

        return response()->json(['status' => true]);
    }
}
