<?php

namespace App\Console\Commands\Author24;

use App\Jobs\Author24\SendOrderToCrmJob;
use App\Models\Author24\Account;
use App\Models\Author24\Order;
use App\Services\Author24\Author24Service;
use App\Services\Author24\NotificationService;
use App\Services\CrmApi\CrmApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckNotificationsCommand extends Command
{
    protected $signature = 'author24:check-notifications';

    protected $description = 'Проверяем уведомления по всем заказам аккаунта';


    public function handle(CrmApiService $crmApiService)
    {
        $accounts = Account::where(['active' => 1])->get();
        $this->info('Start notification parsing');
        $regex_pattern = "/<a href=\"(.*)\">(.*)<\/a>/";
        foreach ($accounts as $account) {
            $service = new Author24Service($account);
            $orderNotifications = collect($service->getNotifications())->map(function ($item) use ($regex_pattern) {
                preg_match_all($regex_pattern, str_replace(["\r", "\n"], '', $item['message']), $link);
                $link = explode('/', $link[1][0]);
                $item['orderId'] = last($link);
                $item['message'] = trim(preg_replace('/<a\b[^>]*>(.*?)<\/a>/i', '', $item['message']));
                return $item;
            })->groupBy('orderId');
            $markAsReadIds = [];
            foreach ($orderNotifications as $key => $list) {
                $order = Order::with(['ourOrder'])->where([
                    'order_id'   => $key,
                    'account_id' => $account->id,
                    'source'     => 1
                ])->first();


                if (!$order) {
                    $this->warn('no order');
                    continue;
                }
                $sortedList = collect($list)->sortByDesc('id');
                if ($order->last_notification_id === $sortedList->first()['id']) {
                    $this->warn(sprintf("skip order [%s, %s]. no new notifications", $order->order_id, $order->our_order_id));
                    continue;
                }
                foreach ($sortedList as $item) {
                    $externalOrderUpdate['last_notification_id'] = $item['id'];
                    $order->update($externalOrderUpdate);
                    if ($order->last_notification_id == $item['id']) {
                        break;
                    }
                    if (empty($item['orderId'])) {
                        $this->warn('no order id in message');
                        continue;
                    }
                    $parsedNotify = (new NotificationService($item))->prepareNotification();
                    if (!$parsedNotify->isCorrect()) {
                        $this->warn('skip notify...');
                        continue;
                    }
                    if (!$item['isRead']) {
                        $markAsReadIds[] = $item['id'];
                    }
                    $notify = $parsedNotify->toArray();
                    if (in_array($notify['our_type'], [NotificationService::TYPE_ORDER_EDIT,
                        NotificationService::TYPE_DATE_CHANGE])) {
                        $service = new Author24Service($order->account);
                        $dialog = $service->getOrderDialog($order);
                        $updateOrder = [
                            'theme'            => $dialog['order']['title'],
                            'note'             => $dialog['order']['description'],
                            'originality'      => (isset($dialog['order']['uniqueService']['name']))
                                ? SendOrderToCrmJob::ORIGINALITY[$dialog['order']['uniqueService']['name']]
                                : 0,
                            'originality_proc' => $dialog['order']['unique'] ?? 0,
                            'pages_count'      => max($dialog['order']['pagesFrom'], $dialog['order']['pagesTo'])
                        ];
                        $updateOrder['deadline'] = Carbon::parse((int)$dialog['order']['deadline'])
                            ->diffInDays(now());
                        $order->ourOrder->update($updateOrder);
                        $changes = $order->ourOrder->getChanges();
                        if (!empty($changes)) {
                            $crmApiService->updateOrderHistory($notify['order_id'], $changes);
                        }

                    }

                    if ($notify['orders_log_event']) {
                        $crmApiService->sendOrdersLog([
                            'order'   => $order->our_order_id,
                            'event'   => $notify['orders_log_event'],
                            'comment' => $notify['message'],
                            'user_id' => $order->account->user_id
                        ]);
                    }

                }
                $this->newLine(3);


            }
        }

    }
}
