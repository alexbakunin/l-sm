<?php

declare(strict_types=1);

namespace App\Services\AuthorsCheckNewbie\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrdersRepository
{
    /** @var string */
    private const TABLE = 'orders';
    /** @var string */
    private const TABLE_ORDERS_AUTHORS = 'orders_authors';
    /** @var string */
    private const TABLE_ORDERS_FINANCES = 'orders_finances';

    /**
     * @return Collection
     */
    public function getEndedOrdersByAuthorIds(): Collection
    {
        return DB::connection('crm')
            ->table(self::TABLE_ORDERS_AUTHORS, 'oa')
            ->select(['oa.author_id', 'oa.order_id'])
            ->leftJoin(self::TABLE . ' as o', 'o.id', '=', 'oa.order_id')
            ->where([
                ['o.active', '=', 1],
                ['oa.active', '=', 1],
                ['oa.status', '=', 4],
            ])
            ->whereIn('o.status', ['executed', 'executed_awaiting_pay', 'unnecessary', 'adjustment'])
            ->groupBy(['oa.author_id', 'oa.order_id'])
            ->get();
    }

    /**
     * @param int $userId
     * @return Collection
     */
    public function getTotalPaysOrderByUserId(): Collection
    {
        return DB::connection('crm')
            ->table(self::TABLE_ORDERS_FINANCES, 'of')
            ->select([
                DB::raw('SUM(' . env('CRM_DB_PREFIX') . 'of.sum) as sum'),
                DB::raw('COUNT(DISTINCT ' . env('CRM_DB_PREFIX') . 'o.id) as count'),
                'o.user_id'
            ])
            ->leftJoin(self::TABLE . ' as o', 'o.id', '=', 'of.order_id')
            ->where([
                ['o.active', '=', 1],
                ['of.active', '=', 1],
                ['of.type', '=', 2],
                ['of.pay_source', '!=', 'bonuses'],
            ])
            ->groupBy(['o.user_id'])
            ->get();
    }

}
