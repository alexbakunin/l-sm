<?php

namespace App\Services\ClientOrder\Repositories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClientOrderRepository
{
    const DB_CONNECTION = 'crm';
    const TABLE = 'orders';
    const DIRECTORY_BONUSES = 'directory_bonuses';
    const USERS_BONUSES = 'users_bonuses';

    public function getBuilder()
    {
        return DB::connection(self::DB_CONNECTION);
    }

    /**
     * @param $orderId
     * @param $userId
     * @return Collection
     */
    public function getPercentBonuses($orderId, $userId): Collection
    {
        return $this->getBuilder()
            ->table(self::USERS_BONUSES, 'u')
            ->select(['u.sum', 'u.available'])
            ->leftJoin(self::DIRECTORY_BONUSES . ' as db', 'db.id', '=', 'u.bonus_id')
            ->where([
                ['db.active', '=', 1],
                ['db.type', '=', 2],
                ['u.active', '=', 1],
                ['u.order_id', '=', $orderId],
                ['u.user_id', '=', $userId]
            ])
            ->where(function($query) {
                $query->where('u.available', '>', time())
                    ->orWhere('u.available', '=', 0);
            })

            ->get();
    }

    /**
     * @param Order $order
     * @param $userId
     * @return int
     */
    public function getChatNewMassages(Order $order, $userId): int
    {
        $parentId = $order->parent ? : $order->id;

        return $this->getBuilder()
            ->table('chat_msg', 'cm')
            ->leftJoin('chat_rooms as cr', 'cr.id', '=' , 'cm.room_id')
            ->where([
                ['cr.order_id', '=', $parentId],
                ['cr.active', '=', 1],
                ['cr.user_table', '=', 'users'],
                ['cr.user_id', '=', $userId],
                ['cm.active', '=', 1],
                ['cm.read', '=', 0],
                ['cm.from_table', '=', 'staff']
            ])
            ->count();
    }

    /**
     * @param $userId
     * @return Model|Builder|object|null
     */
    public function getStatusesCounters($userId)
    {
        return $this->getBuilder()
            ->table('orders')
            ->select(
                DB::raw("count(if(status NOT IN ('archive'),1,NULL)) as `all`"),
                DB::raw("count(if(status IN ('new', 'waiting_information'),1,NULL)) as `new`"),
                DB::raw("count(if(status IN ('assessment', 'auto_estimated'),1,NULL)) as `assessment`"),
                DB::raw("count(if(status IN ('search_author', 'running', 'auto_estimated_paid'),1,NULL)) as `running`"),
                DB::raw("count(if(status IN ('executed_awaiting_pay'),1,NULL)) as `executed_awaiting_pay`"),
                DB::raw("count(if(status IN ('issued', 'executed'),1,NULL)) as `issued`"),
                DB::raw("count(if(status IN ('adjustment'),1,NULL)) as `adjustment`"),
                DB::raw("count(if(status IN ('archive'),1,NULL)) as `archive`"),
            )
            ->where([
                ['active', '=', 1],
                ['user_id', '=', $userId],
            ])
            ->first();
    }

    /**
     * @param Order $order
     * @return Collection
     */
    public function getOrderFiles(Order $order): Collection
    {
        return $this->getBuilder()
            ->table('files')
            ->where([
                ['active', '=', 1],
                ['order_id', '=', $order->id],
            ])
            ->whereIn('view', [2, 4])
            ->get();
    }

    /**
     * @param Order $order
     * @return int
     */
    public function getOrderPriceIncrement(Order $order): int
    {
        return $this->getBuilder()
            ->table('orders_finances')
            ->where([
                ['active', '=', 1],
                ['order_id', '=', $order->id],
                ['type', '=', 1],
            ])
            ->sum('sum');
    }

    /**
     * @param Order $order
     * @return int
     */
    public function getOrderPriceDecrement(Order $order): int
    {
        return $this->getBuilder()
            ->table('orders_finances')
            ->where([
                ['active', '=', 1],
                ['order_id', '=', $order->id],
                ['type', '=', 3],
            ])
            ->sum('sum');
    }

    /**
     * @param int $orderId
     * @param array $data
     * @return void
     */
    public function setOrderDiscounts(int $orderId, array $data): void
    {
        $this->getBuilder()
            ->table('orders')
            ->where([
                ['active', '=', 1],
                ['id', '=', $orderId],
            ])
            ->update($data);
    }

