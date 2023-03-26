<?php

namespace App\Jobs\Author24;

use App\Services\CrmApi\CrmApiService;
use App\Services\CrmApi\Requests\CrmApiRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrdersLogMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public                $tries = 5;
    private CrmApiService $crmApiService;
    private array         $data;

    public function __construct(array $data)
    {
        $this->crmApiService = new CrmApiService(new CrmApiRequest());
        $this->data = $data;
    }

    public function handle(CrmApiService $crmApiService)
    {
        try {
            $send = $crmApiService->sendOrdersLog($this->data);
        } catch (\Throwable $e) {
            \Log::stack(['author24'])->error('Error send message to orders log', [$e->getCode(), $e->getMessage(), $this->data]);
            $this->fail();
        }
    }

    public function backoff()
    {
        return [1, 5, 10, 30, 50];
    }
}
