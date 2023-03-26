<?php

namespace App\Services\OrdersLog;

use App\Services\OrdersLog\Repositories\OrdersLogRepository;
use Illuminate\Support\Collection;

/**
 * Class OrdersLogService
 *
 * @package App\Services\OrdersLog
 */
class OrdersLogService
{
    private const GROUPED_EVENTS = [
        'lk.order.chat.send',
        'chat.manager',
        'authors.new.offer',
        'authors.order.chat.send',
    ];

    /**
     * @var \App\Services\OrdersLog\Repositories\OrdersLogRepository
     */
    private OrdersLogRepository $repository;

    /**
     * OrdersLogService constructor.
     *
     * @param  \App\Services\OrdersLog\Repositories\OrdersLogRepository  $repository
     */
    public function __construct(OrdersLogRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Получить список сообщений в логе по id сообщений в чате
     *
     * @param  array  $messagesIds
     *
     * @return \Illuminate\Support\Collection
     */
    public function getLogsByChatMessagesIds(array $messagesIds): Collection
    {
        return $this->repository->getLogsByChatMessagesIds($messagesIds);
    }

    /**
     * Получить текущую нагрузку
     *
     * @param  array  $toStaffIds
     *
     * @return int
     */
    public function getCurrentNormsCount(array $toStaffIds): int
    {
        $list = $this->repository->getLogsListByToStaffIds($toStaffIds, ['id', 'order_id', 'event']);

        return $list->groupBy(function ($item, $key) {
            if (in_array($item->event, self::GROUPED_EVENTS)) {
                return implode('|', [$item->order_id, $item->event]);
            }
            return $item->id;
        })->count();
    }
}
