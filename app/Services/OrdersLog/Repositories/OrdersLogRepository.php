<?php

namespace App\Services\OrdersLog\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class OrdersLogRepository
 *
 * @package App\Services\OrdersLog\Repositories
 */
class OrdersLogRepository
{
    /** @var string */
    private const TABLE = 'orders_log';

    /**
     * Получить список сообщений в логе по id сообщений в чате
     *
     * @param  array  $messagesIds
     *
     * @return \Illuminate\Support\Collection
     */
    public function getLogsByChatMessagesIds(array $messagesIds): Collection
    {
        return $this->getBuilder()->select(['id', 'to_staff_id'])
            ->selectRaw('CAST(JSON_EXTRACT(`params`, \'$.msg_id\') as UNSIGNED) as `msgId`')
            ->where('active', '=', 1)
            ->where('params', '!=', '')
            ->where('params', '!=', 'Array')
            ->whereRaw("CAST(JSON_EXTRACT(`params`, '$.msg_id') as UNSIGNED) = (?)", implode(',', $messagesIds))
            ->get();
    }

    /**
     * @return Builder
     */
    private function getBuilder(): Builder
    {
        return DB::connection('crm')->table(self::TABLE);
    }

    /**
     * Получить список логов по получателям
     *
     * @param  array           $toStaffIds
     * @param  array|string[]  $select
     *
     * @return \Illuminate\Support\Collection
     */
    public function getLogsListByToStaffIds(array $toStaffIds, array $select = ['*']): Collection
    {
        return $this->getBuilder()->select($select)
            ->where('active', '=', 1)
            ->whereIn('to_staff_id', $toStaffIds)
            ->get();
    }

}
