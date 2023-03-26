<?php

namespace App\Jobs\Author24;

use App\Models\Author24\Order;
use App\Services\CrmApi\CrmApiService;
use App\Services\CrmApi\Requests\CrmApiRequest;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SendOrderToCrmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public                $tries = 5;
    private Order         $order;
    private CrmApiService $crmApiService;
    public const ORIGINALITY = [
        'Etxt'        => 2,
        'Антиплагиат' => 3
    ];

    public function __construct(Order $order)
    {
        $order->load('account');
        $this->order = $order;
        $this->crmApiService = new CrmApiService(new CrmApiRequest());
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->order->id))->releaseAfter(60)];
    }
    public function handle()
    {

        try {
            $data = $this->order->data->toArray();
            $response = $this->crmApiService->createOrder([
                'type_of_work'     => $this->order->type_of_work,
                'course'           => $this->order->course,
                'user_id'          => $this->order->account->user_id,
                'deadline'         => ($data['deadline']) ? Carbon::parse((int) $data['deadline'])->diffInDays(now()) : 0,
                'office_id'        => 2,
                'author24_id'      => $this->order->order_id,
                'is_external'      => 1,
                'safe_mode'        => 1,
                'set_estimation'   => 1,
                'theme'            => $data['theme'],
                'originality'      => (isset($data['originality']))
                    ? self::ORIGINALITY[$data['originality']]
                    : 0,
                'originality_proc' => $data['originality_proc'] ?? 0,
                'note'             => $data['comment'] ?? '',
                'price'            => $this->order->price,
                'pages_count'      => $data['pages']
            ]);

            if (!empty($response['error'])) {
                $this->fail(new \Exception($response['error']));
                \Log::error('Order creation error', ['msg' => $response]);

            }
            $this->order->update(['our_order_id' => $response['data']['order_id']]);
        } catch (\Throwable $e) {
            \Log::error('Order creation error', ['msg' => $e->getMessage(), 'code' => $e->getCode()]);
            $this->fail();
        }
        return Command::SUCCESS;
    }

    public function backoff()
    {
        return [1, 5, 10, 30, 50];
    }
}
