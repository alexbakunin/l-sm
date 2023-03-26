<?php

namespace App\Services\ClientOrder;


use App\Models\CrmUser;
use App\Models\CrmUserBonus;
use App\Models\Order;
use App\Models\OrderCashBack;
use App\Models\OrderFinances;
use App\Services\ClientOrder\Repositories\ClientOrderRepository;
use App\Services\CrmApi\CrmApiService;
use App\Services\CrmApi\Requests\CrmApiRequest;
use Exception;
use Illuminate\Http\Request;

class OrderFinanceService
{
    protected ClientOrderService         $clientOrderService;
    protected ClientOrderRepository      $repository;
    protected CashBackCalculationService $cashBackCalculationService;

    public function __construct(ClientOrderService $clientOrderService, ClientOrderRepository $clientOrderRepository, CashBackCalculationService $cashBackCalculationService)
    {
        $this->clientOrderService = $clientOrderService;
        $this->repository = $clientOrderRepository;
        $this->cashBackCalculationService = $cashBackCalculationService;
    }

    /**
     * Применение оплаты заказа бонусами
     * @param Request $request
     * @return array|void
     * @throws Exception
     */
    public function appendOrderBonusesPayment(Request $request)
    {
        $crmApiService = new CrmApiService(new CrmApiRequest());
        $orderFinancesInsertDataArray = [];
        $defaultDataArray = [
            'key'             => '',
            'payHash'         => '',
            'status'          => '',
            'rsAvailable'     => 0,
            'rsSum'           => 0,
            'yandex_kassa_id' => 0,
            'sale'            => 0,
            'promo'           => 0,
            'commission'      => 0,
            'return_date'     => 0,
            'return_sum'      => 0,
        ];
        $order = Order::find($request->orderId);
        $orderSalePrices = $this->clientOrderService->getOrderPricesForSale($order);
        $priceForSale = $orderSalePrices['priceForSale'];
        $user = CrmUser::find($request->userId);
        $cashBackPayment = $order->cash_back_payment;
        if ($order->cash_back_payment) {
            $tLeft = $order->cash_back_payment;
            $orderFinancesInsertDataArray[] = array_merge($defaultDataArray, [
                'type'       => 2,
                'order_id'   => $order->id,
                'date'       => time(),
                'pay_source' => 'cash-back',
                'promo'      => 1,
                'sum'        => $order->cash_back_payment,
                'comment'    => 'Оплата баллами кешбэка',
                'active'     => 1,
                'user'       => 'users.' . $request->userId
            ]);

            $order->cash_back_payment = 0;
            $order->save();

            $cashBackList = $this->repository->getOrderCashBackDiscount($request->userId, false);
            if (!$cashBackList) {
                return ['status' => false, 'error' => "Ошибка получения списка бонусов"];
            }
            foreach ($cashBackList as $item) {
                $insert = [
                    'ubonus_id' => $item->id,
                    'sum'       => $item->left < $tLeft ? $item->left : $tLeft,
                    'order_id'  => $order->id,
                    'date'      => time(),
                    'comment'   => 'Оплата баллами кешбэка',
                    'active'    => 1,
                ];
                $this->repository->getBuilder()
                    ->table('users_bonuses_log')
                    ->insert($insert);

                $userBonuses = CrmUserBonus::find($item->id);
                $userBonuses->left = $item->left < $tLeft ? 0 : $item->left - $tLeft;
                $userBonuses->save();
                $tLeft -= $item->left < $tLeft ? $item->left : $tLeft;
                if (!$tLeft) {
                    break;
                }
            }
            $source = 'Оплата кешбэком' . " - " . $cashBackPayment . "р.";
            $crmApiService->sendOrdersLog(['order' => $order->id, 'user_id' => $order->user_id, 'comment' => $source, 'event' => 'pay.cashBack']);
        }
        if ($order->paybonuses) {
            $tLeft = $order->paybonuses;
            $bonusList = $this->repository->getUserBonuses($request->orderId, $request->userId);
            if (!$bonusList) {
                return ['status' => false, 'error' => "Ошибка получения списка бонусов"];
            }
            $cashBackBonuses = 0;
            foreach ($bonusList as $item) {
                $insert = [
                    'ubonus_id' => $item->id,
                    'sum'       => $item->left < $tLeft ? $item->left : $tLeft,
                    'order_id'  => $order->id,
                    'date'      => time(),
                    'comment'   => 'Оплата бонусами из личного кабинета',
                    'active'    => 1,
                ];
                $this->repository->getBuilder()
                    ->table('users_bonuses_log')
                    ->insert($insert);

                $userBonuses = CrmUserBonus::find($item->id);
                $userBonuses->left = $item->left < $tLeft ? 0 : $item->left - $tLeft;
                $userBonuses->save();
                $tLeft -= $item->left < $tLeft ? $item->left : $tLeft;
                if (!$tLeft) {
                    break;
                }
            }
            if ($cashBackBonuses) {
                $orderFinancesInsertDataArray[] = array_merge($defaultDataArray, [
                    'type'       => 2,
                    'order_id'   => $order->id,
                    'date'       => time(),
                    'pay_source' => 'cash-back',
                    'sum'        => $cashBackBonuses,
                    'comment'    => 'Оплата баллами кешбэка',
                    'active'     => 1,
                    'user'       => 'users.' . $request->userId
                ]);
            }
            if ($order->paybonuses - $cashBackBonuses > 0) {
                $orderFinancesInsertDataArray[] = array_merge($defaultDataArray, [
                    'type'       => 2,
                    'order_id'   => $order->id,
                    'date'       => time(),
                    'pay_source' => 'bonuses',
                    'sum'        => $order->paybonuses - $cashBackBonuses,
                    'comment'    => 'Оплата бонусами из личного кабинета',
                    'active'     => 1,
                    'user'       => 'users.' . $request->userId
                ]);
            }

            $order->paybonuses = 0;
            $order->save();
        }
        if ($order->paybonuses_promo) {
            $tLeft = $order->paybonuses_promo;
            $orderFinancesInsertDataArray[] = array_merge($defaultDataArray, [
                'type'       => 2,
                'order_id'   => $order->id,
                'date'       => time(),
                'pay_source' => 'bonuses',
                'promo'      => 1,
                'sum'        => $order->paybonuses_promo,
                'comment'    => 'Оплата бонусами из личного кабинета',
                'active'     => 1,
                'user'       => 'users.' . $request->userId
            ]);

            $order->paybonuses_promo = 0;
            $order->save();

            $bonusList = $this->repository->getUserBonuses($request->orderId, $request->userId, 3);
            if (!$bonusList) {
                return ['status' => false, 'error' => "Ошибка получения списка бонусов"];
            }
            foreach ($bonusList as $item) {
                $insert = [
                    'ubonus_id' => $item->id,
                    'sum'       => $item->left < $tLeft ? $item->left : $tLeft,
                    'order_id'  => $order->id,
                    'date'      => time(),
                    'comment'   => 'Оплата промо-бонусами из личного кабинета',
                    'active'    => 1,
                ];
                $this->repository->getBuilder()
                    ->table('users_bonuses_log')
                    ->insert($insert);

                $userBonuses = CrmUserBonus::find($item->id);
                $userBonuses->left = $item->left < $tLeft ? 0 : $item->left - $tLeft;
                $userBonuses->save();
                $tLeft -= $item->left < $tLeft ? $item->left : $tLeft;
                if (!$tLeft) {
                    break;
                }
            }
        }
        if ($order->promocode && $order->promocode->settings->discount_check && $order->promocode->settings->discount_type) {
            $orderFinancesInsertDataArray[] = array_merge($defaultDataArray, [
                'type'       => 2,
                'order_id'   => $order->id,
                'date'       => time(),
                'pay_source' => 'bonuses',
                'sum'        => $order->promocode->settings->discount_rur,
                'comment'    => 'Оплата промокодом из личного кабинета',
                'active'     => 1,
                'user'       => 'users.' . $request->userId
            ]);
        }
        if ($user->balance && $order->paybalance) {
            $paySum = min((int)$order->paybalance, (int)$user->balance);
            $payedSum = min((int)$priceForSale, (int)$paySum);
            $res = $this->userPayBalanceChange($payedSum, $order, $user);
            if (empty($res['status'])) {
                redirect('referer');
            }

            $orderFinancesInsertDataArray[] = array_merge($defaultDataArray, [
                'type'       => 2,
                'status'     => 'ok',
                'order_id'   => $order->id,
                'date'       => time(),
                'pay_source' => 'balance',
                'sum'        => $payedSum,
                'comment'    => 'Оплата балансом из личного кабинета',
                'active'     => 1,
                'user'       => 'users.' . $request->userId
            ]);
        }
        OrderFinances::insert($orderFinancesInsertDataArray);
        $result = $this->cashBackCalculationService->checkUserCashBackPercent($order->user_id);

        return $result;
    }

