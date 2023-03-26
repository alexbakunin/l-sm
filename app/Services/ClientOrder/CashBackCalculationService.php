<?php

namespace App\Services\ClientOrder;

use App\Models\CrmUser;
use App\Models\Order;
use App\Services\ClientOrder\Repositories\ClientOrderRepository;

class CashBackCalculationService
{
    protected ClientOrderRepository $clientOrderRepository;
    private int $cashBackGradationDate;

    const DEFAULT_CASH_BACK_GRADATION_DATE = '01.09.2022 00:00:00';

    const CASH_BACK_LINE_FILL = [
        '0' => 5,
        '3' => 5,
        '5' => 33,
        '10' => 66,
        '15' => 100,
    ];

    const CASH_BACK_DESCRIPTION_TEMPLATE = 'При оплате первого заказа Вы получаете кешбэк 3% на следующие заказы. Кешбэк является накопительным и учитывается при каждом заказе:<br />- при накоплении суммы #5percent# рублей размер кешбэка 5%;<br />- при накоплении суммы #10percent# рублей размер кешбэка становится 10%;<br />- при накоплении суммы #15percent# рублей размер кешбэка будет составлять 15%.<br />';

    public function __construct(ClientOrderRepository $clientOrderRepository)
    {
        $this->clientOrderRepository = $clientOrderRepository;
        $cashBackGradationDate = $this->clientOrderRepository->getCrmSettings('cash_back_gradation_date')?->value ?: self::DEFAULT_CASH_BACK_GRADATION_DATE;
        $this->cashBackGradationDate = strtotime($cashBackGradationDate);
    }

    /**
     * Проверяем % кешбека пользователя и обновляем его в базе
     * @param int $userId
     * @param int $orderId
     * @param bool $set
     * @return array
     */
    public function checkUserCashBackPercent(int $userId, int $orderId = 0, bool $set = false): array
    {
        $total = $this->clientOrderRepository->getUserTotalPaysAndReturns($userId);
        $percent = 3;
        $list = $this->clientOrderRepository->getBuilder()
            ->table('directory_cum_discount')
            ->where(['active' => 1])
            ->orderBy('sum')
            ->get()
            ->toArray();
        $zeroItem = (object)["id" => 0, "sum" => 0, "sale" => 3, "active" => 1];
        array_unshift($list, $zeroItem);
        $user = CrmUser::find($userId);
        if ($user->create <= $this->cashBackGradationDate) {
            if ($user->sale > 3) {
                $list = $this->getGradation($user->sale);
            }
        }
        if ($list) {
            $flPercents = $this->getCBPercent($list,$total);
            $percent = (!$flPercents['last']) ? $flPercents['first']->sale : $flPercents['last']->sale;

            if (!$set && $orderId == 0) {
                $user->sale = $percent;
                $user->save();

                return ['status' => true, 'cashBackPercent' => $percent, 'userSource' => $user->source];
            }
        }
        if ($set && $orderId != 0) {
            $cashBack = $this->clientOrderRepository->getBuilder()
                ->table('orders_cash_back')
                ->where(['order_id' => $orderId])
                ->first();
            $order = Order::find($orderId);
            $order->cash_back_percent = $percent;
            $order->cash_back_id = $cashBack ? $cashBack->id : 0;
            $order->save();

            return ['status' => true, 'cashBackPercent' => $percent, 'userSource' => $user->source];
        }

        return ['status' => false, 'userSource' => $user->source];
    }

    private function getCBPercent(array $list, int $total): array
    {
        $res['first'] = last(array_filter($list, function ($item) use ($total) {
            return $item->sum >= $total;
        }));
        $res['last'] = last(array_filter($list, function ($item) use ($total) {
            return $item->sum <= $total;
        }));

        return $res;
    }

    public function getSaleScale(int $ordersTotal, int $userId)
    {
        $user = CrmUser::find($userId);
        $list = $this->clientOrderRepository->getBuilder()
            ->table('directory_cum_discount')
            ->where(['active' => 1])
            ->orderBy('sum')
            ->get()
            ->toArray();
        if ($user->create <= $this->cashBackGradationDate) {
            if ($user->cash_back_gradation_hold > 3) {
                $list = $this->getGradation($user->cash_back_gradation_hold);
                unset($list[0]);
            }
        }
        $max = $user->create <= $this->cashBackGradationDate && $user->sale == 15 ? 20000 : 30000;
        $cashBackDescription = self::CASH_BACK_DESCRIPTION_TEMPLATE;
        foreach ($list as $item) {
            $cashBackDescription = str_replace('#' . $item->sale . 'percent#', number_format($item->sum, 0, '.', ' '), $cashBackDescription);
        }

        return [
            'discount'     => $list,
            'orders_total' => $ordersTotal,
            'max'          => $max,
            'proc'         => self::CASH_BACK_LINE_FILL[$user->sale],
            'userSale'     => $user->sale,
            'cashBackDescription' => $cashBackDescription,
        ];
    }

    private function getGradation(int $index)
    {
        $cashBackGradation = [
            '5' => [
                (object)["id" => 0, "sum" => 0, "sale" => 3, "active" => 1],
                (object)["id" => 1, "sum" => 7000, "sale" => 5, "active" => 1],
                (object)["id" => 2, "sum" => 20000, "sale" => 10, "active" => 1],
                (object)["id" => 3, "sum" => 30000, "sale" => 15, "active" => 1],
            ],
            '10' => [
                (object)["id" => 0, "sum" => 0, "sale" => 3, "active" => 1],
                (object)["id" => 1, "sum" => 7000, "sale" => 5, "active" => 1],
                (object)["id" => 2, "sum" => 14000, "sale" => 10, "active" => 1],
                (object)["id" => 3, "sum" => 30000, "sale" => 15, "active" => 1],
            ],
            '15' => [
                (object)["id" => 0, "sum" => 0, "sale" => 3, "active" => 1],
                (object)["id" => 1, "sum" => 7000, "sale" => 5, "active" => 1],
                (object)["id" => 2, "sum" => 14000, "sale" => 10, "active" => 1],
                (object)["id" => 3, "sum" => 20000, "sale" => 15, "active" => 1],
            ]
        ];

        return $cashBackGradation[$index];
    }
}
