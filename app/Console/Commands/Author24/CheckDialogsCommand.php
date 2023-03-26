<?php

namespace App\Console\Commands\Author24;

use App\Models\Author24\Order;
use App\Services\Author24\Author24Service;
use App\Services\CrmApi\CrmApiService;
use Illuminate\Console\Command;

class CheckDialogsCommand extends Command
{
    protected $signature = 'author24:check-dialogs';

    protected $description = 'Проверка диалогов';

    public function handle(CrmApiService $crmApiService)
    {
        $orders = Order::with(['account'])->where('bid_id', '>', 0)->where('our_order_id', '>', 0)->where('active', 1)
            ->where('last_update', '<', now()
                ->subMinutes(7)->getTimestamp())->orderBy('last_update', 'asc')->limit(350)->get();
        $bar = $this->output->createProgressBar(count($orders));
        $bar->setFormat('very_verbose');
        $bar->start();
        foreach ($orders as $order) {
            $updata = [];
            $updata['last_update'] = time();
            $service = new Author24Service($order->account);
            $dialog = $service->getOrderDialog($order);
            if (empty($dialog)) {
                $updata['active'] = 0;
                $order->update($updata);
                continue;
            }
            if ($order->status !== 'new' && $order->deadline_notify == 0 && $dialog['order']['deadlineBeenPercent'] >= 50 && $dialog['order']['deadlineBeenPercent'] < 90) {
                $updata['deadline_notify'] = 50;
                $crmApiService->sendOrdersLog([
                    'order'   => $order->our_order_id,
                    'event'   => 'author24.deadline.50',
                    'comment' => '',
                    'user_id' => $order->account->user_id
                ]);
            } elseif ($order->status !== 'new' && $dialog['order']['deadlineBeenPercent'] >= 90 && $order->deadline_notify == 50) {
                $updata['deadline_notify'] = 90;
                $crmApiService->sendOrdersLog([
                    'order'   => $order->our_order_id,
                    'event'   => 'author24.deadline.90',
                    'comment' => '',
                    'user_id' => $order->account->user_id
                ]);
            }

            if ($dialog['lastCommentId'] != $order->last_message_id) {
                $updata['last_message_id'] = $dialog['lastCommentId'];

                $messages = collect($dialog['messages'])->reverse();
                foreach ($messages as $message) {
                    if ($message['id'] == $order->last_message_id) {
                        break;
                    }
                    if (!empty($message['user_id']) && $message['user_id'] == $order->account->external_id) {
                        continue;
                    }
                    $sendMessageToOrdersLog = true;
                    if ($message['__typename'] === 'system') {
                        $eventName = false;
                        if (in_array($message['type'], [0, 5])) {
                            $sendMessageToOrdersLog = false;
                        }
                        switch ($message['text']) {
                            case 'Вас выбрали автором':
                                $eventName = 'author24.checked';
                                $ourOrder = \App\Models\Order::find($order->our_order_id);
                                if ($ourOrder->status === 'auto_estimated') {
                                    $status = $crmApiService->setOrderStatus($order->our_order_id, 'auto_estimated_paid');
                                }
                                $updata['status'] = 'checked';
                                break;
                            case 'Заказ перешел в гарантию':
                                $eventName = 'author24.guarantee';
                                $updata['status'] = 'guarantee';

                                break;
                            case 'Заказ завершен':
                                $eventName = 'author24.finished';
                                $updata['active'] = 0;
                                $updata['status'] = 'finished';

                                break;
                        }
                        if ($eventName) {
                            $crmApiService->sendOrdersLog([
                                'order'   => $order->our_order_id,
                                'event'   => $eventName,
                                'comment' => '',
                                'user_id' => $order->account->user_id
                            ]);
                        }
                    }
                    if (!empty($message['files'])) {
                        $crmApiService->sendFilesToOrder($order->our_order_id, $message['files']);
                    }
                    if ($sendMessageToOrdersLog) {
                        $crmApiService->sendOrdersLog([
                            'order'   => $order->our_order_id,
                            'event'   => 'author24.chat.messages',
                            'comment' => '',
                            'user_id' => $order->account->user_id
                        ]);
                    }

                }
            }
            $order->update($updata);
            $bar->advance();
        }
        $bar->finish();
    }

}
