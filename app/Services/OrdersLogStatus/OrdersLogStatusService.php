<?php

namespace App\Services\OrdersLogStatus;

use App\Services\OrdersLogStatus\Repositories\OrdersLogStatusRepository;
use Illuminate\Support\Collection;

/**
 * Class OrdersLogStatusService
 *
 * @package App\Services\OrdersLog
 */
class OrdersLogStatusService
{
    /**
     * @var \App\Services\OrdersLogStatus\Repositories\OrdersLogStatusRepository
     */
    private OrdersLogStatusRepository $repository;

    /**
     * OrdersLogService constructor.
     *
     * @param  \App\Services\OrdersLogStatus\Repositories\OrdersLogStatusRepository  $repository
     */
    public function __construct(OrdersLogStatusRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Получить список Id лент просматривающего текущуюю ленту
     *
     * @param  int  $staffId
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCurrentWatchedLogIds(int $staffId): Collection
    {
        $list = $this->repository->getCurrentWatchedLogIds($staffId);

        if (!$list->count()) {
            $list->push($staffId);
        }

        return $list;
    }
}