    /**
     * Изменение партнерского баланса при оплате заказа
     * @param int $payedSum
     * @param Order $order
     * @param CrmUser $user
     * @return array|bool[]
     */
    private function userPayBalanceChange(int $payedSum, Order $order, CrmUser $user): array
    {
        if (!$payedSum) {
            return ['status' => false, 'error' => "Сумма списания должна быть больше 0"];
        }
        if (!$user->balance || $user->balance < $payedSum) {
            return ['status' => false, 'error' => "Недостаточно средств на счёте"];
        }
        $user->balance -= $payedSum;
        $user->save();

        $order->paybalance = 0;
        $order->save();

        $insert = [
            'user_id'      => $user->id,
            'type'         => 0,
            'ref_order_id' => 0,
            'ref_user_id'  => 0,
            'sum'          => $payedSum,
            'date'         => time(),
            'comment'      => "Оплата заказа {$order->id}",
            'active'       => 1,
            'balance'      => $user->balance,
        ];

        $this->repository->getBuilder()
            ->table('partners_finances_history')
            ->insert($insert);

        return ['status' => true];
    }

    /**
     * Возврат кешбэка при возврате средств по заказу
     * @param int $orderId
     * @param int $orderReturnSum
     * @param int $userId
     * @return void
     * @throws Exception
     */
    public function returnOrderCashBack(int $orderId, int $orderReturnSum, int $userId): void
    {
        $orderFull = $this->clientOrderService->getOrderBaseData(Order::find($orderId), $userId);
        $orderCashBackBonuses = OrderCashBack::where(['order_id' => $orderId, 'canceled' => false, 'transfered' => 1])->first();
        $percentOfOrder = ($orderReturnSum / $orderFull['finances']['priceForSale']) * 100;
        $bunusReturn = 0;
        if ($orderCashBackBonuses) {
            $bunusReturn = ceil(($orderCashBackBonuses->value / 100) * $percentOfOrder);
        }
        $list = $this->repository->getUserBonuses($orderId, $userId);
        if ($bunusReturn) {
            $tLeft = (int)$bunusReturn;
            foreach ($list as $i) {
                $itemLeft = (int)$i->left;
                $sum = min($itemLeft, $tLeft);
                $this->repository->getBuilder()
                    ->table('users_bonuses_log')
                    ->insert(
                        [
                            'ubonus_id' => $i->id,
                            'sum'       => $sum,
                            'order_id'  => $orderId,
                            'date'      => time(),
                            'comment'   => 'Списание кешбэка за возврат средств по заказу ' . $orderId,
                            'active'    => 1,
                        ]
                    );

                $bonus = CrmUserBonus::find($i->id);
                $bonus->left = $itemLeft < $tLeft ? 0 : $itemLeft - $tLeft;
                $bonus->save();
                $tLeft -= $sum;

                if (!$tLeft) {
                    break;
                }
            }
        }
    }

    /**
     * Данные для оплаты заказа
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function appendOrderPayment(Request $request): array
    {
        $order = Order::find($request->orderId);
        $orderData = $this->clientOrderService->getOrderBaseData($order, $request->userId, true);
        $bonuses = $order->paybonuses ?? false;
        $bonuses += $order->paybonuses_promo ?? false;
        if ($order->cash_back_payment) {
            $cashBackBonuses = (int)$this->repository->getOrderCashBackDiscount($request->userId);
        } else {
            $cashBackBonuses = 0;
        }
        $paybalance = $order->paybalance ?? false;
        $sum = $request->paySum;
        $cashBackBonuses = min((int)$cashBackBonuses, (int)$order->priceForSale);
        $orderPaymentData['cashBackBonuses'] = $cashBackBonuses;
        $orderPaymentData['bonuses'] = $bonuses;
        $orderPaymentData['paybalance'] = $paybalance;
        $orderPaymentData['sum'] = $sum - $cashBackBonuses - $bonuses - $paybalance;

        if ($orderData['contracts']) {
            $order->contracts = $orderData['contracts'];
        }
        return [
            'orderPayment' => $orderPaymentData,
            'entry'        => $order,
        ];
    }

}
