<?php

declare(strict_types=1);

namespace App\Services\AuthorsCapacity\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrdersRepository
{
    /** @var string */
    private const TABLE = 'orders';
    /** @var string */
    private const TABLE_ORDERS_AUTHORS = 'orders_authors';

    /**
     * @return Collection
     */
    public function getProcessOrdersByAuthorIds(): Collection
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
            ->whereIn('o.status', ['running', 'suspended', 'check'])
            ->groupBy(['oa.author_id', 'oa.order_id'])
            ->get();
    }
}
