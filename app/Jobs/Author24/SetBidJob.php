<?php

namespace App\Jobs\Author24;

use App\Jobs\Queue;
use App\Models\Author24\Account;
use App\Models\Author24\Order;
use App\Services\Author24\Author24Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SetBidJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Order $order;
    public $tries = 5;
    public function __construct(Order $order)
    {
        $this->order = $order;
    }
    public function middleware()
    {
        return [(new WithoutOverlapping($this->order->account_id))->releaseAfter(60)];
    }
    public function handle()
    {
        $account = Account::findOrFail($this->order->account_id);
        $service = new Author24Service($account);
        $bid = $service->setOrderBid($this->order, true);
        if (!empty($bid)) {
            SendOrderToCrmJob::dispatch($this->order)->onQueue(Queue::HIGH);
            $service->updateOrderWithBid($this->order, ['bid_id' => $bid['id']]);
        }
    }
    public function backoff()
    {
        return [1, 5, 10, 30, 50];
    }
}