    /**
     * @param int $orderId
     * @param int $userId
     * @param int $type
     * @return Collection
     */
    public function getUserBonuses(int $orderId, int $userId, int $type = 1): Collection
    {
        return $this->getBuilder()
           ->table('users_bonuses', 'b')
           ->select("b.id", "b.left", "b.cash_back")
           ->leftJoin('directory_bonuses as d', 'b.bonus_id', '=' , 'd.id')
           ->where([
               ['b.user_id', '=', $userId],
               ['b.active', '>', 0],
               ['b.left', '>', 0],
               ['d.active', '>', 0],
               ['d.type', '=', $type]
           ])
           ->whereIn('b.order_id',[0,$orderId])
           ->where(function($query) {
               $query->where('b.available', '>', time())
                   ->orWhere('b.available', '=', 0);
           })
           ->orderBy('d.type', 'DESC')
           ->orderBy('b.order_id', 'DESC')
           ->orderBy('b.available', 'DESC')
           ->orderBy('b.date', 'ASC')
           ->get();
    }

    /**
     * @param int $promoCodeId
     * @param int $userId
     * @param bool $nDays
     * @return Model|Builder|object|null
     */
    public function getPromoCodeUsageStatistic(int $promoCodeId, int $userId, bool $nDays = true)
    {
        $res = $this->getBuilder()
            ->table('promocodes_usage_statistic')
            ->where(['user_id' => $userId, 'promocode_id' => $promoCodeId]);
        if ($nDays) {
            $res->where('usage_period', '>', 0);
        }
        $res->orderBy('created_at');

        return $res->first();
    }

    /**
     * @param int $orderId
     * @param int $userId
     * @param int $type
     * @return int|mixed
     */
    public function getUserBonusesSumm(int $orderId, int $userId, int $type = 1): mixed
    {
        return $this->getBuilder()
            ->table('users_bonuses', 'b')
            ->leftJoin('directory_bonuses as d', 'b.bonus_id', '=' , 'd.id')
            ->where([
                ['b.user_id', '=', $userId],
                ['b.active', '>', 0],
                ['b.left', '>', 0],
                ['d.active', '>', 0],
                ['d.type', '=', $type],
                ['b.cash_back', '=', 0]
            ])
            ->whereIn('b.order_id',[0,$orderId])
            ->where(function($query) {
                $query->where('b.available', '>', time())
                    ->orWhere('b.available', '=', 0);
            })
            ->orderBy('d.type', 'DESC')
            ->orderBy('b.order_id', 'DESC')
            ->orderBy('b.available', 'DESC')
            ->orderBy('b.date', 'ASC')
            ->sum('b.left');
    }

    /**
     * Получение разницы между оплатами заказов пользователем и возвратами
     * @param int $userId
     * @param bool $return
     * @return int
     */
    public function getUserTotalPaysAndReturns(int $userId, bool $return = false): int
    {
        $total = $this->getBuilder()
            ->table('orders_finances', 'f')
            ->leftJoin('orders as o', 'f.order_id', '=', 'o.id')
            ->where([
                ['o.active', '>', 0],
                ['f.active', '>', 0],
                ['o.user_id', '=', $userId],
                ['f.type', '=', 2]
            ])
            ->whereIn('f.status', ['','ok'])
            ->sum('f.sum');
        $total = $total ? (int)$total : 0;
        $totalReturns = $this->getBuilder()
            ->table('finances_return', 'f')
            ->leftJoin('orders as o', 'f.order_id', '=', 'o.id')
            ->where([
                ['o.active', '>', 0],
                ['f.active', '>', 0],
                ['o.user_id', '=', $userId],
                ['f.status', '>', 0],
            ])
            ->sum('f.sum');
        $totalReturns = $totalReturns ? (int)$totalReturns : 0;
        $total -= $totalReturns;
        return $total;
    }

    /**
     * Получение даты включение модуля кешбэка
     * @param $param
     * @return mixed
     */
    public function getCrmSettings($param): mixed
    {
        return $this->getBuilder()
            ->table('settings_vars')
            ->select('value')
            ->where(['name' => $param])
            ->first();
    }

    /**
     * Снятие блокировки с заказа
     * @param int $orderId
     * @return void
     */
    public function removeOrderBlock(int $orderId): void
    {
        $this->getBuilder()
            ->table('orders')
            ->where('id', $orderId)
            ->update(['blocked' => 0]);
    }

    public function getOrderCashBackDiscount(int $userId, bool $sum = true)
    {
        $res = $this->getBuilder()
            ->table(self::USERS_BONUSES)
            ->where(
                    [
                        ['active', '=', 1],
                        ['left', '>', 0],
                        ['cash_back', '=', 1],
                        ['user_id', '=', $userId],
                    ]
                )
            ->where(function($query) {
                $query->where('available', '>', time())
                    ->orWhere('available', '=', 0);
            })
        ;
        if ($sum) {
            $result = $res->sum('left');
        } else {
            $result = $res->get();
        }

        return $result;
    }

}
