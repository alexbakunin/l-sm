<?php

namespace App\Services\OrdersLogStatus\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class OrdersLogStatusRepository
 *
 * @package App\Services\OrdersLogStatus\Repositories
 */
class OrdersLogStatusRepository
{
    /** @var string */
    private const TABLE = 'orders_log_status';

    /**
     * Получить список Id лент просматривающего текущуюю ленту
     *
     * @param  int  $staffId
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCurrentWatchedLogIds(int $staffId): Collection
    {
        return $this->getBuilder()->select(['to_staff_id'])
            ->where('staff_id', '=', $this->getStaffIdByToStaffId($staffId))
            ->get()->pluck('to_staff_id');
    }

    /**
     * @return Builder
     */
    private function getBuilder(): Builder
    {
        return DB::connection('crm')->table(self::TABLE);
    }

    /**
     * Получить ID сотрудника просматривающего ленту
     *
     * @param  int  $staffId
     *
     * @return int
     */
    public function getStaffIdByToStaffId(int $staffId): int
    {
        return $this->getBuilder()->select(['staff_id'])
                ->where(
                    function ($query) use ($staffId) {
                        $query->where('staff_id', '=', $staffId)
                            ->where('to_staff_id', '=', $staffId);
                    }
                )
                ->orWhere('to_staff_id', '=', $staffId)
                ->limit(1)
                ->get()
                ->first()->staff_id ?? $staffId;
    }

}
