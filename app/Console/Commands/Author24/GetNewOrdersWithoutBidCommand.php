<?php

namespace App\Console\Commands\Author24;

use App\Jobs\Author24\SetBidJob;
use App\Jobs\Queue;
use App\Models\Author24\Account;
use App\Models\Author24\Matching;
use App\Models\Author24\Order as Author24Order;
use App\Models\Directory\Courses;
use App\Models\Price;
use App\Services\Author24\Author24Service;
use App\Services\Settings\SettingsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GetNewOrdersWithoutBidCommand extends Command
{
    protected $signature = 'author24:get-new-orders-without-bid {accountID : ID аккаунта в системе Автор24}';

    protected $description = 'Получаем список новых заказов без ставки';

    private const ENABLED_ORDERS = [];

    public function handle(SettingsService $settingsService)
    {
        $enabled = (int)$settingsService->getAuthor24Enabled()?->value
            ?: 0;
        if ($enabled == 0) {
            $this->error('Author24 disabled');
            return;
        }

        $accountId = $this->argument('accountID');
        $account = Account::findOrFail($accountId);
        $service = new Author24Service($account);
        $currentPage = 1;

        $matchingTypesOfWork = Matching::where('type', 'types')->first();

        $drop = Author24Order::where('account_id', $account->id)->where(['bid_id' => 0, 'can_be_estimated' => 0, 'hand_crafted' => 0])->delete();
        if (\Cache::has('DirectoryCourses')) {
            $courses = \Cache::get('DirectoryCourses');
        } else {
            $courses = Courses::where('active', 1)->where('category', '>', 0)->get();
            \Cache::set('DirectoryCourses', $courses, 600);
        }
        $count = 0;

        while ($currentPage > 0) {
            $ordersRequest = $service->getNewOrdersWithOutOffer($currentPage - 1);
            $this->newLine();
            $this->info('Page #' . $currentPage);
            if (is_null($ordersRequest)) {
                continue;
            }
            $bar = $this->output->createProgressBar(count($ordersRequest['orders']['orders']));
            $bar->setFormat('verbose');
            $bar->start();
            foreach ($ordersRequest['orders']['orders'] as $order) {
                if (!empty(self::ENABLED_ORDERS) && !in_array($order['id'], self::ENABLED_ORDERS)) {
                    continue;
                }
                $creation = Carbon::parse($order['creation'])->diffInDays(now());
                if ($creation > 1) {
                    continue;
                }
                $totalPages = max($order['pagesFrom'], $order['pagesTo']);

                $ourOrder = Author24Order::firstOrCreate([
                    'order_id'   => $order['id'],
                    'source'     => $service->getExternalSourceId(),
                    'account_id' => $account->id
                ],
                    [
                        'account_id'      => $account->id,
                        'order_id'        => $order['id'],
                        'source'          => $service->getExternalSourceId(),
                        'estimated'       => 0,
                        'price'           => 0,
                        'status'          => 'new',
                        'deadline_notify' => 0,
                        'data'            => [
                            'theme'            => $order['title'],
                            'comment'          => $order['description'],
                            'originality_proc' => $order['unique'],
                            'pagesFrom'        => $order['pagesFrom'],
                            'pagesTo'          => $order['pagesTo'],
                            'pages'            => $totalPages,
                            'deadline'         => $order['deadline'],
                            'type_of_work'     => $order['type']['id'],
                            'course'           => $order['category']['name'],
                            'originality'      => $order['uniqueService']['name'],
                            'font'             => $order['font'],
                            'interval'         => $order['interval'],
                            'budget'           => $order['budget'],
                        ]
                    ]);
                $isNew = $ourOrder->wasRecentlyCreated;
                $updata = ['can_be_estimated' => 0];
                $courseMatch = $courses->filter(fn($item) => $item['name'] === $order['category']['name'])->first();
//                \Log::stack(['author24'])->info('start estimate', [$courseMatch, $order['category']['name']]);
                if ($courseMatch) {
                    $ourTypeOfWork = $matchingTypesOfWork->matching->filter(fn($item) => $item['ext'] == $order['type']['id'])
                        ->first();

                    if ($ourTypeOfWork) {
                        $deadlineInDays = Carbon::parse((int)$order['deadline'])->diffInDays(now());
                        $estimatePrice = Price::where('type_of_work', $ourTypeOfWork['int'])
                            ->where('course_id', $courseMatch->id)
                            ->where('active', 1);
                        $estimatePrice = $estimatePrice->first();
//                        \Log::stack(['author24'])->info('try to get price', [$estimatePrice, $totalPages, $deadlineInDays]);
                        if ($estimatePrice) {
                            $canBeEstimated = ($estimatePrice->capacity_from <= $totalPages && $estimatePrice->capacity_to >= $totalPages && $estimatePrice->deadline_from <= $deadlineInDays);
                            if ($canBeEstimated) {
                                $price = ($estimatePrice->other_price - ($estimatePrice->other_price * 20 / 100));
                                $finalPrice = $price - ($price * 14 / 100);
                                $updata = [
                                    'price'        => ceil($finalPrice),
                                    'estimated'    => 1,
                                    'last_update'  => time(),
                                    'type_of_work' => $ourTypeOfWork['int'],
                                    'course'       => $courseMatch->id
                                ];
                            }
                        }
                    }
                }
                $ourOrder->update($updata);
                if ($ourOrder->wasRecentlyCreated && $ourOrder->price > 0) {
                    SetBidJob::dispatch($ourOrder)->onQueue(Queue::HIGH);
                }
                $bar->advance();
            }
            $currentPage++;
            $lastPage = last($ordersRequest['orders']['pages']);
            if ($currentPage > $lastPage) {
                $currentPage = 0;
            }
            $bar->finish();
            sleep(1);
//            dump($lastPage, $currentPage);
        }
        $this->newLine();
        $this->info('end account parsing. next...');
        $this->newLine(3);



    }
}
