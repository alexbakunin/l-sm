<?php

namespace App\Jobs;

use Artisan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DreamkasAdd implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $data;

    /**
     * Create a new job instance.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \JsonException
     */
    public function handle()
    {
        $log = Log::stack(['dreamkas']);

        $log->debug("add: Обработка записи в очереди: " . json_encode($this->data, JSON_THROW_ON_ERROR));

        try {
            $sum = (float)($this->data['sum'] ?? 0);
            $userId = (int)($this->data['userId'] ?? 0);
            $contractId = (int)($this->data['contractId'] ?? 0);
            $isRefund = (int)($this->data['isRefund'] ?? 0);

            Artisan::call(
                'dreamkas:add',
                [
                    'sum'        => $sum,
                    'userId'     => $userId,
                    'contractId' => $contractId,
                    'isRefund'   => $isRefund,
                ]
            );
        } catch (\Throwable $e) {
            $log->error("add: Ошибка: " . $e->getMessage() . " | " . json_encode($this->data, JSON_THROW_ON_ERROR));
            $this->fail();
        }
    }
}
