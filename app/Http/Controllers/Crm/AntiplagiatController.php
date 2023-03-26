<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Antiplagiat\CheckFileRequest;
use App\Jobs\Antiplagiat\UploadFileJob;
use App\Jobs\Queue;

class AntiplagiatController extends Controller
{
    public function send(CheckFileRequest $request)
    {
        UploadFileJob::dispatch($request->validated())->onQueue(Queue::ANTIPLAGIAT);
        return response()->json(['status' => true]);
    }
}
